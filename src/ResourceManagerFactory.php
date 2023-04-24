<?php

namespace Application\Service\Manager\Factory;

use Application\Repository\ResourceRepository;
use Application\Repository\ResourceCollectionRepository;
use Application\Repository\ResourceTypeRepository;
use Application\Service\Manager\ResourceManager;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class ResourceManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $resourceRepository = $container->get(ResourceRepository::class);
        $resourceCollectionRepository = $container->get(ResourceCollectionRepository::class);
        $resourceTypeRepository = $container->get(ResourceTypeRepository::class);

        return new ResourceManager(
            $resourceRepository,
            $resourceCollectionRepository,
            $resourceTypeRepository
        );
    }
}
