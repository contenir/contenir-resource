<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Repository;

use Contenir\Db\Model\EntityManager;
use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Db\Model\Repository;
use Contenir\Resource\Core\Entity\AbstractResourceTypeEntity;
use Contenir\Resource\Core\Entity\ResourceTypeEntity;

/**
 * Finders for resource type entities.
 *
 * @extends Repository<AbstractResourceTypeEntity>
 *
 * @api
 */
final class ResourceTypeRepository extends Repository
{
    /**
     * @param class-string<AbstractResourceTypeEntity> $entityClass
     *
     * @throws DbModelException When the entity class is not a valid mapping.
     */
    public function __construct(EntityManager $em, string $entityClass = ResourceTypeEntity::class)
    {
        parent::__construct($em, $entityClass);
    }
}
