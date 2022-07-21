<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\OrderBundle\Entity\Order;

class OrderStub extends Order
{
    protected ?float $processing_fee = null;

    public function getProcessingFee(): ?float
    {
        return $this->processing_fee;
    }

    public function setProcessingFee(?float $value): static
    {
        $this->processing_fee = $value;
        return $this;
    }
}
