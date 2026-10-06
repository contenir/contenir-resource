<?php

declare(strict_types=1);

namespace Contenir\Resource\Core;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceCollectionEntity;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;

/**
 * Looks up resources, resource collections and resource types.
 *
 * Criteria and ordering are keyed by entity PROPERTY name (for example
 * "resourceTypeId", not "resource_type_id"). Unknown property names are
 * rejected by contenir-db-model, so caller-supplied keys cannot reach SQL.
 * To match active resources only, add 'status' => ResourceStatus::Active to
 * the criteria, or use a findActive*() method.
 *
 * @api
 */
interface ResourceManagerInterface
{
    /**
     * The resource with that id, whatever its status, or null. Anything
     * other than a positive integer (or a string of digits) finds nothing,
     * without a query.
     *
     * @throws DbModelException
     */
    public function find(int|string $resourceId): ?AbstractResourceEntity;

    /**
     * Like find(), but only an active resource.
     *
     * @throws DbModelException
     */
    public function findActive(int|string $resourceId): ?AbstractResourceEntity;

    /**
     * The active resource with that slug, or null.
     *
     * @throws DbModelException
     */
    public function findActiveBySlug(string $slug): ?AbstractResourceEntity;

    /**
     * The first active resource with that workflow, or null.
     *
     * @throws DbModelException
     */
    public function findActiveByWorkflow(string $workflow): ?AbstractResourceEntity;

    /**
     * The active page resource with that workflow which is visible in
     * navigation, or null.
     *
     * @throws DbModelException
     */
    public function findActivePageByWorkflow(string $workflow): ?AbstractResourceEntity;

    /**
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $orderBy property => ASC|DESC
     *
     * @return list<AbstractResourceEntity>
     *
     * @throws DbModelException
     */
    public function findBy(array $criteria = [], array $orderBy = []): array;

    /**
     * Resources of one type, or of any of a list of types.
     *
     * @param string|list<string>   $resourceTypeId
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $orderBy property => ASC|DESC
     *
     * @return list<AbstractResourceEntity>
     *
     * @throws DbModelException
     */
    public function findByType(string|array $resourceTypeId, array $criteria = [], array $orderBy = []): array;

    /**
     * The active collections of one type, or of any of a list of types,
     * ordered by sequence.
     *
     * @param string|list<string> $resourceTypeId
     *
     * @return list<AbstractResourceCollectionEntity>
     *
     * @throws DbModelException
     */
    public function findCollectionByType(string|array $resourceTypeId): array;

    /**
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $orderBy property => ASC|DESC
     *
     * @throws DbModelException
     */
    public function findOneBy(array $criteria, array $orderBy = []): ?AbstractResourceEntity;

    /**
     * The first resource of one type, or of any of a list of types.
     *
     * @param string|list<string>   $resourceTypeId
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $orderBy property => ASC|DESC
     *
     * @throws DbModelException
     */
    public function findOneByType(
        string|array $resourceTypeId,
        array $criteria = [],
        array $orderBy = [],
    ): ?AbstractResourceEntity;
}
