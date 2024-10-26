<?php

declare(strict_types=1);

namespace Contenir\Resource\View\Helper;

use Contenir\Resource\ResourceManager;
use Contenir\Resource\View\Helper\Resource as ResourceHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class ResourceFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceHelper
    {
        $resourceManager = $container->get(ResourceManager::class);

        return new ResourceHelper($resourceManager);
    }
}
