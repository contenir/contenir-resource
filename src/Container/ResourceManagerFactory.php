<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Container;

use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Repository\ResourceCollectionRepository;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Contenir\Resource\Core\ResourceManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final class ResourceManagerFactory
{
    /**
     * @throws ConfigurationException When a repository service has the wrong type.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceManager
    {
        return new ResourceManager(
            ServiceLocator::get($container, ResourceRepository::class, ResourceRepository::class),
            ServiceLocator::get($container, ResourceCollectionRepository::class, ResourceCollectionRepository::class),
        );
    }
}
