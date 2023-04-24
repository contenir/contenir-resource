<?php

namespace Contenir\Resource;

use Contenir\Resource\Model\Repository\BaseResourceRepository;
use Contenir\Resource\Model\Repository\BaseResourceCollectionRepository;
use Contenir\Resource\Model\Repository\BaseResourceTypeRepository;
use Application\Service\Manager\ResourceManager;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use RuntimeException;

class ResourceManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')['resource'] ?? [];
        if (empty($config)) {
            throw new RuntimeException('No resource config provided');
        }

        $resourceRepository           = $container->get($config['repository']['resource']);
        $resourceCollectionRepository = $container->get($config['repository']['resource_collection']);
        $resourceTypeRepository       = $container->get($config['repository']['resource_type']);

        return new ResourceManager(
            $resourceRepository,
            $resourceCollectionRepository,
            $resourceTypeRepository
        );
    }
}
