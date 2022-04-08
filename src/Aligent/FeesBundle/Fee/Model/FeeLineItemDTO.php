<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Fee\Model;

class FeeLineItemDTO
{
    protected float $amount;
    protected string $currency;
    protected string $productSku;
    protected string $productUnitCode;
    protected string $label;

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): FeeLineItemDTO
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): FeeLineItemDTO
    {
        $this->currency = $currency;
        return $this;
    }

    public function getProductSku(): ?string
    {
        return $this->productSku;
    }

    public function setProductSku(string $productSku): FeeLineItemDTO
    {
        $this->productSku = $productSku;
        return $this;
    }

    public function getProductUnitCode(): ?string
    {
        return $this->productUnitCode;
    }

    public function setProductUnitCode(string $productUnitCode): FeeLineItemDTO
    {
        $this->productUnitCode = $productUnitCode;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): FeeLineItemDTO
    {
        $this->label = $label;
        return $this;
    }
}
