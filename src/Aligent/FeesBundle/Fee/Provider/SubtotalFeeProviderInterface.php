<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Fee\Provider;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\SubtotalProviderInterface;

/**
 * NOTE: This interface should not be used directly, instead extend one of the Abstract Fee providers.
 */
interface SubtotalFeeProviderInterface extends FeeProviderInterface, SubtotalProviderInterface
{

}
