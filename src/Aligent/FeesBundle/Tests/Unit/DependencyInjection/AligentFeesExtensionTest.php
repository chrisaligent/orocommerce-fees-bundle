<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Tests\Unit\DependencyInjection;

use Aligent\FeesBundle\ContextHandler\FreeFormAwareTaxOrderLineItemHandler;
use Aligent\FeesBundle\DependencyInjection\AligentFeesExtension;
use Aligent\FeesBundle\EventListener\CreateCheckoutListener;
use Aligent\FeesBundle\Fee\Factory\CheckoutLineItemFeeFactory;
use Aligent\FeesBundle\Fee\Provider\AbstractLineItemFeeProvider;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class AligentFeesExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new AligentFeesExtension());

        // Services
        $expectedDefinitions = [
            'aligent_fees.fee_provider_registry',
            CheckoutLineItemFeeFactory::class,
            AbstractLineItemFeeProvider::class,
            CreateCheckoutListener::class,
            FreeFormAwareTaxOrderLineItemHandler::class,
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $expectedExtensionConfigs = ['aligent_fees'];
        $this->assertExtensionConfigsLoaded($expectedExtensionConfigs);
    }
}
