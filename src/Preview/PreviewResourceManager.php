<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Preview;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Contenir\Resource\Core\ResourceManagerInterface;
use Override;

/**
 * The site's resource manager while the CMS previews a resource. A lookup the previewed resource
 * answers with its unsaved values, by its id or by the fields it is looked up by (a detail page's
 * slug, a page's workflow), finds it, as a draft would be found once published: status filters
 * are passed over. Every other lookup goes to the site's own manager, with the previewed
 * resource in its place wherever that returns it, so lists show its unsaved values too. A
 * resource not saved yet joins no list.
 *
 * @api
 *
 * @mago-expect lint:too-many-methods One method per lookup of ResourceManagerInterface, plus the constructor.
 */
final readonly class PreviewResourceManager implements ResourceManagerInterface
{
    public function __construct(
        private ResourceManagerInterface $resources,
        private PreviewedResource $previewed,
    ) {}

    /**
     * @throws DbModelException
     */
    #[Override]
    public function find(int|string $resourceId): ?AbstractResourceEntity
    {
        return $this->previewed->over($this->resources->find($resourceId));
    }

    /**
     * @throws DbModelException
     */
    #[Override]
    public function findActive(int|string $resourceId): ?AbstractResourceEntity
    {
        return $this->previewed->byId($resourceId) ?? $this->previewed->over($this->resources->findActive($resourceId));
    }

    /**
     * @throws DbModelException
     */
    #[Override]
    public function findActiveBySlug(string $slug): ?AbstractResourceEntity
    {
        return (
            $this->previewed->matching(['slug' => $slug]) ?? $this->previewed->over($this->resources->findActiveBySlug(
                $slug,
            ))
        );
    }

    /**
     * @throws DbModelException
     */
    #[Override]
    public function findActiveByWorkflow(string $workflow): ?AbstractResourceEntity
    {
        return (
            $this->previewed->matching(['workflow' => $workflow])
                ?? $this->previewed->over($this->resources->findActiveByWorkflow($workflow))
        );
    }

    /**
     * @throws DbModelException
     */
    #[Override]
    public function findActivePageByWorkflow(string $workflow): ?AbstractResourceEntity
    {
        return (
            $this->previewed->matching(['resourceTypeId' => ResourceRepository::ROOT_TYPE, 'workflow' => $workflow])
                ?? $this->previewed->over($this->resources->findActivePageByWorkflow($workflow))
        );
    }

    /**
     * @throws DbModelException
     */
    #[Override]
    public function findBy(array $criteria = [], array $orderBy = []): array
    {
        return $this->previewed->overEach($this->resources->findBy($criteria, $orderBy));
    }

    /**
     * @throws DbModelException
     */
    #[Override]
    public function findByType(string|array $resourceTypeId, array $criteria = [], array $orderBy = []): array
    {
        return $this->previewed->overEach($this->resources->findByType($resourceTypeId, $criteria, $orderBy));
    }

    /**
     * @throws DbModelException
     */
    #[Override]
    public function findCollectionByType(string|array $resourceTypeId): array
    {
        return $this->resources->findCollectionByType($resourceTypeId);
    }

    /**
     * @throws DbModelException
     */
    #[Override]
    public function findOneBy(array $criteria, array $orderBy = []): ?AbstractResourceEntity
    {
        return (
            $this->previewed->matching($criteria) ?? $this->previewed->over($this->resources->findOneBy(
                $criteria,
                $orderBy,
            ))
        );
    }

    /**
     * @throws DbModelException
     */
    #[Override]
    public function findOneByType(
        string|array $resourceTypeId,
        array $criteria = [],
        array $orderBy = [],
    ): ?AbstractResourceEntity {
        return (
            $this->previewed->matching([...$criteria, 'resourceTypeId' => $resourceTypeId])
                ?? $this->previewed->over($this->resources->findOneByType($resourceTypeId, $criteria, $orderBy))
        );
    }
}
