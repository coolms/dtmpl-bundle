<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle;

use CoolMS\Dtmpl\TemplateLoaderInterface;
use CoolMS\DtmplBundle\DependencyInjection\ConstantProviderPass;
use CoolMS\DtmplBundle\DependencyInjection\DtmplExtension;
use CoolMS\DtmplBundle\DependencyInjection\LoaderChainPass;
use CoolMS\DtmplBundle\DependencyInjection\WidgetRegistryPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Wires the DTMPL template engine into a Symfony application.
 *
 * Every TemplateLoaderInterface implementation is tagged
 * `dtmpl.template_loader` as a catch-all. A bundle that owns a loader and
 * wants a specific priority calls registerForAutoconfiguration on its own
 * concrete class in its own build().
 */
final class DtmplBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(TemplateLoaderInterface::class)
            ->addTag('dtmpl.template_loader');

        $container->addCompilerPass(new LoaderChainPass());
        $container->addCompilerPass(new WidgetRegistryPass());
        $container->addCompilerPass(new ConstantProviderPass());
    }

    public function getContainerExtension(): DtmplExtension
    {
        return new DtmplExtension();
    }
}
