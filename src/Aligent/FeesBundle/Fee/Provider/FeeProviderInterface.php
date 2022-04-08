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

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;

interface FeeProviderInterface
{
    public const TYPE_LINE_ITEM = 'lineItem';
    public const TYPE_SUBTOTAL = 'subtotal';

    /**
     * Get Fee name
     */
    public function getName(): string;

    /**
     * Type of Fee
     */
    public function getType(): string;

    /**
     * Is the fee Applicable to this Checkout?
     * e.g. exclude Account Customers
     */
    public function isApplicable(Checkout $checkout): bool;
}
