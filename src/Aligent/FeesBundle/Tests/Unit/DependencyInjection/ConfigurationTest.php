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

use Aligent\FeesBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function testGetConfigTreeBuilder(): void
    {
        $configuration = new Configuration();
        $builder = $configuration->getConfigTreeBuilder();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\Builder\TreeBuilder', $builder);

        $root = $builder->buildTree();
        $this->assertInstanceOf('Symfony\Component\Config\Definition\ArrayNode', $root);
        $this->assertEquals('aligent_fees', $root->getName());
    }

    public function testProcessConfiguration(): void
    {
        $configuration = new Configuration();
        $processor     = new Processor();

        $expected =  [
            'settings' => [
                'resolved' => true,
                'default_product_tax_code' => [
                    'value' => null,
                    'scope' => 'app'
                ],
            ]
        ];

        $this->assertEquals($expected, $processor->processConfiguration($configuration, []));
    }

    public function testGetConfigKeyByName(): void
    {
        $configKey = Configuration::getConfigKeyByName('default_product_tax_code');
        $this->assertEquals('aligent_fees.default_product_tax_code', $configKey);
    }
}
