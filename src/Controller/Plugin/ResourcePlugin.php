<?php

namespace Contenir\Resource\Controller\Plugin;

use Contenir\Db\Model\Entity\AbstractEntity;
use Contenir\Resource\Exception\MissingResourceException;
use Contenir\Resource\ResourceManager;
use Laminas\Filter\AbstractFilter;
use Laminas\Filter\FilterChain;
use Laminas\Filter\StringToLower;
use Laminas\Filter\Word\CamelCaseToUnderscore;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use RuntimeException;

class ResourcePlugin extends AbstractPlugin
{
    protected ResourceManager $resourceManager;
    protected AbstractFilter $filter;

    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
        $this->filter          = (new FilterChain())
            ->attach(new CamelCaseToUnderscore())
            ->attach(new StringToLower());
    }

    public function __invoke($resourceId = null, $throwException = true)
    {
        if ($resourceId === null) {
            return $this->resourceManager;
        }

        return $this->resource($resourceId, $throwException);
    }

    public function resource($resourceId = null, $throwException = true)
    {
        $resource = $this->resourceManager->findOneByField('resource_id', $resourceId);

        return $this->handleResult($resource, $throwException);
    }

    public function handleResult(AbstractEntity $resource = null, $throwException = true)
    {
        if ($resource === null && $throwException) {
            throw new MissingResourceException('Resource not found');
        }

        return $resource;
    }
}
