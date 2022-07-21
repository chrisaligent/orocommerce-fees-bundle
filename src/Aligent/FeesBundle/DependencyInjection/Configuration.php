<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2019 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const ROOT_NODE = 'aligent_fees';
    const DEFAULT_PRODUCT_TAX_CODE = 'default_product_tax_code';

    const PROCESSING_FEE_ENABLED = 'processing_fee_enabled';
    const PROCESSING_FEE_PAYMENT_METHODS = 'processing_fee_payment_methods';

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NODE);

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                self::DEFAULT_PRODUCT_TAX_CODE => ['type' => 'string', 'value' => null],

                self::PROCESSING_FEE_ENABLED => ['type' => 'boolean', 'value' => false],
                self::PROCESSING_FEE_PAYMENT_METHODS => ['type' => 'array', 'value' => []],
            ]
        );

        return $treeBuilder;
    }

    public static function getConfigKeyByName(string $name): string
    {
        return sprintf(self::ROOT_NODE . '%s%s', ConfigManager::SECTION_MODEL_SEPARATOR, $name);
    }
}
