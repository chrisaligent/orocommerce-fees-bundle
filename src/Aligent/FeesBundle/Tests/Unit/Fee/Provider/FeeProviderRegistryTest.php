<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Tests\Unit\Fee\Provider;

use Aligent\FeesBundle\Fee\Provider\FeeProviderInterface;
use Aligent\FeesBundle\Fee\Provider\FeeProviderRegistry;

class FeeProviderRegistryTest extends \PHPUnit\Framework\TestCase
{
    public function testProvidersCanBeAddedAndAccessed(): void
    {
        /**
         * Mock two Fees
         */
        $feeProvider1 = $this->getMockBuilder(FeeProviderInterface::class)
            ->onlyMethods(['getName', 'getType'])
            ->getMockForAbstractClass();
        $feeProvider1->expects($this->any())->method('getName')->willReturn('fee_1');
        $feeProvider1->expects($this->any())->method('getType')
            ->willReturn(FeeProviderInterface::TYPE_LINE_ITEM);

        $feeProvider2 = $this->getMockBuilder(FeeProviderInterface::class)
            ->onlyMethods(['getName'])
            ->getMockForAbstractClass();
        $feeProvider2->expects($this->any())->method('getName')->willReturn('fee_2');
        $feeProvider2->expects($this->any())->method('getType')
            ->willReturn(FeeProviderInterface::TYPE_LINE_ITEM);


        $registry = new FeeProviderRegistry();

        // Confirm no fees yet
        $this->assertEmpty($registry->getProviders());

        // Accessing a non-existent Fee should result in an exception
        $this->expectExceptionMessage('Fee with name "fee_1" does not exist');
        $registry->getProvider('fee_1');

        // Add both fees
        $registry->addProvider($feeProvider1);
        $registry->addProvider($feeProvider2);

        // Expect two Fee Providers returned
        $this->assertCount(2, $registry->getProviders());
        $this->assertContainsOnlyInstancesOf(FeeProviderInterface::class, $registry->getProviders());

        // Confirm we can also retrieve them individually by name
        $this->assertSame($feeProvider1, $registry->getProvider('fee_1'));
        $this->assertSame($feeProvider2, $registry->getProvider('fee_2'));
    }

    public function testFeesCannotBeRegisteredTwice(): void
    {
        $feeProvider = $this->getMockBuilder(FeeProviderInterface::class)
            ->onlyMethods(['getName', 'getType'])
            ->getMockForAbstractClass();
        $feeProvider->expects($this->any())->method('getName')->willReturn('handling_fee');
        $feeProvider->expects($this->any())->method('getType')
            ->willReturn(FeeProviderInterface::TYPE_LINE_ITEM);

        $registry = new FeeProviderRegistry();

        // Add fee once
        $registry->addProvider($feeProvider);

        // Add same fee a second time, expect an exception
        $this->expectExceptionMessage('Fee with name "handling_fee" already registered');
        $registry->addProvider($feeProvider);
    }
}
