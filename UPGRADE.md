# Upgrading from 1.x to 2.0

2.0 is a rewrite. The 1.x line (laminas-mvc, contenir-db-model 1.x, contenir-mvc-workflow 1.x) stays available from
the `1.x` branch and the `v1.*` tags, and is frozen.

## Which package

| You run | Use |
| --- | --- |
| Mezzio | `contenir/contenir-resource-mezzio` 2.x, which requires this package |
| laminas-mvc | Stay on `contenir/contenir-resource` 1.x until `contenir/contenir-resource-laminas-mvc` 2.x is released |
| Anything else (CLI, workers, another framework) | This package on its own |

## Checklist

1. Require PHP 8.3+ and contenir/contenir-db-model 2 (its own [upgrade guide](https://github.com/contenir/contenir-db-model/blob/main/UPGRADE-2.0.md)
   covers entities, repositories and the `EntityManager`).
2. Replace your `BaseResourceEntity` subclass with an `AbstractResourceEntity` subclass carrying
   `#[Table('resource')]`; map your extra columns and relations with attributes; set
   `contenir_resource.resource_entity` to it.
3. Rename criteria keys to property names (`resource_type_id` → `resourceTypeId`, `active` → `status` with
   `ResourceStatus::Active`).
4. Replace calls to the magic finders (table below).
5. Replace the `resource.repository` config with `contenir_resource.*_entity`.
6. Move the controller plugin and view helper calls to your adapter (contenir-resource-mezzio documents the Mezzio
   equivalents).

## Classes

| 1.x | 2.0 |
| --- | --- |
| `Contenir\Resource\Model\Entity\BaseResourceEntity` | `Contenir\Resource\Core\Entity\AbstractResourceEntity` (default `ResourceEntity`) |
| `Contenir\Resource\Model\Entity\BaseResourceCollectionEntity` | `Entity\AbstractResourceCollectionEntity` (default `ResourceCollectionEntity`) |
| `Contenir\Resource\Model\Entity\BaseResourceTypeEntity` | `Entity\AbstractResourceTypeEntity` (default `ResourceTypeEntity`) |
| `Model\Repository\BaseResourceRepository` | `Repository\ResourceRepository` (final; configure the entity instead of extending) |
| `Model\Repository\BaseResourceCollectionRepository` | `Repository\ResourceCollectionRepository` |
| `Model\Repository\BaseResourceTypeRepository` | `Repository\ResourceTypeRepository` |
| `BaseResourceRepository::getWorkflowResources()` | `ResourceRepository::findPageTree()`; the adapters wrap it for their workflow package |
| `ResourceManager`, `ResourceManagerFactory` | `ResourceManager` (implements `ResourceManagerInterface`), `Container\ResourceManagerFactory` |
| `Exception\MissingResourceException` | `Exception\MissingResourceException` (implements this package's `ExceptionInterface`, not laminas-mvc's) |
| `Module`, `Controller\Plugin\*`, `View\Helper\*` | Adapters; the logic behind them is `PageMetadataBuilder`, `MetaTagRenderer`, `MetaText`, `ResourceSummary`, `ExternalUrl` |

## Entity methods

| 1.x `BaseResourceEntity` | 2.0 `AbstractResourceEntity` |
| --- | --- |
| `$entity->resource_id`, `->resource_type_id`, ... (magic) | `->resourceId`, `->resourceTypeId`, ... (typed properties) |
| `$entity->active === 'active'` | `$entity->isActive()` or `->status === ResourceStatus::Active` |
| `$entity->children` (relation) | `getChildren()`, filled by `findPageTree()`; declare your own relation if you need it elsewhere |
| `getRouteId($path = '')` | `getRouteName()`; Mezzio has no child routes, so there is no `$path` |
| `getRoutePath()` | The workflow builds the path from `getSlug()` |
| `getMetaTitle()` | Same; blank strings now count as not set |
| `getMetaDescription()` | Same |
| `getMetaImage()` (`$this->image[0]->path`) | Returns `null`; override it with your image relation |
| `getMetaModified()` | Same (`updated`), already a `DateTimeImmutable` |
| `getMetaPublish()` | Returns `created`. 1.x returned `updated` by mistake |
| `getPrimaryKeys()` | `['resourceId' => id]`, keyed by property name (1.x: `['resource_id' => id]`) |

## ResourceManager methods

| 1.x | 2.0 |
| --- | --- |
| `findOne($id)` | `find($id)`; `findActive($id)` for active only |
| `findByField('slug', $slug, $where)` | `findBy([...$criteria, 'slug' => $slug])` |
| `findOneByField('workflow', $w, ['active' => 'active'])` | `findActiveByWorkflow($w)`, or `findOneBy(['workflow' => $w, 'status' => ResourceStatus::Active])` |
| `findByType($type)` | `findByType($type)` |
| `findActivePageByWorkflow($w)` | `findActivePageByWorkflow($w)` (now also requires `visible`, as 1.x did) |
| `findCollectionByType($type)` | `findCollectionByType($type)` |
| `findArticle()` (magic) | `findByType('article')` |
| `findOneArticle()` | `findOneByType('article')` |
| `findActiveArticle()` | `findByType('article', ['status' => ResourceStatus::Active])` |
| `findOneActiveArticleBySlug($s)` | `findOneByType('article', ['slug' => $s, 'status' => ResourceStatus::Active])` |

The magic finders converted `CamelCase` to `snake_case` column names; 2.0 criteria are property names, so the
conversion no longer applies.

## Configuration

| 1.x | 2.0 |
| --- | --- |
| `resource.repository.resource` (repository service) | `contenir_resource.resource_entity` (entity class) |
| `resource.repository.resource_collection` | `contenir_resource.resource_collection_entity` |
| `resource.repository.resource_type` | `contenir_resource.resource_type_entity` |
| `service_manager` aliases `resource`, `resource_collection`, `resource_type` | Gone; use the class names |
| (none) | `base_url`, `image_base_url`, `description_length`, `summary_length`, `section_renderer` |

## Behaviour changes

- `og:updated_time` is ISO 8601 (`2024-01-02T03:04:05+00:00`), not `2024-01-02 03:04:05`.
- `og:url` and `twitter:url` are the canonical URL, without the query string.
- Meta descriptions have all whitespace collapsed; one without word breaks is cut mid-word instead of being empty.
- Summaries are cut by characters, not bytes.
- External links without a scheme get `https://` (1.x: `http://`); `javascript:` and other schemes are rejected.
