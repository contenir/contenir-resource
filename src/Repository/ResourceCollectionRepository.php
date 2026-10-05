<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Repository;

use Contenir\Db\Model\EntityManager;
use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Db\Model\Repository;
use Contenir\Resource\Core\Entity\AbstractResourceCollectionEntity;
use Contenir\Resource\Core\Entity\ResourceCollectionEntity;

/**
 * Finders for resource collection entities.
 *
 * @extends Repository<AbstractResourceCollectionEntity>
 *
 * @api
 */
final class ResourceCollectionRepository extends Repository
{
    /**
     * @param class-string<AbstractResourceCollectionEntity> $entityClass
     *
     * @throws DbModelException When the entity class is not a valid mapping.
     */
    public function __construct(EntityManager $em, string $entityClass = ResourceCollectionEntity::class)
    {
        parent::__construct($em, $entityClass);
    }
}
