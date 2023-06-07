<?php

namespace Contenir\Resource;

use Contenir\Db\Model\Repository\Factory\RepositoryFactory;
use Contenir\Resource\Model;
use Contenir\Resource\View\Helper;
use Laminas\ServiceManager\Factory\InvokableFactory;

class Module
{
    /**
     * Retrieve default laminas-paginator config for laminas-mvc context.
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'resource' => [
                'repository' => [
                    'resource'            => Model\Repository\BaseResourceRepository::class,
                    'resource_collection' => Model\Repository\BaseResourceCollectionRepository::class,
                    'resource_type'       => Model\Repository\BaseResourceTypeRepository::class,
                ]
            ],
            'controller_plugins' => [
                'aliases' => [
                    'resource' => Controller\Plugin\ResourcePlugin::class,
                    'Resource' => Controller\Plugin\ResourcePlugin::class
                ],
                'factories' => [
                    Controller\Plugin\ResourcePlugin::class => Controller\Plugin\ResourcePluginFactory::class,
                ]
            ],
            'service_manager' => [
                'aliases' => [
                    'resource'            => Model\Repository\BaseResourceRepository::class,
                    'resource_collection' => Model\Repository\BaseResourceCollectionRepository::class,
                    'resource_type'       => Model\Repository\BaseResourceTypeRepository::class,
                ],
                'factories' => [
                    ResourceManager::class                                   => ResourceManagerFactory::class,
                    Model\Entity\BaseResourceEntity::class                   => InvokableFactory::class,
                    Model\Entity\BaseResourceCollectionEntity::class         => InvokableFactory::class,
                    Model\Entity\BaseResourceTypeEntity::class               => InvokableFactory::class,
                    Model\Repository\BaseResourceRepository::class           => RepositoryFactory::class,
                    Model\Repository\BaseResourceCollectionRepository::class => RepositoryFactory::class,
                    Model\Repository\BaseResourceTypeRepository::class       => RepositoryFactory::class
                ]
            ],
            'view_helpers' => [
                'aliases' => [
                    'resource'        => Helper\Resource::class,
                    'Resource'        => Helper\Resource::class,
                    'resourceContent' => Helper\ResourceContent::class,
                    'ResourceContent' => Helper\ResourceContent::class,
                    'resourceMeta'    => Helper\ResourceMeta::class,
                    'ResourceMeta'    => Helper\ResourceMeta::class
                ],
                'factories' => [
                    Helper\Resource::class        => Helper\ResourceFactory::class,
                    Helper\ResourceContent::class => InvokableFactory::class,
                    Helper\ResourceMeta::class    => InvokableFactory::class
                ],
            ],
        ];
    }
}
