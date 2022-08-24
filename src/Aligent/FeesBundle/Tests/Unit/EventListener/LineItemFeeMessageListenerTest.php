<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Tests\Unit\EventListener;

use Aligent\FeesBundle\EventListener\LineItemFeeMessageListener;
use Aligent\FeesBundle\Fee\Model\FeeLineItemDTO;
use Aligent\FeesBundle\Fee\Provider\AbstractLineItemFeeProvider;
use Aligent\FeesBundle\Fee\Provider\FeeProviderRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Iterator;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class LineItemFeeMessageListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    protected MockObject|FeeProviderRegistry $feeProviderRegistry;

    protected function setUp(): void
    {
        $this->feeProviderRegistry = $this->getMockBuilder(FeeProviderRegistry::class)
            ->onlyMethods(['getProviders'])
            ->getMock();
    }

    /**
     * @dataProvider getContextData
     */
    public function testOnlyCheckoutIsSupported(mixed $context, int $expectedCallCount): void
    {
        $listener = new LineItemFeeMessageListener($this->feeProviderRegistry);

        $lineItems = [$this->getEntity(LineItem::class)];

        $this->feeProviderRegistry->expects($this->exactly($expectedCallCount))
            ->method('getProviders');

        $event = new LineItemValidateEvent($lineItems, $context);
        $listener->onLineItemValidate($event);
    }

    /**
     * @throws WorkflowException
     * @return Iterator<string,mixed>
     */
    public function getContextData(): Iterator
    {
        yield 'Invalid Context' => [
            'context' => [],
            'expectedCallCount' => 0,
        ];

        $context = new WorkflowItem();
        $context->setEntity($this->getEntity(Product::class));
        yield 'Invalid Entity' => [
            'context' => $context,
            'expectedCallCount' => 0,
        ];

        $context = new WorkflowItem();
        $context->setEntity($this->getEntity(Checkout::class));
        $context->setCurrentStep($this->getEntity(WorkflowStep::class, [
            'name' => 'payment_fail',
        ]));
        yield 'Valid Entity & Invalid Step' => [
            'context' => $context,
            'expectedCallCount' => 0,
        ];

        $context = new WorkflowItem();
        $context->setEntity($this->getEntity(Checkout::class));
        $context->setCurrentStep($this->getEntity(WorkflowStep::class, [
            'name' => 'enter_billing_address',
        ]));
        yield 'Valid Entity & Step' => [
            'context' => $context,
            'expectedCallCount' => 1, // Provider should be called once
        ];
    }

    /**
     * @dataProvider getWarningMessageData
     * @param array<FeeLineItemDTO> $feeLineItemDTOs
     * @param ArrayCollection<string,string> $expectedWarnings
     * @return void
     * @throws WorkflowException
     */
    public function testWarningMessagesAreAdded(array $feeLineItemDTOs, ArrayCollection $expectedWarnings): void
    {
        $context = new WorkflowItem();
        $context->setEntity($this->getEntity(Checkout::class));
        $context->setCurrentStep($this->getEntity(WorkflowStep::class, [
            'name' => 'order_review',
        ]));

        $listener = new LineItemFeeMessageListener($this->feeProviderRegistry);

        $lineItems = [$this->getEntity(LineItem::class)];

        $feeProvider = $this->getMockBuilder(AbstractLineItemFeeProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMessages'])
            ->getMockForAbstractClass();

        $feeProvider->expects($this->once())
            ->method('getMessages')
            ->willReturn($feeLineItemDTOs);

        $this->feeProviderRegistry->expects($this->exactly(1))
            ->method('getProviders')
            ->willReturn([$feeProvider]);

        $event = new LineItemValidateEvent($lineItems, $context);
        $listener->onLineItemValidate($event);

        $this->assertFalse($event->hasErrors());
        $this->assertCount(count($expectedWarnings), $event->getWarnings());
        $this->assertEquals($expectedWarnings, $event->getWarnings());
        $this->assertEquals((count($expectedWarnings) > 0), $event->hasWarnings());
    }

    public function getWarningMessageData(): Iterator
    {
        yield 'No Warning Messages' => [
            'feeLineItemDTOs' => [],
            'expectedWarnings' => new ArrayCollection([]),
        ];

        yield 'One Warning Message' => [
            'feeLineItemDTOs' => [
                (new FeeLineItemDTO())
                    ->setProductSku('FEE-1')
                    ->setProductUnitCode('each')
                    ->setMessage('Fee is applied 1'),
            ],
            'expectedWarnings' => new ArrayCollection([
                [
                    'sku' => 'FEE-1',
                    'unit' => 'each',
                    'message' => 'Fee is applied 1',
                ],
            ]),
        ];

        yield 'Multiple Warning Messages' => [
            'feeLineItemDTOs' => [
                (new FeeLineItemDTO())
                    ->setProductSku('FEE-2')
                    ->setProductUnitCode('each')
                    ->setMessage('Fee is applied 2'),
                (new FeeLineItemDTO())
                    ->setProductSku('FEE-3')
                    ->setProductUnitCode('item')
                    ->setMessage('Fee is applied 3'),
            ],
            'expectedWarnings' => new ArrayCollection([
                [
                    'sku' => 'FEE-2',
                    'unit' => 'each',
                    'message' => 'Fee is applied 2',
                ],
                [
                    'sku' => 'FEE-3',
                    'unit' => 'item',
                    'message' => 'Fee is applied 3',
                ],
            ]),
        ];
    }
}
