<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Tests\Unit\Fee\Provider;

use Aligent\FeesBundle\Fee\Provider\PaymentProcessingFeeProvider;
use Aligent\FeesBundle\Tests\Unit\Entity\Stub\OrderStub;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentProcessingFeeProviderTest extends AbstractSubtotalProviderTest
{
    use EntityTrait;

    protected TranslatorInterface|MockObject $translator;
    protected RoundingServiceInterface|MockObject $roundingService;
    protected ConfigManager|MockObject $configManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static function ($message) {
                    return $message;
                }
            );

        $this->roundingService = $this->getMockBuilder(RoundingServiceInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['round', 'getPrecision', 'getRoundType'])
            ->getMock();

        $this->roundingService->expects($this->any())
            ->method('round')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return round($value, 2, PHP_ROUND_HALF_UP);
                    }
                )
            );

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
    }

    /**
     * @dataProvider getApplicableEntityData
     *
     * @template T
     * @param class-string<T> $entityClass
     * @param bool $expectApplicable
     * @return void
     * @throws InvalidRoundingTypeException
     */
    public function testApplicableEntityType(string $entityClass, bool $expectApplicable): void
    {
        $entity = $this->getEntity($entityClass);

        $provider = new PaymentProcessingFeeProvider(
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider),
            $this->configManager,
            $this->translator,
            $this->roundingService,
        );

        if ($expectApplicable) {
            // Applicable Entity, no exception thrown
            $this->assertNull($provider->getSubtotal($entity));
        } else {
            // Expect an Exception
            $this->expectExceptionMessage('Entity not supported for provider');
            $provider->getSubtotal($entity);
        }
    }

    public function getApplicableEntityData(): \Generator
    {
        yield 'Invalid Entity Type' => [
            'entityClass' => Customer::class,
            'expectApplicable' => false,
        ];

        yield 'Invalid Order Entity' => [
            // The Order entity does not contain the processing_fee field
            'entityClass' => Order::class,
            'expectApplicable' => false,
        ];

        yield 'Valid Order Entity is Applicable' => [
            // The OrderStub contains the processing_fee field
            'entityClass' => OrderStub::class,
            'expectApplicable' => true,
        ];
    }

    public function testProcessingFeeIsNotRecalculatedUnlessForced(): void
    {
        $entity = $this->getEntity(OrderStub::class, [
            'id' => 27,
            'processing_fee' => 12.34,
        ]);

        $provider = $this->getMockBuilder(PaymentProcessingFeeProvider::class)
            ->onlyMethods(['calculateProcessingFee'])
            ->setConstructorArgs([
                new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider),
                $this->configManager,
                $this->translator,
                $this->roundingService,
            ])
            ->getMock();

        $provider->expects($this->once())
            ->method('calculateProcessingFee')
            ->willReturn(27.50);

        $subtotal = $provider->getSubtotal($entity);

        // This should use the fee assigned to the OrderStub entity
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(12.34, $subtotal->getAmount());
        $this->assertEquals('aligent.fees.checkout.subtotal.processing_fee.label', $subtotal->getLabel());

        // Trigger recalculation
        $provider->setForceRecalculation(true);
        $subtotal = $provider->getSubtotal($entity);

        // This will use the mocked amount from calculateProcessingFee
        $this->assertInstanceOf(Subtotal::class, $subtotal);
        $this->assertEquals(27.50, $subtotal->getAmount());
        $this->assertEquals('aligent.fees.checkout.subtotal.processing_fee.label', $subtotal->getLabel());
    }

    public function testProcessingFeesCanBeDisabled(): void
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['aligent_fees.processing_fee_enabled', false, false, null, false],
            ]);

        $provider = new PaymentProcessingFeeProvider(
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider),
            $this->configManager,
            $this->translator,
            $this->roundingService,
        );

        $this->assertNull($provider->getSubtotal($this->getEntity(OrderStub::class)));
    }

    /**
     * @dataProvider getProcessingFeeData
     *
     * @param array<string,float> $feeConfiguration
     * @param float $orderSubtotal
     * @param float|null $expectedFeeAmount
     * @return void
     * @throws InvalidRoundingTypeException
     */
    public function testProcessingFeesAreApplied(
        array $feeConfiguration,
        float $orderSubtotal,
        ?float $expectedFeeAmount,
    ): void {
        $order = $this->getEntity(OrderStub::class);

        $subtotal = new Subtotal();
        $subtotal->setAmount($orderSubtotal)
                ->setCurrency('USD')
                ->setSortOrder(0)
                ->setLabel('Subtotal');

        $subtotalProviderRegistry = $this->getMockBuilder(SubtotalProviderRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getSupportedProviders'])
            ->getMock();

        $subtotalProviderRegistry->expects($this->any())
            ->method('getSupportedProviders')
            ->willReturn([]);

        $totalProcessorProvider = $this->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTotalForSubtotals'])
            ->getMock();

        $totalProcessorProvider->expects($this->any())
            ->method('getTotalForSubtotals')
            ->willReturn($subtotal);

        $entityPaymentMethodsProvider = $this->getMockBuilder(EntityPaymentMethodsProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getPaymentMethods'])
            ->getMock();

        $entityPaymentMethodsProvider->expects($this->any())
            ->method('getPaymentMethods')
            ->with($order)
            ->willReturn(['payment_method_1']);

        $this->configManager->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['aligent_fees.processing_fee_enabled', false, false, null, true],
                ['aligent_fees.processing_fee_payment_methods', false, false, null, $feeConfiguration],
            ]);

        $provider = new PaymentProcessingFeeProvider(
            new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider),
            $this->configManager,
            $this->translator,
            $this->roundingService,
        );

        $provider->setSubtotalProviderRegistry($subtotalProviderRegistry);
        $provider->setTotalProcessorProvider($totalProcessorProvider);
        $provider->setEntityPaymentMethodsProvider($entityPaymentMethodsProvider);


        $feeSubtotal = $provider->getSubtotal($order);

        if ($expectedFeeAmount > 0) {
            $this->assertInstanceOf(Subtotal::class, $feeSubtotal);
            $this->assertEquals($expectedFeeAmount, $feeSubtotal->getAmount());
            $this->assertEquals('aligent.fees.checkout.subtotal.processing_fee.label', $feeSubtotal->getLabel());
            $this->assertEquals($expectedFeeAmount, $order->getProcessingFee());
        } else {
            $this->assertNull($feeSubtotal);
            $this->assertEquals(0.00, $order->getProcessingFee());
        }
    }

    public function getProcessingFeeData(): \Generator
    {
        yield '10% fee on $100 order' => [
            'feeConfiguration' => [
                'payment_method_1' => ['percentage' => 10.00],
            ],
            'orderSubtotal' => 100.00,
            'expectedFeeAmount' => 10.00,
        ];

        yield '7.23% fee on $147.51 order' => [
            'feeConfiguration' => [
                'payment_method_1' => ['percentage' => 7.23],
            ],
            'orderSubtotal' => 147.51,
            'expectedFeeAmount' => 10.66,
        ];

        yield 'Fee applies to other payment method' => [
            'feeConfiguration' => [
                // Our Order uses payment_method_1
                'payment_method_2' => ['percentage' => 15.00],
            ],
            'orderSubtotal' => 250.21,
            'expectedFeeAmount' => null,
        ];
    }
}
