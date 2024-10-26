<?php

declare(strict_types=1);

namespace Contenir\Resource\Controller\Plugin;

use Contenir\Db\Model\Entity\EntityInterface;
use Contenir\Resource\Exception\MissingResourceException;
use Contenir\Resource\ResourceManager;
use Laminas\Filter\FilterChain;
use Laminas\Filter\FilterInterface;
use Laminas\Filter\StringToLower;
use Laminas\Filter\Word\CamelCaseToUnderscore;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class ResourcePlugin extends AbstractPlugin
{
    protected ResourceManager $resourceManager;
    protected FilterInterface $filter;

    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
        $this->filter          = (new FilterChain())
            ->attach(new CamelCaseToUnderscore())
            ->attach(new StringToLower());
    }

    public function __invoke(
        ?string $resourceId = null,
        bool $throwException = true
    ): EntityInterface|ResourceManager|null {
        if ($resourceId === null) {
            return $this->resourceManager;
        }

        return $this->resource($resourceId, $throwException);
    }

    public function resource(string|iterable|null $resourceId = null, bool $throwException = true): ?EntityInterface
    {
        $resource = $this->resourceManager->findOneByField('resource_id', $resourceId);

        return $this->handleResult($resource, $throwException);
    }

    public function handleResult(?EntityInterface $resource = null, bool $throwException = true): ?EntityInterface
    {
        if ($resource === null && $throwException) {
            throw new MissingResourceException('Resource not found');
        }

        return $resource;
    }
}
