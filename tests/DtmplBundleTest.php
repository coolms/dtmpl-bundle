<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle\Tests;

use CoolMS\Dtmpl\TemplateLoaderInterface;
use CoolMS\DtmplBundle\DependencyInjection\ConstantProviderPass;
use CoolMS\DtmplBundle\DependencyInjection\DtmplExtension;
use CoolMS\DtmplBundle\DependencyInjection\LoaderChainPass;
use CoolMS\DtmplBundle\DependencyInjection\WidgetRegistryPass;
use CoolMS\DtmplBundle\DtmplBundle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

#[CoversClass(DtmplBundle::class)]
final class DtmplBundleTest extends TestCase
{
    public function testItExposesTheExtensionUnderTheExpectedAlias(): void
    {
        $extension = new DtmplBundle()->getContainerExtension();

        self::assertInstanceOf(DtmplExtension::class, $extension);
        // config/packages/dtmpl.yaml is keyed on this. A mismatch makes Symfony
        // report the file as configuring an unknown extension.
        self::assertSame('dtmpl', $extension->getAlias());
    }

    /**
     * The engine is publishable on its own because nothing in it knows about
     * the framework. This bundle is where that knowledge is allowed to live, so
     * it extends Symfony's Bundle directly rather than any application base
     * class -- which is what the original could not do.
     */
    public function testItExtendsSymfonysBundleAndNothingElse(): void
    {
        self::assertInstanceOf(Bundle::class, new DtmplBundle());
        self::assertSame(Bundle::class, get_parent_class(DtmplBundle::class));
    }

    public function testItAutoconfiguresTemplateLoaders(): void
    {
        $container = new ContainerBuilder();
        new DtmplBundle()->build($container);

        $autoconfigured = $container->getAutoconfiguredInstanceof();
        self::assertArrayHasKey(TemplateLoaderInterface::class, $autoconfigured);
        self::assertArrayHasKey(
            'dtmpl.template_loader',
            $autoconfigured[TemplateLoaderInterface::class]->getTags(),
        );
    }

    public function testItRegistersAllThreeCompilerPasses(): void
    {
        $container = new ContainerBuilder();
        new DtmplBundle()->build($container);

        $passes = $container->getCompiler()->getPassConfig()->getPasses();
        foreach ([LoaderChainPass::class, WidgetRegistryPass::class, ConstantProviderPass::class] as $class) {
            $found = array_filter($passes, static fn (object $p): bool => $p instanceof $class);
            self::assertCount(1, $found, $class . ' is not registered exactly once');
        }
    }
}
