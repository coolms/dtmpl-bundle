# Changelog

All notable changes to `coolms/dtmpl-bundle` are recorded here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning is described in `CONTRIBUTING.md` -- read it before assuming what a
major number means here.

!! Entries dated before 2026-09-01 were **reconstructed** from tags and commit
history when this file was created. Every entry after that is written in the
same commit as the change it describes.

## 2.0.0-alpha1 - 2026-09-09

**A pre-release, and it replaces the withdrawn 2.0.0** -- see the note under
that heading below. It carries no compatibility promise, which is what lets the
namespace move here at all.

Composer will not install it under default stability. Set

```json
"minimum-stability": "alpha",
"prefer-stable": true
```

in your root `composer.json`, then:

```
composer require coolms/dtmpl-bundle:^2.0@alpha
```

### Changed

!! **The namespace nests under the domain root: `CoolMS\DtmplBundle\` becomes
`CoolMS\Dtmpl\Bundle\`.** Update `config/bundles.php`, which names the bundle
class directly -- an entry left unchanged there is a fatal at boot, not a
deprecation. Nothing else moved: same services, same registrations, same
requires.

Every layer in a family now carries a namespace segment equal to its package
suffix, and the domain package keeps the root prefix. `coolms/dtmpl` is
unaffected by this.

- Each widget renderer's key is read at compile time instead of by
  instantiating the renderer to ask it.
- Comments, docblocks and changelogs are ascii.
- Development-only files are export-ignored, so `composer require` no longer
  downloads them.

### Added

- A test that asserts every imported sibling class exists in the installed
  tree, and reports every file that imports an absent one rather than stopping
  at the first. A constraint floor lower than what this code needs surfaces
  here rather than in an application.
- `CONTRIBUTING.md`, describing the release train, the deprecation window and
  how this package's version number relates to the platform packages.

## 2.0.0 - 2026-08-26

!! **Withdrawn on 2026-09-09.** The tag was deleted so this package could join
the platform's 2.0.0-alpha series instead of sitting at a stable number while
the generation's namespaces were still moving. The entry stays because the
release happened; the tag does not, so nothing resolves `^2.0` to it any more.

### Changed

Require `coolms/dtmpl` `^2.0`. DTMPL 2.0 encodes output by default and renamed
the verbatim block to `{verbatim}`; see that package's changelog for the
template migration. A major here rather than a minor because moving a consumer
across that boundary is a break for them, whether or not this bundle's own API
moved.

Also switched to the parenthesis-free `new` expression, tidied imports, and
dropped the VCS repository entry now that Packagist resolves the package by
name.

## 1.0.0 - 2026-08-14

First release. The Symfony bundle for `coolms/dtmpl`: registers the engine's
services, the loader chain, the widget registry and the constant providers.
