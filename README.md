# contenir/contenir-resource

[![Continuous Integration](https://github.com/contenir/contenir-resource/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/contenir/contenir-resource/actions/workflows/continuous-integration.yml)
[![codecov](https://codecov.io/gh/contenir/contenir-resource/graph/badge.svg)](https://codecov.io/gh/contenir/contenir-resource)

The framework-neutral core for [Contenir](https://github.com/contenir) resources (the pages, articles and other
content rows of a Contenir site), built on
[contenir-db-model 2](https://github.com/contenir/contenir-db-model):

- attribute-mapped entities for the `resource`, `resource_collection` and `resource_type` tables;
- repositories, including the page tree that routes and navigation are built from;
- a resource manager with the lookups sites use (by id, slug, workflow, type);
- page metadata (title, canonical URL, description, Open Graph and Twitter tags) from
  [contenir-metadata](https://github.com/contenir/contenir-metadata)'s `MetadataInterface`, with a
  renderer-agnostic HTML renderer;
- plain-text summaries and safe normalisation of editor-entered links.

It has no framework dependency: no Mezzio, PSR-7/PSR-15, laminas-mvc or routing. Use it through an adapter:

- **Mezzio:** [contenir/contenir-resource-mezzio](https://github.com/contenir/contenir-resource-mezzio) adds workflow
  routing and navigation, a middleware that resolves the routed resource, URL generation and optional laminas-view
  helpers.
- **laminas-mvc:** a 2.x adapter will be published as `contenir/contenir-resource-laminas-mvc`. Until then laminas-mvc
  sites stay on the 1.x line of this package (the `1.x` branch and `v1.*` tags).

Version 2.0 is a rewrite and is not compatible with 1.x; see [UPGRADE.md](UPGRADE.md).

## Requirements

- PHP 8.3, 8.4 or 8.5
- contenir/contenir-db-model 2.x and contenir/contenir-metadata 2.x
- Any PSR-11 container, for the optional factories

## Install

```bash
composer require contenir/contenir-resource
```

With [laminas-component-installer](https://docs.laminas.dev/laminas-component-installer/) the
`Contenir\Resource\Core\ConfigProvider` is added to your configuration automatically. It registers its services
under `dependencies`; contenir-db-model's own `ConfigProvider` supplies the `EntityManager`.

## Quick start

```php
use Contenir\Resource\Core\Metadata\MetaTagRenderer;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\ResourceManagerInterface;

$resources = $container->get(ResourceManagerInterface::class);
$page      = $resources->findActiveBySlug('about');

$metadata = $container->get(PageMetadataBuilder::class)->build($page, 'https://www.example.com/about');
echo $container->get(MetaTagRenderer::class)->render($metadata); // <title>, canonical link, meta tags
```

## Configuration

Everything is optional and lives under `contenir_resource`:

```php
return [
    'contenir_resource' => [
        // Entity classes; each must extend the matching abstract entity.
        'resource_entity'            => Contenir\Resource\Core\Entity\ResourceEntity::class,
        'resource_collection_entity' => Contenir\Resource\Core\Entity\ResourceCollectionEntity::class,
        'resource_type_entity'       => Contenir\Resource\Core\Entity\ResourceTypeEntity::class,

        // The site's base URL for canonical URLs. Set it in production: without it the
        // request's own origin (from the client-controlled Host header) is used.
        'base_url'           => 'https://www.example.com',
        // Base URL for relative share-image paths; defaults to base_url, then the request origin.
        'image_base_url'     => 'https://cdn.example.com',
        'description_length' => 160,

        'summary_length'   => 249,
        // Service name of a SectionRendererInterface, used to summarise section content.
        'section_renderer' => null,
    ],
];
```

A key that is present with a value of the wrong type is a `ConfigurationException`, never silently ignored.

## Public API

| Class | Purpose |
| --- | --- |
| `Entity\AbstractResourceEntity`, `Entity\ResourceEntity` | Resource rows; metadata, navigation and routing accessors |
| `Entity\AbstractResourceCollectionEntity`, `Entity\ResourceCollectionEntity` | Collection rows |
| `Entity\AbstractResourceTypeEntity`, `Entity\ResourceTypeEntity` | Resource type rows |
| `Entity\ResourceStatus` | The `active` column: `pending`, `active`, `inactive`, `archived` |
| `Repository\ResourceRepository` | Finders and `findPageTree()` |
| `Repository\ResourceCollectionRepository`, `Repository\ResourceTypeRepository` | Finders |
| `ResourceManagerInterface`, `ResourceManager` | Lookups by id, slug, workflow, type; collections by type |
| `Metadata\PageMetadataBuilder`, `Metadata\PageMetadata` | The head metadata of one page |
| `Metadata\MetaTagRenderer` | Escaped HTML for any template engine |
| `Metadata\MetaText` | Plain-text cleaning, summaries and keywords |
| `Metadata\ImageUrlResolverInterface`, `Metadata\PathImageUrlResolver` | Share image URLs |
| `Content\ResourceSummary`, `Content\SectionAwareInterface`, `Content\SectionRendererInterface` | Plain-text summaries |
| `Url\ExternalUrl`, `Url\ResourceLink` | Safe editor-entered links |
| `ConfigProvider`, `Container\*Factory` | Container wiring |

Every concrete class is `final`; the abstract entities and the interfaces are the extension points.

The [docs](docs/) folder covers each area:

- [Entities](docs/entities.md)
- [Repositories and the resource manager](docs/repositories.md)
- [Page metadata](docs/metadata.md)
- [Summaries and links](docs/content-and-links.md)
- [Configuration and container](docs/configuration.md)

## Development

The QA toolchain is [php-db/phpdb-qa-tools](https://github.com/php-db/phpdb-qa-tools).
[Mago](https://mago.carthage.software/) is a standalone binary, installed separately (`brew install mago`).

```bash
composer check             # everything below
composer cs-check          # mago format --check && mago lint
composer static-analysis   # mago analyze
composer test              # unit suite: no I/O
composer test-integration  # integration suite: in-memory SQLite, real EntityManager and ServiceManager
composer test-coverage     # both suites, clover.xml for Codecov
composer mutation-test     # Infection over both suites (needs Xdebug or PCOV)
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
