<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2019 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Fee\Provider;

use LogicException;

class FeeProviderRegistry
{
    /**
     * @var array<FeeProviderInterface>
     */
    protected array $providers = [];

    /**
     * @var array<string,array<FeeProviderInterface>>
     */
    protected array $providersByType = [];

    /**
     * Add fee to the registry
     */
    public function addProvider(FeeProviderInterface $provider): void
    {
        if (array_key_exists($provider->getName(), $this->providers)) {
            throw new LogicException(
                sprintf('Fee with name "%s" already registered', $provider->getName())
            );
        }

        $this->providers[$provider->getName()] = $provider;
        $this->providersByType[$provider->getType()][$provider->getName()] = $provider;
    }

    /**
     * @return array<FeeProviderInterface>
     */
    public function getProviders(?string $type = null): array
    {
        if ($type) {
            return $this->providersByType[$type] ?? [];
        }

        return $this->providers;
    }

    /**
     * Get fee by name
     * @throws LogicException Throw exception when provider with specified name not found
     */
    public function getProvider(string $name): FeeProviderInterface
    {
        if (!array_key_exists($name, $this->providers)) {
            throw new LogicException(
                sprintf('Fee with name "%s" does not exist', $name)
            );
        }

        return $this->providers[$name];
    }
}
