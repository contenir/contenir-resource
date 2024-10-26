<?php

declare(strict_types=1);

namespace Contenir\Resource\Controller\Plugin;

use Contenir\Resource\ResourceManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ResourcePluginFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourcePlugin
    {
        $resourceManager = $container->get(ResourceManager::class);

        return new ResourcePlugin($resourceManager);
    }
}
