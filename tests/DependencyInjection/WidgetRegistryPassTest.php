<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle\Tests\DependencyInjection;

use CoolMS\Dtmpl\Widget\WidgetRegistry;
use CoolMS\DtmplBundle\DependencyInjection\WidgetRegistryPass;
use CoolMS\DtmplBundle\Tests\Fixtures\KeyedRendererStub;
use CoolMS\DtmplBundle\Tests\Fixtures\UnkeyedRendererStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The pass has to learn each renderer's key WITHOUT building it, which is the
 * only reason the registry can then build one renderer instead of all of them.
 * Reading a class constant by reflection does that; reading `$renderer->key`
 * does not, because the property is on an instance.
 */
#[CoversClass(WidgetRegistryPass::class)]
final class WidgetRegistryPassTest extends TestCase
{
    public function testARendererDeclaringAKeyConstantIsRegisteredUnderIt(): void
    {
        $container = $this->containerWith(['widget.keyed' => KeyedRendererStub::class]);

        new WidgetRegistryPass()->process($container);

        $calls = $container->findDefinition(WidgetRegistry::class)->getMethodCalls();
        self::assertCount(1, $calls);
        self::assertSame('registerKeyed', $calls[0][0]);
        self::assertSame(KeyedRendererStub::KEY, $calls[0][1][0]);
        self::assertInstanceOf(ServiceClosureArgument::class, $calls[0][1][1]);
    }

    public function testARendererWithoutAKeyConstantKeepsTheUnkeyedPath(): void
    {
        $container = $this->containerWith(['widget.plain' => UnkeyedRendererStub::class]);

        new WidgetRegistryPass()->process($container);

        $calls = $container->findDefinition(WidgetRegistry::class)->getMethodCalls();
        self::assertCount(1, $calls);
        self::assertSame('registerLazy', $calls[0][0]);
        self::assertInstanceOf(ServiceClosureArgument::class, $calls[0][1][0]);
    }

    public function testAnExplicitKeyOnTheTagWinsOverTheConstant(): void
    {
        $container = $this->containerWith([]);
        $container->register('widget.tagged', KeyedRendererStub::class)
            ->addTag('dtmpl.widget', ['key' => 'from:tag']);

        new WidgetRegistryPass()->process($container);

        $calls = $container->findDefinition(WidgetRegistry::class)->getMethodCalls();
        self::assertSame('registerKeyed', $calls[0][0]);
        self::assertSame('from:tag', $calls[0][1][0]);
    }

    public function testAServiceWithNoResolvableClassFallsBackRatherThanFailing(): void
    {
        $container = $this->containerWith([]);
        // A definition whose class cannot be reflected -- e.g. one produced by a
        // factory. The pass must not fail the compile over it.
        $container->register('widget.factory_built', 'CoolMS\\Nope\\DoesNotExist')
            ->addTag('dtmpl.widget');

        new WidgetRegistryPass()->process($container);

        $calls = $container->findDefinition(WidgetRegistry::class)->getMethodCalls();
        self::assertSame('registerLazy', $calls[0][0]);
    }

    public function testThePassDoesNothingWithoutTheRegistry(): void
    {
        $container = new ContainerBuilder();
        $container->register('widget.keyed', KeyedRendererStub::class)->addTag('dtmpl.widget');

        new WidgetRegistryPass()->process($container);

        self::assertFalse($container->has(WidgetRegistry::class));
    }

    /** @param array<string, class-string> $renderers service id => class */
    private function containerWith(array $renderers): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register(WidgetRegistry::class, WidgetRegistry::class);
        foreach ($renderers as $id => $class) {
            $container->register($id, $class)->addTag('dtmpl.widget');
        }

        return $container;
    }
}
