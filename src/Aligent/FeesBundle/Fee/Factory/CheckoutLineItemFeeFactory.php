<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Fee\Factory;

use Aligent\FeesBundle\Fee\Model\FeeLineItemDTO;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository;

class CheckoutLineItemFeeFactory
{
    protected ProductUnitRepository $productUnitRepository;

    /**
     * @var array<string,ProductUnit>
     */
    protected array $productUnits = [];

    public function __construct(ProductUnitRepository $productUnitRepository)
    {
        $this->productUnitRepository = $productUnitRepository;
    }

    /**
     * @param array<FeeLineItemDTO> $feeLineItemDTOs
     * @return array<CheckoutLineItem>
     */
    public function createMultiple(array $feeLineItemDTOs): array
    {
        $lineItems = [];
        foreach ($feeLineItemDTOs as $feeLineItemDTO) {
            $lineItems[] = $this->create($feeLineItemDTO);
        }
        return $lineItems;
    }

    public function create(FeeLineItemDTO $feeLineItemDTO): CheckoutLineItem
    {
        /** @var ProductUnit $productUnit */
        $productUnit = $this->getProductUnit($feeLineItemDTO->getProductUnitCode());

        $price = $feeLineItemDTO->getPrice();

        $lineItem = new CheckoutLineItem();
        $lineItem
            ->setQuantity(1.0) // Quantity is 1 for fees, requires float
            ->setPrice($price)
            ->setValue($price->getValue())
            ->setPriceFixed(true) // Without this, Cart to Checkout conversion will fail
            ->setCurrency($price->getCurrency())
            ->setFreeFormProduct($feeLineItemDTO->getLabel()) // Must be a Free Form product
            ->setProductSku($feeLineItemDTO->getProductSku())
            ->setProductUnit($productUnit)
            ->setProductUnitCode($productUnit->getCode())
        ;

        return $lineItem;
    }

    protected function getProductUnit(string $code): ProductUnit
    {
        if (!array_key_exists($code, $this->productUnits)) {
            /** @var ProductUnit|null $productUnit */
            $productUnit = $this->productUnitRepository->findOneBy(['code' => $code]);
            if (!$productUnit) {
                throw new \InvalidArgumentException(sprintf("Invalid Product Unit Code %s", $code));
            }

            $this->productUnits[$code] = $productUnit;
        }

        return $this->productUnits[$code];
    }
}
