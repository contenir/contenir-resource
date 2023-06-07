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

    public function __invoke($resourceId = null)
    {
        if ($resourceId === null) {
            return $this;
        }
    }

    public function resource($resourceId = null, $throwException = true)
    {
        $resource = $this->resourceManager->findOneByField('resource_id', $resourceId);

        return $this->handleResult($resource, $throwException);
    }

    public function __call($method, array $args)
    {
        $controller = $this->getController();
        $matches    = [];

        if (preg_match('/^findOne(Active)?(\w+?)(?:By(\w+))?$/', $method, $matches)) {
            $where          = [];
            $active         = isset($matches[1]) ? 'active' : null;
            $throwException = isset($args[1]) ? (bool) $args[1] : true;
            $resourceTypeId = $this->filter->filter($matches[2]);
            $param          = isset($matches[3]) ? $this->filter->filter($matches[3]) : null;

            if ($active) {
                $where['active'] = 'active';
            }

            if ($param) {
                $value         = isset($args[0]) ? $args[0] : $controller->getEvent()->getRouteMatch()->getParam($param);
                $where[$param] = $value;
            }

            $resource = $this->resourceManager->findOneByField('resource_type_id', $resourceTypeId, $where);

            return $this->handleResult($resource, $throwException);
        }

        throw new RuntimeException("Unrecognized method '$method()'");
    }

    public function handleResult(AbstractEntity $resource = null, $throwException = true)
    {
        if ($resource === null && $throwException) {
            throw new MissingResourceException('Resource not found');
        }

        return $resource;
    }
}
