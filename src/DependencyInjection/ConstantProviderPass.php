<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle\DependencyInjection;

use CoolMS\Dtmpl\DtmplEngine;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all services tagged 'coolms.dtmpl.constant_provider' and
 * injects them into DtmplEngine via setConstantProviders().
 */
final class ConstantProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(DtmplEngine::class)) {
            return;
        }

        $definition = $container->findDefinition(DtmplEngine::class);

        $providers = [];
        foreach (array_keys($container->findTaggedServiceIds('coolms.dtmpl.constant_provider')) as $id) {
            $providers[] = new Reference($id);
        }

        if ([] !== $providers) {
            $definition->addMethodCall('setConstantProviders', [$providers]);
        }
    }
}
