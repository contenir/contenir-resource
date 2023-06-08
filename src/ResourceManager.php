<?php

namespace Contenir\Resource;

use Contenir\Resource\Model\Repository\BaseResourceRepository;
use Contenir\Resource\Model\Repository\BaseResourceCollectionRepository;
use Contenir\Resource\Model\Repository\BaseResourceTypeRepository;
use Laminas\Filter\FilterChain;
use Laminas\Filter\StringToLower;
use Laminas\Filter\Word\CamelCaseToUnderscore;
use RuntimeException;

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

    protected $fieldFilter;

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

        $this->fieldFilter = new FilterChain();
        $this->fieldFilter
            ->attach(new CamelCaseToUnderscore())
            ->attach(new StringToLower());
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

    public function findCollectionByType($resourceTypeId)
    {
        return $this->resourceCollectionRepository->find([
            'resource_type_id' => $resourceTypeId,
            'active'           => 'active'
        ], [
            'sequence ASC'
        ]);
    }

    public function __call($method, array $args)
    {
        $matches = [];

        if (preg_match('/^find(One)(Active)?(\w+?)(?:By(\w+))?$/', $method, $matches)) {
            $where          = [];
            $method         = isset($matches[1]) ? 'findOneByField' : 'findByField';
            $active         = isset($matches[2]) ? 'active' : null;
            $resourceTypeId = $matches[3];
            $param          = $matches[4] ?? null;

            if ($active) {
                $where['active'] = 'active';
            }

            if ($param) {
                $field         = $this->fieldFilter->filter($param);
                $where[$field] = $args[0];
            }

            return $this->$method('resource_type_id', $resourceTypeId, $where);
        }

        throw new RuntimeException("Unrecognized method '$method()'");
    }
}
