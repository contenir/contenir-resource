<?php

declare(strict_types=1);

namespace Contenir\Resource\Core;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Repository\ResourceCollectionRepository;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Override;

use function is_int;
use function preg_match;

/**
 * The default resource manager, over the resource and resource collection
 * repositories.
 *
 * @api
 *
 * @mago-expect lint:too-many-methods One method per lookup of ResourceManagerInterface, plus the constructor.
 */
final readonly class ResourceManager implements ResourceManagerInterface
{
    public function __construct(
        private ResourceRepository $resources,
        private ResourceCollectionRepository $collections,
    ) {}

    #[Override]
    public function find(int|string $resourceId): ?AbstractResourceEntity
    {
        $valid = is_int($resourceId) ? $resourceId > 0 : 1 === preg_match('/^[1-9][0-9]*$/D', $resourceId);

        return $valid ? $this->resources->find($resourceId) : null;
    }

    #[Override]
    public function findActive(int|string $resourceId): ?AbstractResourceEntity
    {
        $resource = $this->find($resourceId);

        return true === $resource?->isActive() ? $resource : null;
    }

    #[Override]
    public function findActiveBySlug(string $slug): ?AbstractResourceEntity
    {
        return $this->findOneBy(['slug' => $slug, 'status' => ResourceStatus::Active]);
    }

    #[Override]
    public function findActiveByWorkflow(string $workflow): ?AbstractResourceEntity
    {
        return $this->findOneBy(['workflow' => $workflow, 'status' => ResourceStatus::Active]);
    }

    #[Override]
    public function findActivePageByWorkflow(string $workflow): ?AbstractResourceEntity
    {
        return $this->findOneBy([
            'resourceTypeId' => ResourceRepository::ROOT_TYPE,
            'workflow'       => $workflow,
            'status'         => ResourceStatus::Active,
            'visible'        => true,
        ]);
    }

    #[Override]
    public function findBy(array $criteria = [], array $orderBy = []): array
    {
        return $this->resources->findBy($criteria, $orderBy);
    }

    #[Override]
    public function findByType(string|array $resourceTypeId, array $criteria = [], array $orderBy = []): array
    {
        return $this->findBy([...$criteria, 'resourceTypeId' => $resourceTypeId], $orderBy);
    }

    #[Override]
    public function findCollectionByType(string|array $resourceTypeId): array
    {
        return $this->collections->findBy(
            ['resourceTypeId' => $resourceTypeId, 'status' => ResourceStatus::Active],
            ['sequence' => 'ASC'],
        );
    }

    #[Override]
    public function findOneBy(array $criteria, array $orderBy = []): ?AbstractResourceEntity
    {
        return $this->resources->findOneBy($criteria, $orderBy);
    }

    #[Override]
    public function findOneByType(
        string|array $resourceTypeId,
        array $criteria = [],
        array $orderBy = [],
    ): ?AbstractResourceEntity {
        return $this->findOneBy([...$criteria, 'resourceTypeId' => $resourceTypeId], $orderBy);
    }
}
