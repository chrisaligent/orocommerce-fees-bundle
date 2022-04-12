<?php
/**
 * @category  Aligent
 * @package
 * @author    Chris Rossi <chris.rossi@aligent.com.au>
 * @copyright 2022 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */
namespace Aligent\FeesBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Aligent\FeesBundle\DependencyInjection\CompilerPass\FeeProviderPass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FeeProviderPassTest extends \PHPUnit\Framework\TestCase
{
    protected CompilerPassInterface $compiler;

    protected function setUp(): void
    {
        $this->compiler = new FeeProviderPass();
    }

    public function testProcessNoFeesRegistered(): void
    {
        $container = new ContainerBuilder();

        $this->compiler->process($container);
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $feeRegistry = $container->register('aligent_fees.fee_provider_registry');

        $container->register('provider_fee_1')
            ->addTag('aligent_fees.fee_provider', ['alias' => 'fee_1']);
        $container->register('provider_fee_2')
            ->addTag('aligent_fees.fee_provider', ['alias' => 'fee_2']);

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addProvider', [new Reference('provider_fee_1')]],
                ['addProvider', [new Reference('provider_fee_2')]]
            ],
            $feeRegistry->getMethodCalls()
        );
    }

    public function testProcessNoTagged(): void
    {
        $container = new ContainerBuilder();
        $feeRegistry = $container->register('aligent_fees.fee_provider_registry');

        $this->compiler->process($container);

        self::assertSame([], $feeRegistry->getMethodCalls());
    }

    public function testProcessWithPriority(): void
    {
        $container = new ContainerBuilder();
        $feeRegistry = $container->register('aligent_fees.fee_provider_registry');

        /**
         * Register three fees with varying priorities.
         * Expect lowest to highest.
         */
        $container->register('provider_fee_1')
            ->addTag('aligent_fees.fee_provider', ['priority' => 100]);
        $container->register('provider_fee_2')
            ->addTag('aligent_fees.fee_provider', ['priority' => 0]);
        $container->register('provider_fee_3')
            ->addTag('aligent_fees.fee_provider', ['priority' => 1]);

        $this->compiler->process($container);

        self::assertEquals(
            [
                ['addProvider', [new Reference('provider_fee_2')]],
                ['addProvider', [new Reference('provider_fee_3')]],
                ['addProvider', [new Reference('provider_fee_1')]],
            ],
            $feeRegistry->getMethodCalls()
        );
    }
}
