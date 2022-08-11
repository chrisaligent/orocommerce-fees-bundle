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

use Aligent\FeesBundle\Fee\Factory\CheckoutLineItemFeeFactory;
use Aligent\FeesBundle\Fee\Model\FeeLineItemDTO;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractLineItemFeeProvider implements LineItemFeeProviderInterface
{
    protected ConfigManager $configManager;
    protected TranslatorInterface $translator;
    protected RoundingServiceInterface $rounding;
    protected CheckoutLineItemFeeFactory $checkoutLineItemFeeFactory;
    /** @var array<FeeLineItemDTO>|null  */
    protected ?array $feeLineItems = null;

    public function __construct(
        ConfigManager $configManager,
        TranslatorInterface $translator,
        RoundingServiceInterface $rounding,
        CheckoutLineItemFeeFactory $checkoutLineItemFeeFactory,
    ) {
        $this->configManager = $configManager;
        $this->translator = $translator;
        $this->rounding = $rounding;
        $this->checkoutLineItemFeeFactory = $checkoutLineItemFeeFactory;
    }

    /**
     * This method should use the passed Checkout instance to conditionally
     * inject Fees using $this->>addFeeLineItem();
     * @param Checkout $checkout
     * @return void
     */
    abstract protected function buildFees(Checkout $checkout): void;

    /**
     * Creates one or more Checkout Line Item for Fee.
     * Fee optionally based on Checkout instance
     * @return array<CheckoutLineItem>
     */
    public function getCheckoutLineItems(Checkout $checkout): array
    {
        return $this
            ->checkoutLineItemFeeFactory
            ->createMultiple($this->getFeeLineItems($checkout));
    }

    /**
     * @return FeeLineItemDTO[]
     */
    public function getFeeLineItems(Checkout $checkout): array
    {
        if (is_null($this->feeLineItems)) {
            // Only build fees once (if not null)
            $this->feeLineItems = [];
            $this->buildFees($checkout);
        }

        return $this->feeLineItems;
    }

    protected function addFeeLineItem(FeeLineItemDTO $feeLineItemDTO): void
    {
        $this->feeLineItems[] = $feeLineItemDTO;
    }

    /**
     * @param Checkout $checkout
     * @return array<FeeLineItemDTO>
     */
    public function getMessages(Checkout $checkout): array
    {
        $messages = [];
        $fees = $this->getFeeLineItems($checkout);
        foreach ($fees as $feeLineItemDTO) {
            if (!$feeLineItemDTO->hasMessage()) {
                continue;
            }
            $messages[] = $feeLineItemDTO;
        }

        return $messages;
    }

    public function getType(): string
    {
        return FeeProviderInterface::TYPE_LINE_ITEM;
    }

    public function setCheckoutLineItemFeeFactory(CheckoutLineItemFeeFactory $checkoutLineItemFeeFactory): void
    {
        $this->checkoutLineItemFeeFactory = $checkoutLineItemFeeFactory;
    }
}
