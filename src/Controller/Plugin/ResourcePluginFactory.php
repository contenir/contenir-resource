<?php

namespace Contenir\Resource\Controller\Plugin;

use Contenir\Resource\ResourceManager;
use Psr\Container\ContainerInterface;

class ResourcePluginFactory
{
    public function __invoke(ContainerInterface $container): ResourcePlugin
    {
        $resourceManager = $container->get(ResourceManager::class);

        return new ResourcePlugin($resourceManager);
    }
}
