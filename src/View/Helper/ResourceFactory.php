<?php

namespace Contenir\Resource\View\Helper;

use Contenir\Resource\ResourceManager;
use Contenir\Resource\View\Helper\Resource as ResourceHelper;
use Psr\Container\ContainerInterface;

class ResourceFactory
{
    public function __invoke(ContainerInterface $container): ResourceHelper
    {
        $resourceManager = $container->get(ResourceManager::class);

        return new ResourceHelper($resourceManager);
    }
}
