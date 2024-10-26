<?php

declare(strict_types=1);

namespace Contenir\Resource;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

class ResourceManagerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param string     $requestedName
     * @param null|array $options
     * @throws ContainerExceptionInterface If any other error occurs.
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ): ResourceManager {
        $config = $container->get('config')['resource'] ?? [];
        if (empty($config)) {
            throw new RuntimeException('No resource config provided');
        }

        $resourceRepository           = $container->get($config['repository']['resource']);
        $resourceCollectionRepository = $container->get($config['repository']['resource_collection']);
        $resourceTypeRepository       = $container->get($config['repository']['resource_type']);

        return new $requestedName(
            $resourceRepository,
            $resourceCollectionRepository,
            $resourceTypeRepository
        );
    }
}
