<?php

declare(strict_types=1);

namespace Contenir\Resource\Model\Repository;

use Contenir\Db\Model\Repository\AbstractRepository;
use Contenir\Mvc\Workflow\Resource\ResourceAdapterInterface;

class BaseResourceRepository extends AbstractRepository implements ResourceAdapterInterface
{
    public function getWorkflowResources(): iterable
    {
        return $this->find([
            'resource_type_id' => 'page',
            'parent_id IS NULL',
            'active' => 'active',
        ], [
            'sequence ASC',
        ]);
    }
}
