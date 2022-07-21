<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Tests\Unit\WorkflowState\Mapper;

use Aligent\FeesBundle\WorkflowState\Mapper\ShoppingListLineItemDiffMapperDecorator;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutShippingContextProvider;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShoppingListLineItemDiffMapper;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class ShoppingListLineItemDiffMapperDecoratorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    protected MockObject|ShoppingListLineItemDiffMapper $innerMapper;
    protected ConfigManager|MockObject $configManager;
    protected MockObject|CheckoutShippingContextProvider $shipContextProvider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->shipContextProvider = $this->createMock(CheckoutShippingContextProvider::class);

        $this->innerMapper = $this->getMockBuilder(ShoppingListLineItemDiffMapper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCurrentState'])
            ->getMock();

        // A default/mock value that the innerMapper will return if it is ever called
        $this->innerMapper->expects($this->any())
            ->method('getCurrentState')
            ->willReturn(['CURRENT_STATE']);
    }

    /**
     * @dataProvider lineItemsProvider
     * @param array<array<string,mixed>> $lineItemsData
     * @param array<string>|null $expectedCurrentState
     * @return void
     */
    public function testCurrentStateIsReturnedCorrectly(array $lineItemsData, ?array $expectedCurrentState): void
    {
        $decoratedMapper = new ShoppingListLineItemDiffMapperDecorator(
            $this->configManager,
            $this->shipContextProvider
        );

        $decoratedMapper->setInnerMapper($this->innerMapper);

        $checkout = $this->getEntity(Checkout::class, [
            'id' => 123,
        ]);

        foreach ($lineItemsData as $lineItemsDatum) {
            $lineItem = $this->getEntity(CheckoutLineItem::class, $lineItemsDatum);
            $checkout->addLineItem($lineItem);
        }

        $currentState = $decoratedMapper->getCurrentState($checkout);

        $this->assertEquals($expectedCurrentState, $currentState);
    }

    public function lineItemsProvider(): \Generator
    {
        yield 'No Freeform Line Items returns array' => [
            'lineItemsData' => [
                [
                    'id' => 1,
                    'product' => $this->getEntity(Product::class, [
                        'id' => 20,
                        'sku' => 'ABC123',
                    ])
                ],
                [
                    'id' => 2,
                    'product' => $this->getEntity(Product::class, [
                        'id' => 30,
                        'sku' => 'XYZ321',
                    ])
                ],
            ],
            'expectedCurrentState' => ['CURRENT_STATE'],
        ];

        yield 'One or more Freeform Line Items returns null' => [
            'lineItemsData' => [
                [
                    'id' => 1,
                    'product' => $this->getEntity(Product::class, [
                        'id' => 20,
                        'sku' => 'ABC123',
                    ])
                ],
                [
                    'id' => 2,
                    'product' => null,
                    'freeFormProduct' => 'FREEFORM-1',
                ],
            ],
            'expectedCurrentState' => null,
        ];

        yield 'Multiple Freeform Line Items returns null' => [
            'lineItemsData' => [
                [
                    'id' => 1,
                    'product' => null,
                    'freeFormProduct' => 'FREEFORM-1',
                ],
                [
                    'id' => 2,
                    'product' => null,
                    'freeFormProduct' => 'FREEFORM-2',
                ],
            ],
            'expectedCurrentState' => null,
        ];
    }
}
