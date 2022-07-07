<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\WorkflowState\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\CheckoutStateDiffMapperInterface;
use Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShoppingListLineItemDiffMapper;

/**
 * Decorate the \Oro\Bundle\CheckoutBundle\WorkflowState\Mapper\ShoppingListLineItemDiffMapper
 * added in OroCommerce 5.0.3 as this breaks LineItem Fees.
 *
 * The ShoppingListLineItemDiffMapper assumes that Checkout Line Items which were converted
 * from ShoppingList Line Items do not contain FreeForm Line Items, however our Bundle
 * may have injected those as Line Item Fees.
 * This causes fatal errors when attempting to access properties of a null object
 * (ie $item->getProduct()->getSkuUppercase() when $item->getProduct() returns null).
 *
 * NOTE: WE need to extend the ShoppingListLineItemDiffMapper as Oro expects an instance of this specific class.
 */
class ShoppingListLineItemDiffMapperDecorator extends ShoppingListLineItemDiffMapper
{
    protected CheckoutStateDiffMapperInterface $innerMapper;

    public function setInnerMapper(CheckoutStateDiffMapperInterface $innerMapper): void
    {
        $this->innerMapper = $innerMapper;
    }

    /**
     * @param mixed $entity
     * @return array<string>|null
     */
    public function getCurrentState(mixed $entity): ?array
    {
        if ($this->entityContainsFreeformLineItems($entity)) {
            /**
             * If Entity contains Freeform Line Items, return null so that a new Checkout is started
             * (and ShoppingListLineItemDiffMapper::getCurrentState is skipped).
             */
            return null;
        }

        return $this->innerMapper->getCurrentState($entity);
    }

    /**
     * Determine if $entity contains Freeform LineItems
     * (ie LineItems without a Product)
     */
    protected function entityContainsFreeformLineItems(mixed $entity): bool
    {
        if (!$entity instanceof Checkout) {
            return false;
        }

        foreach ($entity->getLineItems() as $lineItem) {
            // ProductHolderInterface incorrectly presumes that getProduct() is not nullable
            // @phpstan-ignore-next-line
            if (!$lineItem->getProduct()) {
                // This LineItem has no Product
                return true;
            }
        }

        return false;
    }

    public function isEntitySupported($entity): bool
    {
        return $this->innerMapper->isEntitySupported($entity);
    }

    public function getName(): string
    {
        return $this->innerMapper->getName();
    }

    public function isStatesEqual($entity, $state1, $state2): bool
    {
        return $this->innerMapper->isStatesEqual($entity, $state1, $state2);
    }
}
