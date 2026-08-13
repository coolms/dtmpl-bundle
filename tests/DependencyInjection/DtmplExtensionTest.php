<?php

declare(strict_types=1);

namespace CoolMS\DtmplBundle\Tests\DependencyInjection;

use CoolMS\Dtmpl\DtmplEngine;
use CoolMS\Dtmpl\Lexer\Lexer;
use CoolMS\Dtmpl\Loader\CompositeTemplateLoader;
use CoolMS\Dtmpl\Loader\FallbackTemplateLoader;
use CoolMS\Dtmpl\Loader\FilesystemTemplateLoader;
use CoolMS\Dtmpl\Parser\Parser;
use CoolMS\Dtmpl\Runtime\FilterRegistry;
use CoolMS\Dtmpl\Widget\WidgetRegistry;
use CoolMS\Dtmpl\Widget\WidgetTemplateResolver;
use CoolMS\Dtmpl\Widget\WidgetTemplateResolverInterface;
use CoolMS\DtmplBundle\DependencyInjection\DtmplExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(DtmplExtension::class)]
final class DtmplExtensionTest extends TestCase
{
    /**
     * These classes used to become services for free, because they sat under
     * the application's own service glob. In a package they do not, and the
     * failure mode is a container that builds fine until something asks for the
     * engine. Each one is asserted rather than assumed.
     *
     * @return iterable<string, array{class-string}>
     */
    public static function expectedServices(): iterable
    {
        yield 'engine' => [DtmplEngine::class];
        yield 'filesystem loader' => [FilesystemTemplateLoader::class];
        yield 'fallback loader' => [FallbackTemplateLoader::class];
        yield 'composite loader' => [CompositeTemplateLoader::class];
        yield 'widget registry' => [WidgetRegistry::class];
        yield 'widget template resolver' => [WidgetTemplateResolver::class];
        yield 'lexer' => [Lexer::class];
        yield 'parser' => [Parser::class];
        yield 'filter registry' => [FilterRegistry::class];
    }

    /**
     * @param class-string $class
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('expectedServices')]
    public function testItRegistersTheEnginesServices(string $class): void
    {
        $container = $this->load();

        self::assertTrue($container->hasDefinition($class), $class . ' is not registered');
    }

    /**
     * Application code fetches the engine from the container in places rather
     * than injecting it; a private service would be inlined away and the fetch
     * would fail at runtime, not at build time.
     */
    public function testTheEngineIsPublic(): void
    {
        self::assertTrue($this->load()->getDefinition(DtmplEngine::class)->isPublic());
    }

    public function testTheWidgetTemplateResolverIsAliasedToItsInterface(): void
    {
        $container = $this->load();

        self::assertTrue($container->hasAlias(WidgetTemplateResolverInterface::class));
        self::assertSame(
            WidgetTemplateResolver::class,
            (string) $container->getAlias(WidgetTemplateResolverInterface::class),
        );
    }

    /**
     * The loader reads this path. It used to arrive through an #[Autowire]
     * attribute on the constructor, which the engine cannot carry now that it
     * has no dependency on the container -- so the Extension passes it as an
     * argument, and that hand-off is what this pins.
     */
    public function testTheFilesystemLoaderReceivesTheConfiguredBasePath(): void
    {
        $container = $this->load(['template_base_path' => '/srv/templates']);

        self::assertSame(
            '/srv/templates',
            $container->getDefinition(FilesystemTemplateLoader::class)->getArgument(0),
        );
        self::assertSame('/srv/templates', $container->getParameter('dtmpl.template_base_path'));
    }

    public function testWidgetTemplatesReachTheResolver(): void
    {
        $container = $this->load(['widget_templates' => ['comments' => 'partials/comments.html.dtmpl']]);

        self::assertSame(
            ['comments' => 'partials/comments.html.dtmpl'],
            $container->getDefinition(WidgetTemplateResolver::class)->getArgument(0),
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function load(array $config = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new DtmplExtension())->load([$config], $container);

        return $container;
    }
}
