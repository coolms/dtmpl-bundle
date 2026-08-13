# coolms/dtmpl-bundle

[![CI](https://github.com/coolms/dtmpl-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/coolms/dtmpl-bundle/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/coolms/dtmpl-bundle)](https://packagist.org/packages/coolms/dtmpl-bundle)
[![PHP](https://img.shields.io/badge/php-%E2%89%A5%208.5-777bb4)](https://www.php.net/releases/8.5/en.php)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

**Symfony integration for [`coolms/dtmpl`](https://github.com/coolms/dtmpl).**
Registers the engine, assembles the loader chain, collects widgets and constant
providers.

The engine is a separate package on purpose: it runs without an HTTP kernel or
a DI container, and keeping this bundle out of it is what preserves that. If you
are not on Symfony, install `coolms/dtmpl` alone and wire it yourself.

## Installation

```bash
composer require coolms/dtmpl-bundle
```

```php
// config/bundles.php
CoolMS\DtmplBundle\DtmplBundle::class => ['all' => true],
```

## Configuration

```yaml
# config/packages/dtmpl.yaml
dtmpl:
    template_base_path: '%kernel.project_dir%/templates'
    extensions:
        html: 'html.dtmpl'
    widget_templates:
        comments: 'partials/widgets/comments.html.dtmpl'
```

| Key | Purpose |
|---|---|
| `template_base_path` | root the filesystem loader resolves against |
| `extensions` | logical name to file extension map |
| `widget_templates` | which partial each widget renders through |

`widget_templates` is the seam that keeps widget markup out of PHP: a theme
repoints a widget at a different partial without touching the renderer.

## What gets registered

| Service | Notes |
|---|---|
| `DtmplEngine` | public; the entry point |
| `CompositeTemplateLoader` | aliased to `TemplateLoaderInterface` |
| `FilesystemTemplateLoader` | wired with `template_base_path` |
| `WidgetRegistry` | populated by the widget pass |
| `WidgetTemplateResolver` | aliased to its interface |
| `Lexer`, `Parser`, `FilterRegistry`, ... | the engine's internals |

## Extension points

Implement an interface; the bundle tags it.

| Interface | Tag | Effect |
|---|---|---|
| `WidgetRendererInterface` | `dtmpl.widget` | adds `{widget:yourKey}` |
| `TemplateLoaderInterface` | `dtmpl.template_loader` | joins the loader chain |
| `ConstantProviderInterface` | `coolms.dtmpl.constant_provider` | adds `{const:YOURS}` |

Loaders sort by `getPriority()` descending, with `FallbackTemplateLoader` pinned
last. To fix a priority for your own loader class, call
`registerForAutoconfiguration` on the concrete class in your bundle's `build()`.

## A note on the compiler passes

The loader chain, the widget registry and the constant providers are wired by
compiler passes rather than by extension arguments. That is not stylistic: an
application whose `App\` glob re-registers a service would silently discard
arguments an extension had set, because the glob loads after bundle extensions.
A pass runs once every definition exists, so the wiring survives.

## License

MIT © Dmitry Popov
