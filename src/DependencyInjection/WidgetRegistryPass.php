<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle\DependencyInjection;

use CoolMS\Dtmpl\Widget\WidgetRegistry;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function class_exists;
use function is_string;

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
 *
 * !! Deferring alone still built EVERY renderer on the FIRST widget lookup,
 * because the registry could only learn a renderer's key by constructing it --
 * so one `{widget:...}` on one page constructed every widget renderer in the
 * application, and with them whatever each one's constructor pulls in. Measured
 * 2026-09-08: it is a large part of why a public page render touched 33 modules.
 * The key is therefore read HERE, at compile time, from the renderer's `KEY`
 * constant (or an explicit `key` on the tag), and baked into the compiled
 * container as a literal. Reflection reads a constant without instantiating,
 * and at runtime the container carries the string, so nothing reflects per
 * request. A renderer that declares neither still works -- it falls back to the
 * un-keyed path and is built on the first lookup that nothing keyed answers.
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
            $key = $this->declaredKey($container, $id, $tags);

            $definition->addMethodCall(
                null !== $key ? 'registerKeyed' : 'registerLazy',
                null !== $key
                    ? [$key, new ServiceClosureArgument(new Reference($id))]
                    : [new ServiceClosureArgument(new Reference($id))],
            );
        }
    }

    /**
     * The renderer's key, if it can be known without building the renderer.
     *
     * @param array<int, array<string, mixed>> $tags
     */
    private function declaredKey(ContainerBuilder $container, string $id, array $tags): ?string
    {
        foreach ($tags as $tag) {
            if (isset($tag['key']) && is_string($tag['key']) && '' !== $tag['key']) {
                return $tag['key'];
            }
        }

        $class = $container->getDefinition($id)->getClass();
        if (!is_string($class) || '' === $class) {
            return null;
        }

        // A class name may still carry a %parameter%; the tagged service id is
        // often the class name itself, which resolves the same way.
        $class = $container->getParameterBag()->resolveValue($class);
        if (!is_string($class) || !class_exists($class)) {
            return null;
        }

        $reflection = new ReflectionClass($class);
        if (!$reflection->hasConstant('KEY')) {
            return null;
        }

        $key = $reflection->getConstant('KEY');

        return is_string($key) && '' !== $key ? $key : null;
    }
}
