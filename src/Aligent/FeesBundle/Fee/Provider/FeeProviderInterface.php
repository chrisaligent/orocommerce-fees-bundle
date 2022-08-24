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

/**
 * NOTE: This interface should not be used directly, instead extend one of the Abstract Fee providers.
 */
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
}
