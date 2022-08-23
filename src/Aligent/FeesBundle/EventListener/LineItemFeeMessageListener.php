<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\EventListener;

use Aligent\FeesBundle\Fee\Provider\FeeProviderInterface;
use Aligent\FeesBundle\Fee\Provider\FeeProviderRegistry;
use Aligent\FeesBundle\Fee\Provider\LineItemFeeProviderInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

class LineItemFeeMessageListener
{
    protected FeeProviderRegistry $feeProviderRegistry;

    public const ALLOWED_STEPS = [
        'order_review' => true,
        'checkout' => true,
        'enter_billing_address' => true,
        'enter_shipping_address' => true,
        'enter_shipping_method' => true,
        'enter_payment' => true
    ];

    public function __construct(
        FeeProviderRegistry $feeProviderRegistry
    ) {
        $this->feeProviderRegistry = $feeProviderRegistry;
    }

    /**
     * Called when validating a LineItem
     */
    public function onLineItemValidate(LineItemValidateEvent $event): void
    {
        $context = $event->getContext();

        if (!$this->isContextSupported($context)) {
            // The ShoppingList has no Context, exclude it
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $context->getEntity();

        foreach ($this->feeProviderRegistry->getProviders(FeeProviderInterface::TYPE_LINE_ITEM) as $feeProvider) {
            if (!$feeProvider instanceof LineItemFeeProviderInterface) {
                continue;
            }

            foreach ($feeProvider->getMessages($checkout) as $feeMessage) {
                $event->addWarningByUnit(
                    $feeMessage->getProductSku(),
                    $feeMessage->getProductUnitCode(),
                    $feeMessage->getMessage()
                );
            }
        }
    }

    protected function isContextSupported(mixed $context): bool
    {
        return $context instanceof WorkflowItem
            && $context->getEntity() instanceof Checkout
            && !empty(self::ALLOWED_STEPS[$context->getCurrentStep()->getName()]);
    }
}
