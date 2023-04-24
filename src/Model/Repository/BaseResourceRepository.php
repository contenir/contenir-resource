<?php

namespace Contenir\Resource\Model\Repository;

use Contenir\Db\Model\Repository\AbstractRepository;
use Contenir\Mvc\Workflow\Adapter\ResourceAdapterInterface;

abstract class BaseResourceRepository extends AbstractRepository implements ResourceAdapterInterface
{
    public function getWorkflowResources()
    {
        return $this->find([
            'resource_type_id' => 'page',
            'parent_id IS NULL',
            'active' => 'active'
        ], [
            'sequence ASC'
        ]);
    }
}
