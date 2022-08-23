<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Tests\Unit\Fee\Factory;

use Aligent\FeesBundle\Fee\Factory\CheckoutLineItemFeeFactory;
use Aligent\FeesBundle\Fee\Model\FeeLineItemDTO;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class CheckoutLineItemFeeFactoryTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    protected ProductUnitRepository|MockObject $productUnitRepository;

    protected function setUp(): void
    {
        $this->productUnitRepository = $this->getMockBuilder(ProductUnitRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findOneBy'])
            ->getMock();

        $this->productUnitRepository->expects($this->any())
            ->method('findOneBy')
            ->with(['code' => 'each'])
            ->willReturn($this->getEntity(ProductUnit::class, [
                'code' => 'each',
            ]));
    }

    /**
     * @dataProvider singleFeeDataProvider
     * @param array<string,mixed> $feeConfigData
     * @param array<string,mixed> $expectedLineItemData
     */
    public function testCreate(array $feeConfigData, array $expectedLineItemData): void
    {
        $factory = new CheckoutLineItemFeeFactory($this->productUnitRepository);

        $feeLineItemDTO = new FeeLineItemDTO();
        $feeLineItemDTO
            ->setAmount($feeConfigData['amount'])
            ->setCurrency($feeConfigData['currency'])
            ->setLabel($feeConfigData['label'])
            ->setProductSku($feeConfigData['productSku'])
            ->setProductUnitCode($feeConfigData['productUnitCode']);

        $lineItem = $factory->create($feeLineItemDTO);
        $this->assertInstanceOf(CheckoutLineItem::class, $lineItem);

        $this->assertNull($lineItem->getProduct());
        $this->assertEquals($expectedLineItemData['quantity'], $lineItem->getQuantity());
        $this->assertEquals($expectedLineItemData['freeFormProduct'], $lineItem->getFreeFormProduct());
        $this->assertEquals($expectedLineItemData['productSku'], $lineItem->getProductSku());

        $this->assertInstanceOf(ProductUnit::class, $lineItem->getProductUnit());
        $this->assertEquals($expectedLineItemData['productUnitCode'], $lineItem->getProductUnit()->getCode());
        $this->assertEquals($expectedLineItemData['productUnitCode'], $lineItem->getProductUnitCode());

        $this->assertInstanceOf(Price::class, $lineItem->getPrice());
        $this->assertEquals($expectedLineItemData['amount'], $lineItem->getPrice()->getValue());
        $this->assertEquals($expectedLineItemData['currency'], $lineItem->getPrice()->getCurrency());
        $this->assertEquals($expectedLineItemData['amount'], $lineItem->getValue());
        $this->assertEquals($expectedLineItemData['currency'], $lineItem->getCurrency());
    }

    public function singleFeeDataProvider(): \Generator
    {
        yield 'Test Single Fee' => [
            'feeConfigData' => [
                'currency' => 'USD',
                'amount' => 123.45,
                'label' => 'Handling Fee',
                'productSku' => 'ABC123',
                'productUnitCode' => 'each',
            ],
            'expectedLineItemData' => [
                'currency' => 'USD',
                'quantity' => 1.0,
                'amount' => 123.45,
                'freeFormProduct' => 'Handling Fee',
                'productSku' => 'ABC123',
                'productUnitCode' => 'each',
            ]
        ];
    }

    /**
     * @dataProvider multipleFeeDataProvider(): \Generator
     * @param array<array<string,mixed>> $feeConfigData
     * @param array<array<string,mixed>> $expectedLineItemData
     */
    public function testCreateMultiple(array $feeConfigData, array $expectedLineItemData): void
    {
        $factory = new CheckoutLineItemFeeFactory($this->productUnitRepository);

        $feeLineItemDTOs = [];
        foreach ($feeConfigData as $feeConfig) {
            $feeLineItemDTO = new FeeLineItemDTO();
            $feeLineItemDTO->setAmount($feeConfig['amount'])
                ->setCurrency($feeConfig['currency'])
                ->setProductUnitCode($feeConfig['productUnitCode'])
                ->setProductSku($feeConfig['productSku'])
                ->setLabel($feeConfig['label']);

            $feeLineItemDTOs[] = $feeLineItemDTO;
        }

        $lineItems = $factory->createMultiple($feeLineItemDTOs);
        $this->assertContainsOnlyInstancesOf(CheckoutLineItem::class, $lineItems);
        $this->assertCount(count($expectedLineItemData), $lineItems);

        $i = 0;
        foreach ($feeConfigData as $feeConfig) {
            $this->assertEquals($feeConfig['label'], $lineItems[$i]->getFreeFormProduct());
            $this->assertEquals($feeConfig['amount'], $lineItems[$i]->getValue());
            $this->assertEquals($feeConfig['productSku'], $lineItems[$i]->getProductSku());
            $this->assertEquals($feeConfig['productUnitCode'], $lineItems[$i]->getProductUnitCode());
            $this->assertEquals($feeConfig['currency'], $lineItems[$i]->getCurrency());
            $i++;
        }
    }

    public function multipleFeeDataProvider(): \Generator
    {
        yield 'Test Multiple Fees' => [
            'feeConfigData' => [
                'Handling Fee 1' => [
                    'currency' => 'USD',
                    'amount' => 123.45,
                    'label' => 'Handling Fee 1',
                    'productSku' => 'ABC123',
                    'productUnitCode' => 'each',
                ],
                'Handling Fee 2' => [
                    'currency' => 'USD',
                    'amount' => 987.65,
                    'label' => 'Handling Fee 2',
                    'productSku' => 'XYZ321',
                    'productUnitCode' => 'each',
                ],
            ],
            'expectedLineItemData' => [
                [
                    'currency' => 'USD',
                    'quantity' => 1.0,
                    'amount' => 123.45,
                    'freeFormProduct' => 'Handling Fee 1',
                    'productSku' => 'ABC123',
                    'productUnitCode' => 'each',
                ],
                [
                    'currency' => 'USD',
                    'quantity' => 1.0,
                    'amount' => 987.65,
                    'freeFormProduct' => 'Handling Fee 2',
                    'productSku' => 'XYZ321',
                    'productUnitCode' => 'each',
                ],
            ]
        ];
    }
}
