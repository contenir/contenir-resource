<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Repository;

use Contenir\Db\Model\EntityManager;
use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Db\Model\Repository;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;

use function array_filter;
use function array_map;

/**
 * Finders for resource entities. findPageTree() returns the active
 * top-level pages with their active descendants attached, loading one
 * query per level of the tree: the tree the framework adapters build
 * routes and navigation from.
 *
 * @extends Repository<AbstractResourceEntity>
 *
 * @api
 */
final class ResourceRepository extends Repository
{
    /**
     * The resource type of the top-level resources of the page tree.
     */
    public const string ROOT_TYPE = 'page';

    /**
     * @param class-string<AbstractResourceEntity> $entityClass
     *
     * @throws DbModelException When the entity class is not a valid mapping.
     */
    public function __construct(EntityManager $em, string $entityClass = ResourceEntity::class)
    {
        parent::__construct($em, $entityClass);
    }

    /**
     * The active top-level pages, ordered by sequence, each with its active
     * children (of any type) attached, recursively.
     *
     * @return list<AbstractResourceEntity>
     *
     * @throws DbModelException
     */
    public function findPageTree(): array
    {
        $roots = $this->findBy(
            ['resourceTypeId' => self::ROOT_TYPE, 'parentId' => null, 'status' => ResourceStatus::Active],
            ['sequence' => 'ASC', 'resourceId' => 'ASC'],
        );

        $parents = $roots;
        while ([] !== $parents) {
            $parents = $this->attachChildren($parents);
        }

        return $roots;
    }

    /**
     * Load the active children of the given parents in one query, attach
     * them, and return them as the next level.
     *
     * Every resource has a single parent, so a resource is reached at most
     * once and a parent cycle (which cannot include a top-level page) is
     * never reached at all: the walk always ends.
     *
     * @param non-empty-list<AbstractResourceEntity> $parents
     *
     * @return list<AbstractResourceEntity>
     *
     * @throws DbModelException
     */
    private function attachChildren(array $parents): array
    {
        $children = $this->findBy(
            [
                'parentId' => array_map(
                    static fn(AbstractResourceEntity $parent): ?int => $parent->resourceId,
                    $parents,
                ),
                'status'   => ResourceStatus::Active,
            ],
            ['sequence' => 'ASC', 'resourceId' => 'ASC'],
        );

        foreach ($parents as $parent) {
            $parent->setChildren(array_filter(
                $children,
                static fn(AbstractResourceEntity $child): bool => $child->parentId === $parent->resourceId,
            ));
        }

        return $children;
    }
}
