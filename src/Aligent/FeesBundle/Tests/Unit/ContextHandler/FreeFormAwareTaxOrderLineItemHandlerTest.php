<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Tests\Unit\ContextHandler;

use Aligent\FeesBundle\ContextHandler\FreeFormAwareTaxOrderLineItemHandler;
use Aligent\FeesBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Event\ContextEvent;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;
use Oro\Bundle\TaxBundle\Provider\TaxCodeProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FreeFormAwareTaxOrderLineItemHandlerTest extends TestCase
{
    use EntityTrait;

    protected ConfigManager|MockObject $configManager;
    protected TaxCodeProvider|MockObject $taxCodeProvider;
    protected TaxationAddressProvider|MockObject $taxationAddressProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();

        $this->taxationAddressProvider = $this->createMock(TaxationAddressProvider::class);

        $this->taxCodeProvider = $this->getMockBuilder(TaxCodeProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTaxCode'])
            ->getMock();
    }

    /**
     * @dataProvider configuredTaxCodeProvider
     */
    public function testConfiguredProductTaxCode(
        ?string $configuredTaxCode,
        ?Product $product,
        ?string $productTaxCode,
        ?string $expectedTaxCodeCode,
    ): void {
        $order = $this->getEntity(Order::class);

        $orderLineItem = $this->getEntity(OrderLineItem::class, [
            'order' => $order,
            'product' => $product,
            'productSku' => 'FEE1',
            'productName' => 'Handling Fee',
            'freeFormProduct' => 'Handling Fee',
        ]);

        $productTaxCode = null;
        if ($expectedTaxCodeCode) {
            $productTaxCode = $this->getEntity(ProductTaxCode::class, [
                'code' => $expectedTaxCodeCode,
            ]);
        }

        $this->taxCodeProvider->expects($this->any())
            ->method('getTaxCode')
            ->with(TaxCodeInterface::TYPE_PRODUCT, $orderLineItem->getProduct())
            ->willReturn($productTaxCode);

        $handler = new FreeFormAwareTaxOrderLineItemHandler(
            $this->taxationAddressProvider,
            $this->taxCodeProvider,
            OrderLineItem::class
        );

        $this->configManager->expects($this->any())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_PRODUCT_TAX_CODE))
            ->willReturn($configuredTaxCode);

        $handler->setConfigManager($this->configManager);

        $contextEvent = new ContextEvent($orderLineItem);
        $handler->onContextEvent($contextEvent);

        $context = $contextEvent->getContext();

        $this->assertEquals($expectedTaxCodeCode, $context->offsetGet(Taxable::PRODUCT_TAX_CODE));
    }

    /**
     * @return \Generator<string,array<string,mixed>>
     */
    public function configuredTaxCodeProvider(): \Generator
    {
        /**
         * Line Item has Product without a Tax Code, and our Custom Configuration has been set.
         * Expect no Tax Code as per core Oro functionality, as our decorated class
         * should not apply unless the Line Item is a Freeform (ie has no Product).
         */
        yield 'Tax Code Configured, Line Item Product without Tax Code' => [
            'configuredTaxCode' => 'CONFIGURED_TAX_1',
            'product' => $this->getEntity(Product::class, [
                'sku' => 'ABC123',
            ]),
            'productTaxCode' => null,
            'expectedTaxCode' => null,
        ];

        /**
         * Line Item has Product with Tax Code, and our Custom Configuration has been set.
         * Expect the Product Tax Code as per core Oro functionality.
         */
        yield 'Tax Code Configured, Line Item Product with Tax Code' => [
            'configuredTaxCode' => 'CONFIGURED_TAX_2',
            'product' => $this->getEntity(Product::class, [
                'sku' => 'ABC123',
            ]),
            'productTaxCode' => 'TAXABLE_PRODUCT_2',
            'expectedTaxCode' => 'TAXABLE_PRODUCT_2',
        ];

        /**
         * Freeform Line Item, but our custom Configuration has been set.
         * Expect our custom Tax Code instead of null.
         */
        yield 'Tax Code Configured, Freeform Line Item' => [
            'configuredTaxCode' => 'CONFIGURED_TAX_3',
            'product' => null,
            'productTaxCode' => null,
            'expectedTaxCode' => 'CONFIGURED_TAX_3',
        ];

        /**
         * Freeform Line Item, and our custom Configuration has not been set.
         * Expect no tax code as per core Oro functionality.
         */
        yield 'Tax Code not Configured, Freeform Line Item' => [
            'configuredTaxCode' => null,
            'product' => null,
            'productTaxCode' => null,
            'expectedTaxCode' => null,
        ];
    }
}
