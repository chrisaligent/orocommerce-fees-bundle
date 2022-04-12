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

use Aligent\FeesBundle\Fee\Factory\CheckoutLineItemFeeFactory;
use Aligent\FeesBundle\Fee\Model\FeeLineItemDTO;
use Aligent\FeesBundle\Fee\Provider\AbstractLineItemFeeProvider;
use Aligent\FeesBundle\Fee\Provider\FeeProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Component\Testing\Unit\EntityTrait;

class AbstractLineItemFeeProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testGetCheckoutLineItems(): void
    {
        $factory = $this->getMockBuilder(CheckoutLineItemFeeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $provider = $this->getMockBuilder(AbstractLineItemFeeProvider::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['buildFees'])
            ->getMockForAbstractClass();
        $provider->setCheckoutLineItemFeeFactory($factory);

        // Create two empty FeeLineItemDTO's
        $lineItemDTOs = [new FeeLineItemDTO(),new FeeLineItemDTO()];

        $provider->expects($this->any())
            ->method('buildFees')
            ->willReturn($lineItemDTOs);

        // Assert that calling getCheckoutLineItems() will try to create multiple Line Items
        $factory
            ->expects($this->once())->method('createMultiple')
            ->with($lineItemDTOs);

        // Create an empty Checkout and pass to Provider
        $checkout = $this->getEntity(Checkout::class);
        $provider->getCheckoutLineItems($checkout);
    }

    public function testCorrectTypeIsReturned(): void
    {
        $provider = $this->getMockBuilder(AbstractLineItemFeeProvider::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertEquals(FeeProviderInterface::TYPE_LINE_ITEM, $provider->getType());
    }
}
