<?php

namespace Contenir\Resource\View\Helper;

use Contenir\Resource\ResourceManager;
use Laminas\View\Helper\AbstractHelper;

class Resource extends AbstractHelper
{
    protected $resourceManager;

    public function __construct(ResourceManager $resourceManager = null)
    {
        $this->resourceManager = $resourceManager;
    }

    public function __invoke($resourceId = null)
    {
        if ($resourceId === null) {
            return $this;
        }

        return $this->resourceManager->findOne($resourceId, [
            'active' => 'active'
        ]);
    }

    public function findBySlug($slug)
    {
        return $this->resourceManager->findOneByField('slug', $slug, [
            'active' => 'active'
        ]);
    }

    public function findByWorkflow($workflow)
    {
        return $this->resourceManager->findOneByField('workflow', $workflow, [
            'active' => 'active'
        ]);
    }

    public function findActivePageByWorkflow($workflow)
    {
        return $this->resourceManager->findActivePageByWorkflow($workflow);
    }
}
