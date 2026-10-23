# Configuration and container

`Contenir\Resource\Core\ConfigProvider` registers, under `dependencies`:

| Service | Built by |
| --- | --- |
| `ResourceRepository`, `ResourceCollectionRepository`, `ResourceTypeRepository` | `Container\RepositoryFactory`, with the configured entity class and the `EntityManager` |
| `ResourceManager` (alias `ResourceManagerInterface`) | `Container\ResourceManagerFactory` |
| `PageMetadataBuilder` | `Container\PageMetadataBuilderFactory` |
| `PathImageUrlResolver` (alias `ImageUrlResolverInterface`) | `Container\PathImageUrlResolverFactory` |
| `ResourceSummary` | `Container\ResourceSummaryFactory` |
| `MetaTagRenderer` | invokable |

The `EntityManager` comes from contenir-db-model's `ConfigProvider`. Override any service in your own
`dependencies`, for example alias `ImageUrlResolverInterface` to your asset service's resolver.

## Keys

All under `contenir_resource`; all optional.

| Key | Default | Used by |
| --- | --- | --- |
| `resource_entity` | `ResourceEntity` | `RepositoryFactory`; must extend `AbstractResourceEntity` |
| `resource_collection_entity` | `ResourceCollectionEntity` | must extend `AbstractResourceCollectionEntity` |
| `resource_type_entity` | `ResourceTypeEntity` | must extend `AbstractResourceTypeEntity` |
| `base_url` | none: the request origin | `PageMetadataBuilder`; an absolute http(s) URL, no query or fragment |
| `image_base_url` | `base_url`, then the request origin | `PathImageUrlResolver` |
| `description_length` | `160` | `PageMetadataBuilder` |
| `site_name` | none | `PageMetadataBuilder`: `og:site_name` on every page |
| `description_ellipsis` | none | `PageMetadataBuilder`: what a description cut short ends with, such as `…` |
| `article_types` | none | `PageMetadataBuilder`: the resource types whose pages are articles, such as `['project', 'news']` |
| `image_alt_from_title` | `false` | `PageMetadataBuilder`: whether the share image's alt text is the page's title |
| `image_root` | none | `PageMetadataBuilder`: the directory share image paths are measured under, usually the web root; an `ImageSizeResolverInterface` service takes its place |
| `summary_length` | `249` | `ResourceSummary` |
| `section_renderer` | none | `ResourceSummary`; a `SectionRendererInterface` service name |

Wrong values (a class that does not extend the base, a relative `base_url`, a zero length, a service of the wrong
type) throw `Exception\ConfigurationException` naming the key or service.
