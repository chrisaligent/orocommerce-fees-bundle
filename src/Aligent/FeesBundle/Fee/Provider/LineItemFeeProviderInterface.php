<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2019 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Fee\Provider;

use Aligent\FeesBundle\Fee\Model\FeeLineItemDTO;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;

interface LineItemFeeProviderInterface extends FeeProviderInterface
{
    /**
     * @return array<CheckoutLineItem>
     */
    public function getCheckoutLineItems(Checkout $checkout): array;

    /**
     * @return array<FeeLineItemDTO>
     */
    public function buildFees(Checkout $checkout): array;
}
