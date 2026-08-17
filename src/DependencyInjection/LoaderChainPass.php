<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle\DependencyInjection;

use CoolMS\Dtmpl\Loader\CompositeTemplateLoader;
use CoolMS\Dtmpl\Loader\FallbackTemplateLoader;
use CoolMS\Dtmpl\TemplateLoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Collects services tagged dtmpl.template_loader, sorts by priority DESC,
 * and injects them into CompositeTemplateLoader; pins FallbackTemplateLoader last.
 * Restores explicit wiring on CompositeTemplateLoader after auto-scan overrides it.
 * Aliases TemplateLoaderInterface to CompositeTemplateLoader.
 */
class LoaderChainPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(CompositeTemplateLoader::class)) {
            return;
        }

        $tagged = $container->findTaggedServiceIds('dtmpl.template_loader');

        $compositeId = CompositeTemplateLoader::class;

        $loaders = [];
        foreach ($tagged as $serviceId => $tags) {
            if ($serviceId === $compositeId) {
                continue; // never inject CompositeTemplateLoader into itself
            }

            // FallbackTemplateLoader is handled separately below -- it is always
            // pinned at the very end of the chain regardless of its priority tag,
            // and it must NOT circulate through the priority-sort pool.
            if (FallbackTemplateLoader::class === $serviceId) {
                continue;
            }

            // $tags[0] is always the class-specific (highest-specificity) tag entry.
            $priority = (int) ($tags[0]['priority'] ?? 0);
            $loaders[$priority][] = new Reference($serviceId);
        }

        // Sort DESC -- highest priority loader is tried first
        krsort($loaders);
        $sorted = array_merge(...([] !== $loaders ? array_values($loaders) : [[]]));

        // FallbackTemplateLoader is always last -- pinned here regardless of its priority tag.
        if ($container->has(FallbackTemplateLoader::class)) {
            $sorted[] = new Reference(FallbackTemplateLoader::class);
        }

        // Restore explicit wiring -- auto-scan clears the un-autowirable array $loaders.
        $container->findDefinition(CompositeTemplateLoader::class)
            ->setAutowired(false)
            ->setAutoconfigured(false)
            ->setArgument('$loaders', $sorted);

        // Alias TemplateLoaderInterface to CompositeTemplateLoader.
        if (!$container->hasAlias(TemplateLoaderInterface::class)) {
            $container->setAlias(TemplateLoaderInterface::class, CompositeTemplateLoader::class)
                ->setPublic(false);
        }
    }
}
