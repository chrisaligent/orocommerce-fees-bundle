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
     * Creates one or more Checkout Line Item for Fee.
     * Fee optionally based on Checkout instance
     * @return array<CheckoutLineItem>
     */
    public function getCheckoutLineItems(Checkout $checkout): array
    {
        return $this
            ->checkoutLineItemFeeFactory
            ->createMultiple($this->buildFees($checkout));
    }

    /**
     * @return array<FeeLineItemDTO>
     */
    abstract public function buildFees(Checkout $checkout): array;

    public function getType(): string
    {
        return FeeProviderInterface::TYPE_LINE_ITEM;
    }
}
