<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle\DependencyInjection;

use CoolMS\Dtmpl\DtmplEngine;
use CoolMS\Dtmpl\Lexer\KeywordRegistry;
use CoolMS\Dtmpl\Lexer\Lexer;
use CoolMS\Dtmpl\Loader\CompositeTemplateLoader;
use CoolMS\Dtmpl\Loader\FallbackTemplateLoader;
use CoolMS\Dtmpl\Loader\FilesystemTemplateLoader;
use CoolMS\Dtmpl\Optimizer\WhitespaceTrimmer;
use CoolMS\Dtmpl\Parser\Parser;
use CoolMS\Dtmpl\Runtime\EntityWrapperFactory;
use CoolMS\Dtmpl\Runtime\FilterRegistry;
use CoolMS\Dtmpl\Runtime\ConstantProviderInterface;
use CoolMS\Dtmpl\ValueObject\TemplateExtensionMap;
use CoolMS\Dtmpl\Widget\WidgetRegistry;
use CoolMS\Dtmpl\Widget\WidgetRendererInterface;
use CoolMS\Dtmpl\Widget\WidgetTemplateResolver;
use CoolMS\Dtmpl\Widget\WidgetTemplateResolverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * Template Extension.
 *
 * Loads template configuration and registers services
 */
class DtmplExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('dtmpl.template_base_path', $config['template_base_path']);

        // Registered here rather than scanned, and wired by argument rather than
        // by an #[Autowire] attribute on the constructor. The engine package has
        // no dependency on the DI container, so it cannot carry that attribute;
        // and configurable wiring belongs in the Extension where it can be read.
        $container
            ->register(FilesystemTemplateLoader::class, FilesystemTemplateLoader::class)
            ->setArguments([$config['template_base_path']])
            ->setAutowired(false)
            ->setAutoconfigured(true)
            ->setPublic(false);

        // Register TemplateExtensionMap service with configuration
        $container
            ->register(TemplateExtensionMap::class, TemplateExtensionMap::class)
            ->setAutowired(false)
            ->setAutoconfigured(false)
            ->setFactory([TemplateExtensionMap::class, 'fromConfig'])
            ->setArguments([$config['extensions']])
            ->setPublic(true);

        // Register CompositeTemplateLoader with autowire:false as a first-pass defense.
        // services.yaml auto-scan will override this with autowire:true -- which would cause
        // a compile-time error because the array $loaders parameter cannot be autowired.
        // LoaderChainPass runs last and re-asserts setAutowired(false) + injects $loaders.
        $container
            ->register(CompositeTemplateLoader::class, CompositeTemplateLoader::class)
            ->setAutowired(false)
            ->setAutoconfigured(false);

        // WidgetRegistry -- mutable, populated by WidgetRegistryPass.
        $container
            ->register(WidgetRegistry::class, WidgetRegistry::class)
            ->setAutowired(false)
            ->setAutoconfigured(false)
            ->setPublic(false);

        // WidgetTemplateResolver -- the central widget→partial config map
        // (`dtmpl.widget_templates`); Domain services are wired here, not scanned.
        $container
            ->register(WidgetTemplateResolver::class, WidgetTemplateResolver::class)
            ->setArguments([$config['widget_templates'] ?? []])
            ->setAutowired(false)
            ->setAutoconfigured(false)
            ->setPublic(false);
        $container->setAlias(WidgetTemplateResolverInterface::class, WidgetTemplateResolver::class);

        // Auto-tag all WidgetRendererInterface implementations with 'dtmpl.widget'.
        $container->registerForAutoconfiguration(WidgetRendererInterface::class)
            ->addTag('dtmpl.widget');

        // Auto-tag all ConstantProviderInterface implementations.
        $container->registerForAutoconfiguration(ConstantProviderInterface::class)
            ->addTag('coolms.dtmpl.constant_provider');

        // The engine's own services. These used to arrive for free because the
        // classes sat under the application's `App\` glob; they live in a
        // package now, so the bundle has to register them. Every constructor
        // here is autowirable: scalars carry defaults, and the only object
        // dependencies are the property accessor and the loader chain.
        //
        // Registered by class name with no alias, because that is what callers
        // already type-hint.
        foreach ([
            DtmplEngine::class,
            FallbackTemplateLoader::class,
            EntityWrapperFactory::class,
            Lexer::class,
            KeywordRegistry::class,
            Parser::class,
            FilterRegistry::class,
            WhitespaceTrimmer::class,
        ] as $class) {
            $container
                ->register($class, $class)
                ->setAutowired(true)
                ->setAutoconfigured(true);
        }

        // Public: application code fetches the engine from the container in a
        // few places rather than injecting it, and a private service would be
        // inlined away.
        $container->getDefinition(DtmplEngine::class)->setPublic(true);
    }

    public function getAlias(): string
    {
        return 'dtmpl';
    }
}
