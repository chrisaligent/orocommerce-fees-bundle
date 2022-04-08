<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2019 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FeeProviderPass implements CompilerPassInterface
{
    const TAG = 'aligent_fees.fee_provider';
    const REGISTRY_SERVICE = 'aligent_fees.fee_provider_registry';
    const PRIORITY = 'priority';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::REGISTRY_SERVICE)) {
            return;
        }

        $taggedServices = $container->findTaggedServiceIds(self::TAG);

        if (empty($taggedServices)) {
            return;
        }

        $registryDefinition = $container->getDefinition(self::REGISTRY_SERVICE);

        // Sort by Priority (if set)
        $feeProviders = [];
        foreach ($taggedServices as $serviceId => $tags) {
            $priority = $tags[0][self::PRIORITY] ?? 0;
            $feeProviders[$priority][] = $serviceId;
        }
        ksort($feeProviders);

        foreach ($feeProviders as $priority => $definitions) {
            foreach ($definitions as $fee) {
                $registryDefinition->addMethodCall('addProvider', [new Reference($fee)]);
            }
        }
    }
}
