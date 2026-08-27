# Changelog

All notable changes to `coolms/dtmpl-bundle` are recorded here.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).
Versioning is described in `CONTRIBUTING.md` -- read it before assuming what a
major number means here.

⚠️ Entries dated before 2026-09-01 were **reconstructed** from tags and commit
history when this file was created. Every entry after that is written in the
same commit as the change it describes.

## Unreleased

Contributor documentation only: `CONTRIBUTING.md`, describing the Tuesday
release train, the deprecation window, and how this package's version number
relates to the CoolMS platform packages.

No code changed, so **this will not be released on its own.** It rides out with
the next change that is worth a version number -- publishing an empty patch to
ship a documentation file would contradict the policy the file describes.

## 2.0.0 - 2026-08-26

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
