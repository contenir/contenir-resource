# Repositories and the resource manager

## Repositories

`ResourceRepository`, `ResourceCollectionRepository` and `ResourceTypeRepository` extend contenir-db-model's
`Repository`, so `find()`, `findBy()`, `findOneBy()`, `count()`, `stream()`, `createSelect()` and `preload()` are all
available. Each takes the `EntityManager` and the entity class (defaulting to the package's entity):

```php
$pages = new ResourceRepository($em, Page::class);
```

Criteria are keyed by property name. Unknown properties throw contenir-db-model's `QueryException`, so
caller-supplied keys can never become SQL identifiers.

### The page tree

`ResourceRepository::findPageTree()` returns the active top-level resources of type `page` (no parent), ordered by
`sequence` then id, each with its active children (of any type) attached through `setChildren()`, recursively. It
runs one query per level of the tree, never one per resource. A resource is reached through its single parent only,
so the walk always ends, even if the data holds a parent cycle (which can never include a top-level page).

The framework adapters build routes and navigation from this tree.

## ResourceManager

`ResourceManagerInterface` collects the lookups templates and handlers use. The default `ResourceManager` works over
`ResourceRepository` and `ResourceCollectionRepository`.

| Method | Finds |
| --- | --- |
| `find($id)` | The resource with that id, whatever its status |
| `findActive($id)` | The resource with that id, if it is active |
| `findActiveBySlug($slug)` | The active resource with that slug |
| `findActiveByWorkflow($workflow)` | The first active resource with that workflow |
| `findActivePageByWorkflow($workflow)` | The active, visible `page` resource with that workflow |
| `findBy($criteria, $orderBy)`, `findOneBy($criteria, $orderBy)` | By property criteria |
| `findByType($type, $criteria, $orderBy)`, `findOneByType(...)` | Of a type, or any of a list of types |
| `findCollectionByType($type)` | Active collections of a type (or types), by sequence |

`find()` and `findActive()` accept a positive integer or a string of digits. Anything else (`0`, `'01'`, `'abc'`,
`"1\n"`) finds nothing without running a query, so an id taken from a request is safe to pass.

To match only active resources in `findBy()`, add `'status' => ResourceStatus::Active` to the criteria.
