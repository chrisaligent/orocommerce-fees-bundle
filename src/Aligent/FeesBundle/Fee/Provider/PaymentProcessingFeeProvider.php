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

use Aligent\FeesBundle\DependencyInjection\Configuration;
use Brick\Math\BigDecimal;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Payment\Method\EntityPaymentMethodsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\SubtotalProviderRegistry;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class PaymentProcessingFeeProvider extends AbstractSubtotalFeeProvider
{
    const NAME = 'payment_processing_fee';
    const SUBTOTAL_SORT_ORDER = 200;

    protected EntityPaymentMethodsProvider $entityPaymentMethodsProvider;
    protected SubtotalProviderRegistry $subtotalProviderRegistry;
    protected TotalProcessorProvider $totalProcessorProvider;

    protected bool $forceRecalculation = false;

    public function isSupported(mixed $entity): bool
    {
        return $entity instanceof Order && method_exists($entity, 'getProcessingFee');
    }

    public function getName(): string
    {
        return self::NAME;
    }

    protected function getSortOrder(): int
    {
        return self::SUBTOTAL_SORT_ORDER;
    }

    protected function getFeeAmount(mixed $entity): ?float
    {
        if (!$this->isSupported($entity)) {
            throw new \InvalidArgumentException('Entity not supported for provider');
        }

        if ($entity->getId() && !$this->forceRecalculation) {
            // Load fee from Entity (ie persisted against Order)
            $fee = $entity->getProcessingFee();
        } else {
            // Calculate the fee and assign it back to the Entity
            $fee = $this->calculateProcessingFee($entity);
            $entity->setProcessingFee($fee);
        }

        return $fee;
    }

    protected function calculateProcessingFee(object $entity): ?float
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $feeConfiguration = $this->getPaymentMethodFeeConfiguration($entity);
        if (!$feeConfiguration) {
            return null;
        }

        $percentage = $feeConfiguration['percentage'] / 100;

        $subtotals = $this->getSubtotalsForEntity($entity);
        $subtotal = $this->totalProcessorProvider->getTotalForSubtotals($entity, $subtotals);

        $amount = BigDecimal::of($subtotal->getAmount());

        return $amount->multipliedBy($percentage)->toFloat();
    }

    /**
     * @param object $entity
     * @return ArrayCollection<int,Subtotal>
     */
    protected function getSubtotalsForEntity(object $entity): ArrayCollection
    {
        $subtotals = [];
        foreach ($this->subtotalProviderRegistry->getSupportedProviders($entity) as $provider) {
            if ($provider instanceof self) {
                // Skip self when determining subtotal of Entity
                continue;
            }

            $entitySubtotals = $provider->getSubtotal($entity);
            $entitySubtotals = is_object($entitySubtotals) ? [$entitySubtotals] : (array) $entitySubtotals;
            foreach ($entitySubtotals as $subtotal) {
                $subtotals[] = $subtotal;
            }
        }

        // phpcs:ignore PHPCS_SecurityAudit.BadFunctions.CallbackFunctions.WarnCallbackFunctions
        usort($subtotals, function (Subtotal $leftSubtotal, Subtotal $rightSubtotal) {
            return $leftSubtotal->getSortOrder() - $rightSubtotal->getSortOrder();
        });

        return new ArrayCollection($subtotals);
    }

    protected function isEnabled(): bool
    {
        return (bool)$this->getConfiguration(Configuration::PROCESSING_FEE_ENABLED, false);
    }

    /**
     * @param object $entity
     * @return array<string,mixed>|null
     */
    protected function getPaymentMethodFeeConfiguration(object $entity): ?array
    {
        $paymentMethod = $this->getPaymentMethod($entity);
        if (!$paymentMethod) {
            return null;
        }

        $feeConfiguration = (array)$this->getConfiguration(Configuration::PROCESSING_FEE_PAYMENT_METHODS);

        if (!array_key_exists($paymentMethod, $feeConfiguration)) {
            return null;
        }

        $paymentMethodFeeConfiguration = $feeConfiguration[$paymentMethod];

        if (!array_key_exists('percentage', $paymentMethodFeeConfiguration)) {
            return null;
        }

        if ($paymentMethodFeeConfiguration['percentage'] > 0) {
            return $paymentMethodFeeConfiguration;
        }

        return null;
    }

    protected function getPaymentMethod(object $entity): ?string
    {
        if ($entity instanceof Order) {
            $paymentMethods = $this->entityPaymentMethodsProvider->getPaymentMethods($entity);
            return current($paymentMethods);
        }
        return null;
    }

    public function setForceRecalculation(bool $forceRecalculation): void
    {
        $this->forceRecalculation = $forceRecalculation;
    }

    public function setEntityPaymentMethodsProvider(EntityPaymentMethodsProvider $entityPaymentMethodsProvider): void
    {
        $this->entityPaymentMethodsProvider = $entityPaymentMethodsProvider;
    }

    public function setSubtotalProviderRegistry(SubtotalProviderRegistry $subtotalProviderRegistry): void
    {
        $this->subtotalProviderRegistry = $subtotalProviderRegistry;
    }

    public function setTotalProcessorProvider(TotalProcessorProvider $totalProcessorProvider): void
    {
        $this->totalProcessorProvider = $totalProcessorProvider;
    }
}
