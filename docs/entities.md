# Entities

The entities map the standard Contenir tables with contenir-db-model 2 attributes. Each table has an abstract base
class holding the shared columns and behaviour, and a final default entity carrying `#[Table]`:

| Table | Abstract base | Default entity |
| --- | --- | --- |
| `resource` | `AbstractResourceEntity` | `ResourceEntity` |
| `resource_collection` | `AbstractResourceCollectionEntity` | `ResourceCollectionEntity` |
| `resource_type` | `AbstractResourceTypeEntity` | `ResourceTypeEntity` |

## Columns

Properties are camelCase; the column names are explicit. Criteria and ordering use the **property** names.

`AbstractResourceEntity`:

| Property | Column | Type |
| --- | --- | --- |
| `resourceId` | `resource_id` | `?int`, generated id |
| `parentId` | `parent_id` | `?int` |
| `resourceTypeId` | `resource_type_id` | `?string` |
| `workflow` | `workflow` | `?string` |
| `sequence` | `sequence` | `?int` |
| `slug` | `slug` | `?string` |
| `title`, `titleShort`, `subtitle` | `title`, `title_short`, `subtitle` | `?string` |
| `description` | `description` | `?string` |
| `metaTitle`, `metaDescription` | `meta_title`, `meta_description` | `?string` |
| `visible` | `visible` | `?bool` |
| `created`, `updated` | `created`, `updated` | `?DateTimeImmutable` |
| `status` | `active` | `?ResourceStatus` |

`AbstractResourceCollectionEntity`: `resourceCollectionId`, `resourceTypeId`, `sequence`, `title`, `slug`,
`description`, `layout`, `metaTitle`, `metaDescription`, `status` (column `active`).

`AbstractResourceTypeEntity`: `resourceTypeId` (string id), `slug`, `sequence`, `type`, `title`.

Only the columns every Contenir site has are mapped. Site-specific columns (`section`, `metadata_1`, `url`, `theme`,
`thumbnail`, `publish`, ...) and relations (images, tags, collections) belong on the site's own entity.

## Your own entity

```php
use Contenir\Db\Model\Collection;
use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\ManyToMany;
use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Mapping\Via;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;

#[Table('resource')]
final class Page extends AbstractResourceEntity
{
    #[Column]
    public ?string $section = null;

    /** @var Collection<Asset> */
    #[ManyToMany(Asset::class, via: new Via('lookup_resource_asset', foreignKey: 'resource_id', relatedKey: 'asset_id'))]
    public Collection $image;

    public function getMetaImage(): ?string
    {
        return $this->image->first()?->path;
    }
}
```

Then point the repository at it: `'contenir_resource' => ['resource_entity' => Page::class]`.

## Behaviour of AbstractResourceEntity

| Method | Returns |
| --- | --- |
| `getId()` | The resource id; throws `MissingResourceException` for an unsaved entity |
| `getPrimaryKeys()` | `['resourceId' => id]` (the key is `AbstractResourceEntity::PRIMARY_KEY`) |
| `getType()`, `getSlug()` | The type and slug, `''` when not set |
| `getRouteName()` | `"<type>-<id>"`, the route name the default workflows register |
| `getWorkflowName()` | The `workflow` column, or `page` when empty |
| `getNavigationLabel()` | The short title, or the title |
| `isActive()` | Status is `active` |
| `isVisible()` | `visible` is true (null means hidden) |
| `getChildren()`, `setChildren()` | The children assembled by `ResourceRepository::findPageTree()` |
| `getMetaTitle()` | The meta title, or the title and subtitle joined by a space |
| `getMetaDescription()` | The meta description, or the description |
| `getMetaImage()` | `null`; override it |
| `getMetaModified()` | `updated` |
| `getMetaPublish()` | `created` |

Empty and whitespace-only strings count as "not set" throughout, so a blank meta title falls back to the title.
