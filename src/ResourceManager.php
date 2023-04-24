<?php

namespace Application\Service\Manager;

use Application\Entity\ResourceEntity;
use Application\Entity\ResourceCollectionEntity;
use Application\Entity\ResourceTypeEntity;
use Application\Repository\ResourceRepository;
use Application\Repository\ResourceCollectionRepository;
use Application\Repository\ResourceTypeRepository;
use Laminas\Db\Sql;

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
    private $resourceRepository;

    /**
     * User Repository
     * @var \Application\Repository\ResourceCollectionRepository
     */
    private $resourceCollectionRepository;

    /**
     * User Repository
     * @var \Application\Repository\ResourceTypeRepository
     */
    private $resourceTypeRepository;

    /**
     * Constructs the service.
     */
    public function __construct(
        ResourceRepository $resourceRepository,
        ResourceCollectionRepository $resourceCollectionRepository,
        ResourceTypeRepository $resourceTypeRepository
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

    public function findPage($slug)
    {
        return $this->resourceRepository->findOne([
            'resource_type_id' => 'page',
            'slug'             => $slug
        ]);
    }

    public function findCollections(
        $resourceTypeId,
        $slug = null
    ) {
        $faqs  = [];
        $where = [
            'resource_type_id' => $resourceTypeId
        ];
        if ($slug) {
            $where['slug'] = $slug;
        }

        $resourceCollections = $this->resourceCollectionRepository->find($where);

        foreach ($resourceCollections as $resourceCollection) {
            $select = $this->resourceRepository->select()
                ->join(
                    'lookup_resource_collection',
                    'lookup_resource_collection.resource_id = resource.resource_id',
                    []
                )
                ->where([
                    'lookup_resource_collection.resource_collection_id' => $resourceCollection->resource_collection_id
                ])
                ->order([
                    'resource.sequence ASC'
                ]);

            $resources                                         = $this->resourceRepository->find(null, null, $select);
            $faqs[$resourceCollection->resource_collection_id] = [
                'collection' => $resourceCollection,
                'resources'  => $resources
            ];
        }

        return $faqs;
    }
}
