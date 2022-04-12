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

use Aligent\FeesBundle\EventListener\CreateCheckoutListener;
use Aligent\FeesBundle\Fee\Provider\AbstractLineItemFeeProvider;
use Aligent\FeesBundle\Fee\Provider\FeeProviderInterface;
use Aligent\FeesBundle\Fee\Provider\FeeProviderRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

class CreateCheckoutListenerTest extends \PHPUnit\Framework\TestCase
{
    protected FeeProviderRegistry|MockObject $feeProviderRegistry;
    protected ManagerRegistry|MockObject $managerRegistry;
    protected ObjectManager|MockObject $objectManager;

    use EntityTrait;

    protected function setUp(): void
    {
        $this->feeProviderRegistry = $this->getMockBuilder(FeeProviderRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getProviders'])
            ->getMock();

        $this->managerRegistry = $this->getMockBuilder(ManagerRegistry::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getManagerForClass'])
            ->getMockForAbstractClass();

        $this->objectManager = $this->createMock(ObjectManager::class);

        $this->managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Checkout::class)
            ->willReturn($this->objectManager);
    }

    public function testOnStartCheckoutConditionCheckWithoutFees(): void
    {
        /**
         * Create Checkout and Add to Context
         */
        $checkout = $this->getEntity(Checkout::class);
        $context = new ActionData([
            'checkout' => $checkout,
        ]);
        $event = new ExtendableConditionEvent();
        $event->setContext($context);

        $this->assertEmpty($checkout->getLineItems());

        $this->objectManager->expects($this->never())
            ->method('persist');
        $this->objectManager->expects($this->never())
            ->method('flush');

        // Fire Event
        $listener = new CreateCheckoutListener($this->feeProviderRegistry, $this->managerRegistry);
        $listener->onStartCheckoutConditionCheck($event);
    }

    public function testOnStartCheckoutConditionCheckWithFees(): void
    {
        /**
         * Create Checkout with a single line item
         */
        $checkout = $this->getEntity(Checkout::class);
        $checkoutLineItem = $this->getEntity(CheckoutLineItem::class, [
            'checkout' => $checkout,
        ]);
        $checkout->addLineItem($checkoutLineItem);
        $this->assertCount(1, $checkout->getLineItems());

        /**
         * Add Checkout to Context
         */
        $context = new ActionData([
            'checkout' => $checkout,
        ]);
        $event = new ExtendableConditionEvent();
        $event->setContext($context);

        /**
         * Create Fee Line Items
         */
        $feeLineItems1 = [
            $this->getEntity(CheckoutLineItem::class),
            $this->getEntity(CheckoutLineItem::class),
        ];

        $feeLineItems2 = [
            $this->getEntity(CheckoutLineItem::class),
        ];

        /**
         * Register two new Fee Providers
         */
        $feeProvider1 = $this->getMockBuilder(AbstractLineItemFeeProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCheckoutLineItems'])
            ->getMockForAbstractClass();
        $feeProvider2 = $this->getMockBuilder(AbstractLineItemFeeProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCheckoutLineItems'])
            ->getMockForAbstractClass();

        $feeProvider1->expects($this->any())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn($feeLineItems1);

        $feeProvider2->expects($this->any())
            ->method('getCheckoutLineItems')
            ->with($checkout)
            ->willReturn($feeLineItems2);

        /**
         * Fee Provider Registry should return both Providers
         */
        $this->feeProviderRegistry->expects($this->any())
            ->method('getProviders')
            ->with(FeeProviderInterface::TYPE_LINE_ITEM)
            ->willReturn([$feeProvider1, $feeProvider2]);

        // Expect ObjectManager to persist/flush once
        $this->objectManager->expects($this->once())
            ->method('persist');
        $this->objectManager->expects($this->once())
            ->method('flush');

        // Fire Event
        $listener = new CreateCheckoutListener($this->feeProviderRegistry, $this->managerRegistry);
        $listener->onStartCheckoutConditionCheck($event);

        // Expect 3 additional Line Items on top of original Line Item (Total 4)
        $this->assertCount(4, $checkout->getLineItems());

        // Expect all Line Items
        foreach ($feeLineItems1 as $feeLineItem) {
            $this->assertContains($feeLineItem, $checkout->getLineItems());
        }
        foreach ($feeLineItems2 as $feeLineItem) {
            $this->assertContains($feeLineItem, $checkout->getLineItems());
        }
    }
}
