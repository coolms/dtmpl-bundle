<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle\DependencyInjection;

use CoolMS\Dtmpl\Widget\WidgetRegistry;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects all services tagged 'dtmpl.widget' and registers them into WidgetRegistry.
 *
 * Each widget renderer is registered as a {@see \Closure} (ServiceClosureArgument)
 * rather than a direct Reference so the renderer's instantiation is deferred until
 * the first {@see WidgetRegistry::has()}/{@see WidgetRegistry::get()} call.
 *
 * This breaks a service-graph cycle latent in the wiring: DtmplEngine's compiled
 * factory function had to resolve every widget renderer's constructor argument
 * (incl. DocumentWidgetRenderer -> DocumentFormatProviderRegistry -> WordFormatProvider
 * -> DtmplEngine) BEFORE storing the DtmplEngine instance in $container->privates.
 * The early-return cycle-detection guards Symfony emits per arg only fire AFTER
 * the instance is stored, so the second entry to getDtmplEngineService::do
 * restarted from scratch, looping until OOM (1.5M frames / 10GB RSS).
 *
 * Deferring renderer construction via ServiceClosureArgument means the DtmplEngine
 * compiled factory never resolves DocumentFormatProviderRegistry inline; the cycle
 * is broken at the engine boundary.
 */
class WidgetRegistryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(WidgetRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(WidgetRegistry::class);

        foreach ($container->findTaggedServiceIds('dtmpl.widget') as $id => $tags) {
            $definition->addMethodCall('registerLazy', [
                new ServiceClosureArgument(new Reference($id)),
            ]);
        }
    }
}
