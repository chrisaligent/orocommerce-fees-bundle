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
            ->onlyMethods(['getFeeLineItems'])
            ->getMockForAbstractClass();
        $provider->setCheckoutLineItemFeeFactory($factory);

        // Create two empty FeeLineItemDTO's
        $lineItemDTOs = [new FeeLineItemDTO(),new FeeLineItemDTO()];

        // Add another FeeLineItemDTO with a Message
        $lineItemDTOWithMessage = (new FeeLineItemDTO())
            ->setProductSku('MSG-01')
            ->setLabel('Fee with a Message')
            ->setMessage('This is a test Message');
        $lineItemDTOs[] = $lineItemDTOWithMessage;

        $provider->expects($this->any())
            ->method('getFeeLineItems')
            ->willReturn($lineItemDTOs);

        // Assert that calling getCheckoutLineItems() will try to create multiple Line Items
        $factory
            ->expects($this->once())->method('createMultiple')
            ->with($lineItemDTOs);

        // Create an empty Checkout and pass to Provider
        $checkout = $this->getEntity(Checkout::class);
        $provider->getCheckoutLineItems($checkout);

        // Return the Fees which have Messages enabled
        $messageFees = $provider->getMessages($checkout);
        $this->assertCount(1, $messageFees);
        $this->assertEquals($lineItemDTOWithMessage, $messageFees[0]);
        $this->assertEquals('MSG-01', $messageFees[0]->getProductSku());
        $this->assertEquals('This is a test Message', $messageFees[0]->getMessage());
    }

    public function testCorrectTypeIsReturned(): void
    {
        $provider = $this->getMockBuilder(AbstractLineItemFeeProvider::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->assertEquals(FeeProviderInterface::TYPE_LINE_ITEM, $provider->getType());
    }
}
