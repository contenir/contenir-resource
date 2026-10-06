# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

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
