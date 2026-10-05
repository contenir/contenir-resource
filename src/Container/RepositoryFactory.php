<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Container;

use Contenir\Db\Model\EntityManager;
use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Db\Model\Repository;
use Contenir\Resource\Core\Entity\AbstractResourceCollectionEntity;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\AbstractResourceTypeEntity;
use Contenir\Resource\Core\Entity\ResourceCollectionEntity;
use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Entity\ResourceTypeEntity;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Repository\ResourceCollectionRepository;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Contenir\Resource\Core\Repository\ResourceTypeRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Builds the three repositories over the container's EntityManager, each
 * for the entity class configured under "contenir_resource":
 * "resource_entity", "resource_collection_entity" and "resource_type_entity".
 *
 * @api
 */
final class RepositoryFactory
{
    /**
     * @return Repository<object>
     *
     * @throws ConfigurationException When an entity class or the EntityManager service is not usable.
     * @throws ContainerExceptionInterface
     * @throws DbModelException When the entity class is not a valid mapping.
     */
    public function __invoke(ContainerInterface $container, string $requestedName): Repository
    {
        $config = ConfigReader::fromContainer($container);
        $em     = ServiceLocator::get($container, EntityManager::class, EntityManager::class);

        return match ($requestedName) {
            ResourceRepository::class => new ResourceRepository(
                $em,
                $config->className('resource_entity', AbstractResourceEntity::class, ResourceEntity::class),
            ),
            ResourceCollectionRepository::class => new ResourceCollectionRepository(
                $em,
                $config->className(
                    'resource_collection_entity',
                    AbstractResourceCollectionEntity::class,
                    ResourceCollectionEntity::class,
                ),
            ),
            ResourceTypeRepository::class => new ResourceTypeRepository(
                $em,
                $config->className(
                    'resource_type_entity',
                    AbstractResourceTypeEntity::class,
                    ResourceTypeEntity::class,
                ),
            ),
            default                             => throw ConfigurationException::unknownRepository($requestedName),
        };
    }
}
