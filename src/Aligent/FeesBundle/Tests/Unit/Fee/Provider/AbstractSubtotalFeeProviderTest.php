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

use Aligent\FeesBundle\Fee\Provider\AbstractSubtotalFeeProvider;
use Aligent\FeesBundle\Fee\Provider\FeeProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Exception\InvalidRoundingTypeException;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Provider\SubtotalProviderConstructorArguments;
use Oro\Bundle\PricingBundle\Tests\Unit\SubtotalProcessor\Provider\AbstractSubtotalProviderTest;
use Oro\Bundle\TaskBundle\Entity\Task;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractSubtotalFeeProviderTest extends AbstractSubtotalProviderTest
{
    use EntityTrait;

    protected TranslatorInterface|MockObject $translator;
    protected RoundingServiceInterface|MockObject $roundingService;
    protected ConfigManager|MockObject $configManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->roundingService = $this->createMock(RoundingServiceInterface::class);
        $this->roundingService->expects($this->any())
            ->method('round')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return round($value, 2, PHP_ROUND_HALF_UP);
                    }
                )
            );

        $this->configManager = $this->createMock(ConfigManager::class);
    }

    /**
     * @throws InvalidRoundingTypeException
     */
    public function testUnsupportedEntityIsRejected(): void
    {
        $supportedEntity = $this->getEntity(Checkout::class);
        $unsupportedEntity = $this->getEntity(Task::class);

        $provider = $this->getMockBuilder(AbstractSubtotalFeeProvider::class)
            ->onlyMethods(['isSupported'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $provider->expects($this->any())
            ->method('isSupported')
            ->willReturnMap([
                [$supportedEntity, true],
                [$unsupportedEntity, false],
            ]);

        // A Supported entity will return null
        $this->assertNull($provider->getSubtotal($supportedEntity));

        // An Unsupported entity will throw an exception
        $this->expectExceptionMessage('Entity not supported for provider');
        $provider->getSubtotal($unsupportedEntity);
    }

    /**
     * @dataProvider getSubtotalFeeData
     *
     * @param float|null $feeAmount
     * @param string|null $feeLabel
     * @param array<string,mixed>|null $expectedSubtotal
     * @return void
     * @throws InvalidRoundingTypeException
     */
    public function testSubtotalIsReturned(
        ?float $feeAmount,
        ?string $feeLabel,
        ?array $expectedSubtotal,
    ): void {
        $checkout = $this->getEntity(Checkout::class);

        $provider = $this->getMockBuilder(AbstractSubtotalFeeProvider::class)
            ->onlyMethods(['getFeeAmount', 'getFeeLabel', 'isSupported'])
            ->setConstructorArgs([
                new SubtotalProviderConstructorArguments($this->currencyManager, $this->websiteCurrencyProvider),
                $this->configManager,
                $this->translator,
                $this->roundingService,
            ])
            ->getMockForAbstractClass();

        $provider->expects($this->any())
            ->method('isSupported')
            ->with($checkout)
            ->willReturn(true);

        if (!is_null($feeAmount)) {
            $provider->expects($this->any())
                ->method('getFeeAmount')
                ->with($checkout)
                ->willReturn($feeAmount);
        }

        if (!is_null($feeLabel)) {
            $provider->expects($this->any())
                ->method('getFeeLabel')
                ->willReturn($feeLabel);
        }

        $subtotal = $provider->getSubtotal($checkout);

        if (is_null($expectedSubtotal)) {
            $this->assertNull($subtotal);
        } else {
            $this->assertInstanceOf(Subtotal::class, $subtotal);
            $this->assertEquals($expectedSubtotal['amount'], $subtotal->getAmount());
            $this->assertEquals($expectedSubtotal['label'], $subtotal->getLabel());
        }
    }

    public function getSubtotalFeeData(): \Generator
    {
        yield 'No Fee Applicable' => [
            'feeAmount' => null,
            'feeLabel' => null,
            'expectedSubtotal' => null,
        ];

        yield 'Fee applicable' => [
            'feeAmount' => 12.34,
            'feeLabel' => 'Abstract Fee',
            'expectedFee' => [
                'amount' => 12.34,
                'label' => 'Abstract Fee'
            ],
        ];
    }

    public function testCorrectTypeIsReturned(): void
    {
        $provider = $this->getMockBuilder(AbstractSubtotalFeeProvider::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertEquals(FeeProviderInterface::TYPE_SUBTOTAL, $provider->getType());
    }
}
