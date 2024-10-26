<?php

declare(strict_types=1);

namespace Contenir\Resource;

use Contenir\Db\Model\Entity\EntityInterface;
use Contenir\Resource\Model\Repository\BaseResourceCollectionRepository;
use Contenir\Resource\Model\Repository\BaseResourceRepository;
use Contenir\Resource\Model\Repository\BaseResourceTypeRepository;
use Laminas\Filter\FilterChain;
use Laminas\Filter\StringToLower;
use Laminas\Filter\Word\CamelCaseToUnderscore;
use RuntimeException;

use function preg_match;

/**
 * The AuthManager service is responsible for user's login/logout and simple access
 * filtering. The access filtering feature checks whether the current visitor
 * is allowed to see the given page or not.
 */
class ResourceManager
{
    /**
     * User Repository
     */
    protected BaseResourceRepository $resourceRepository;

    /**
     * User Repository
     */
    protected BaseResourceCollectionRepository $resourceCollectionRepository;

    /**
     * User Repository
     */
    protected BaseResourceTypeRepository $resourceTypeRepository;

    protected FilterChain $fieldFilter;

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

    public function findOne(string|iterable $resourceId): ?EntityInterface
    {
        return $this->resourceRepository->findOne([
            'resource_id' => $resourceId,
        ]);
    }

    public function findByField(string $field, mixed $value, array $where = []): iterable
    {
        $where[$field] = $value;

        return $this->resourceRepository->find($where);
    }

    public function findOneByField(string $field, mixed $value, array $where = []): ?EntityInterface
    {
        $where[$field] = $value;

        return $this->resourceRepository->findOne($where);
    }

    public function findByType(string|iterable $resourceTypeId): iterable
    {
        return $this->resourceRepository->find([
            'resource_type_id' => $resourceTypeId,
        ]);
    }

    public function findActivePageByWorkflow(string $workflow): ?EntityInterface
    {
        return $this->resourceRepository->findOne([
            'resource_type_id' => 'page',
            'workflow'         => $workflow,
            'active'           => 'active',
            'visible'          => 1,
        ]);
    }

    public function findCollectionByType(string|iterable $resourceTypeId): iterable
    {
        return $this->resourceCollectionRepository->find([
            'resource_type_id' => $resourceTypeId,
            'active'           => 'active',
        ], [
            'sequence ASC',
        ]);
    }

    public function __call(string $method, array $args): mixed
    {
        $matches = [];

        if (preg_match('/^find(One)?(Active)?(\w+?)(?:By(\w+))?$/', $method, $matches)) {
            $where          = [];
            $method         = ! empty($matches[1]) ? 'findOneByField' : 'findByField';
            $active         = ! empty($matches[2]) ? 'active' : null;
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
