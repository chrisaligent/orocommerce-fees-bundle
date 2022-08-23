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

use Oro\Bundle\CurrencyBundle\Entity\Price;

class FeeLineItemDTO
{
    protected ?float $amount = null;
    protected ?string $currency = null;
    protected ?string $productSku = null;
    protected ?string $productUnitCode = null;
    protected ?string $label = null;
    protected ?string $message = null;

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getProductSku(): ?string
    {
        return $this->productSku;
    }

    public function setProductSku(string $productSku): static
    {
        $this->productSku = $productSku;
        return $this;
    }

    public function getProductUnitCode(): ?string
    {
        return $this->productUnitCode;
    }

    public function setProductUnitCode(string $productUnitCode): static
    {
        $this->productUnitCode = $productUnitCode;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getPrice(): ?Price
    {
        if (!$this->getCurrency() || !$this->getAmount()) {
            return null;
        }

        return Price::create(
            (string)$this->getAmount(),
            $this->getCurrency()
        );
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function hasMessage(): bool
    {
        return (bool)$this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }
}
