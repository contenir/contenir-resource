# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased] - 2026-10-21

### Added

- `og:site_name`, a `robots` meta tag, and the image's `og:image:width`, `og:image:height`, `og:image:alt` and
  `twitter:image:alt`, from new optional `PageMetadata` values: `siteName`, `robots`, `imageAlt`, `imageWidth` and
  `imageHeight`. The size is written only when both sides are known. `PageMetadataBuilder` gives every page the site
  name in `contenir_resource.site_name`. The SEO add-on's head fixes reach laminas-mvc sites through
  contenir/seo-laminas-mvc's `seo()` helper; these values let a site on this package write them through
  `PageMetadata` itself.

- A `twitter:card` tag: `summary_large_image` when the page has an image, and `summary` otherwise, so shared images
  show large.

- Articles: a resource whose type is in `contenir_resource.article_types` gets `og:type` `article`, with
  `article:published_time` and, when it changed after it was published, `article:modified_time`. `PageMetadata`
  takes the type and the publish date as new optional `type` and `published` values.

- The share image's real size: `PageMetadataBuilder` measures it with an `ImageSizeResolverInterface` and writes
  `og:image:width` and `og:image:height`. `LocalImageSizeResolver` measures files under `contenir_resource.image_root`,
  and never outside it; a site whose images live elsewhere registers its own resolver.

- `contenir_resource.image_alt_from_title`, to use the page's title as the share image's alt text, and
  `contenir_resource.description_ellipsis`, to end a description cut short with, say, `…`
  (`MetaText::summarise()`'s new `$ellipsis`).

### Fixed

- `MetaText::clean()` leaves a space where a paragraph, line break, list item, heading or other block ends, so a
  description taken from `<p>One</p><p>Two</p>` reads "One Two", not "OneTwo".

- `og:updated_time` is the date the page last changed (`getMetaModified()`), falling back to `getMetaPublish()`. It
  was the publish date whenever one was set, which for a resource is when it was created.
- Twitter tags are written with the `name` attribute, as X documents them, instead of `property`. Open Graph tags
  keep `property`.

## [2.0.0-RC2] - 2026-10-18

### Added

- CMS previews of resources: where contenir-preview's preview context is registered, the resource manager finds the
  previewed resource with the editor's unsaved values, drafts and resources not saved yet included, and puts it in its
  place wherever the site finds it, lists included (`Preview\PreviewResourceManager`, registered by
  `Container\PreviewResourceManagerDelegator`). See [CMS previews](docs/previews.md).

## [2.0.0-RC1] - 2026-10-06

A rewrite as a framework-neutral core on contenir-db-model 2. The laminas-mvc parts move to adapters: Mezzio sites
use `contenir/contenir-resource-mezzio`; a laminas-mvc 2.x adapter will be `contenir/contenir-resource-laminas-mvc`.
See [UPGRADE.md](UPGRADE.md).

### Added

- Attribute-mapped entities for `resource`, `resource_collection` and `resource_type`, each an abstract base plus a
  final default, and the `ResourceStatus` enum for the `active` column.
- `ResourceRepository::findPageTree()`: active top-level pages with their active descendants, one query per level.
- `ResourceManagerInterface` with typed lookups (`find`, `findActive`, `findActiveBySlug`, `findActiveByWorkflow`,
  `findActivePageByWorkflow`, `findBy`, `findOneBy`, `findByType`, `findOneByType`, `findCollectionByType`).
- `PageMetadataBuilder`, `PageMetadata` and `MetaTagRenderer`: the page head built from `MetadataInterface`, rendered
  as escaped HTML for any template engine, with a configurable `base_url` for canonical URLs.
- `MetaText`, `ResourceSummary`, `ExternalUrl` and `ResourceLink`, the framework-neutral parts of the 1.x view helpers.
- `ConfigProvider` and PSR-11 factories, configured under `contenir_resource`.
- Mago, PHPUnit unit and integration suites, Infection (MSI 100%) and Codecov in CI.

### Changed

- Requires PHP 8.3, 8.4 or 8.5, contenir/contenir-db-model 2 and contenir/contenir-metadata 2.
- `getMetaPublish()` returns the `created` date. In 1.x it checked `created` but returned `updated`.
- Entity properties are camelCase (`resourceTypeId`), and criteria use property names.
- Lookups that a 1.x view helper meant to limit to active resources now do: 1.x passed the filter to
  `ResourceManager::findOne()`, which ignored it.
- Summaries are cut by characters, not bytes, so multibyte text is never split mid-character.
- A description without word breaks is cut mid-word instead of coming out empty.
- `og:updated_time` is ISO 8601.

### Removed

- The laminas-mvc `Module`, controller plugin, view helpers and the `laminas/laminas-mvc` dependency (they move to the
  adapters).
- The magic `find(One)(Active)<Type>(By<Field>)()` methods of `ResourceManager`; use `findByType()` and
  `findOneByType()` with criteria.
