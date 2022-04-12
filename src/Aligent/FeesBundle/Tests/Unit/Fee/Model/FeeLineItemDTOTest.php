<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Tests\Unit\Fee\Model;

use Aligent\FeesBundle\Fee\Model\FeeLineItemDTO;
use Oro\Bundle\CurrencyBundle\Entity\Price;

class FeeLineItemDTOTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate(): void
    {
        $object = new FeeLineItemDTO();

        $this->assertNull($object->getProductUnitCode());
        $this->assertNull($object->getCurrency());
        $this->assertNull($object->getProductUnitCode());
        $this->assertNull($object->getAmount());
        $this->assertNull($object->getLabel());

        $object
            ->setProductSku('ABC123')
            ->setCurrency('USD')
            ->setProductUnitCode('each')
            ->setAmount(123.45)
            ->setLabel('Shipping Fee')
        ;

        $this->assertEquals('ABC123', $object->getProductSku());
        $this->assertEquals('USD', $object->getCurrency());
        $this->assertEquals('each', $object->getProductUnitCode());
        $this->assertEquals(123.45, $object->getAmount());
        $this->assertEquals('Shipping Fee', $object->getLabel());

        $this->assertInstanceOf(Price::class, $object->getPrice());
        $price = $object->getPrice();
        $this->assertEquals(123.45, $price->getValue());
        $this->assertEquals('USD', $price->getCurrency());
    }

    public function testPriceIsOnlyCreatedWithValidData(): void
    {
        // No Amount or Currency
        $object = new FeeLineItemDTO();
        $this->assertNull($object->getPrice());

        // Only Amount, no Currency
        $object = new FeeLineItemDTO();
        $object->setAmount(123.45);
        $this->assertNull($object->getPrice());

        // Only Currency, no Amount
        $object = new FeeLineItemDTO();
        $object->setCurrency('USD');
        $this->assertNull($object->getPrice());

        // Both Currency and Amount
        $object = new FeeLineItemDTO();
        $object->setCurrency('USD');
        $object->setAmount(123.45);
        $this->assertInstanceOf(Price::class, $object->getPrice());
        $this->assertEquals('USD', $object->getPrice()->getCurrency());
        $this->assertEquals(123.45, $object->getPrice()->getValue());
    }
}
