<?php

namespace Contenir\Resource;

use Contenir\Resource\Model\Repository\BaseResourceRepository;
use Contenir\Resource\Model\Repository\BaseResourceCollectionRepository;
use Contenir\Resource\Model\Repository\BaseResourceTypeRepository;

/**
 * The AuthManager service is responsible for user's login/logout and simple access
 * filtering. The access filtering feature checks whether the current visitor
 * is allowed to see the given page or not.
 */
class ResourceManager
{
    /**
     * User Repository
     * @var \Application\Repository\ResourceRepository
     */
    protected $resourceRepository;

    /**
     * User Repository
     * @var \Application\Repository\ResourceCollectionRepository
     */
    protected $resourceCollectionRepository;

    /**
     * User Repository
     * @var \Application\Repository\ResourceTypeRepository
     */
    protected $resourceTypeRepository;

    /**
     * Constructs the service.
     */
    public function __construct(
        BaseResourceRepository $resourceRepository,
        BaseResourceCollectionRepository $resourceCollectionRepository,
        BaseResourceTypeRepository $resourceTypeRepository
    ) {
        $this->resourceRepository           = $resourceRepository;
        $this->resourceCollectionRepository = $resourceCollectionRepository;
        $this->resourceTypeRepository       = $resourceTypeRepository;
    }

    public function findOne($resourceId)
    {
        return $this->resourceRepository->findOne([
            'resource_id' => $resourceId
        ]);
    }

    public function findOneByField($field, $value, array $where = [])
    {
        $where[$field] = $value;

        return $this->resourceRepository->findOne($where);
    }

    public function findByType($resourceTypeId)
    {
        return $this->resourceRepository->find([
            'resource_type_id' => $resourceTypeId
        ]);
    }

    public function findActivePageByWorkflow($workflow)
    {
        return $this->resourceRepository->findOne([
            'resource_type_id' => 'page',
            'workflow'         => $workflow,
            'active'           => 'active',
            'visible'          => 1
        ]);
    }
}
