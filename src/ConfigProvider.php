<?php

declare(strict_types=1);

namespace Contenir\Resource\Core;

use Contenir\Resource\Core\Container\PageMetadataBuilderFactory;
use Contenir\Resource\Core\Container\PathImageUrlResolverFactory;
use Contenir\Resource\Core\Container\RepositoryFactory;
use Contenir\Resource\Core\Container\ResourceManagerFactory;
use Contenir\Resource\Core\Container\ResourceSummaryFactory;
use Contenir\Resource\Core\Content\ResourceSummary;
use Contenir\Resource\Core\Metadata\ImageUrlResolverInterface;
use Contenir\Resource\Core\Metadata\MetaTagRenderer;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\Metadata\PathImageUrlResolver;
use Contenir\Resource\Core\Repository\ResourceCollectionRepository;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Contenir\Resource\Core\Repository\ResourceTypeRepository;

/**
 * Registers the framework-neutral services under "dependencies", the key
 * Mezzio and laminas-config-aggregator setups read. It contributes no
 * "contenir_resource" config: every setting has a default in code.
 *
 * @psalm-type ServiceConfig = array{
 *     aliases: array<class-string, class-string>,
 *     invokables: array<class-string, class-string>,
 *     factories: array<class-string, class-string>
 * }
 *
 * @api
 */
final class ConfigProvider
{
    /**
     * @return ServiceConfig
     */
    public function getDependencies(): array
    {
        return [
            'aliases'    => [
                ResourceManagerInterface::class  => ResourceManager::class,
                ImageUrlResolverInterface::class => PathImageUrlResolver::class,
            ],
            'invokables' => [
                MetaTagRenderer::class => MetaTagRenderer::class,
            ],
            'factories'  => [
                ResourceRepository::class           => RepositoryFactory::class,
                ResourceCollectionRepository::class => RepositoryFactory::class,
                ResourceTypeRepository::class       => RepositoryFactory::class,
                ResourceManager::class              => ResourceManagerFactory::class,
                PageMetadataBuilder::class          => PageMetadataBuilderFactory::class,
                PathImageUrlResolver::class         => PathImageUrlResolverFactory::class,
                ResourceSummary::class              => ResourceSummaryFactory::class,
            ],
        ];
    }

    /**
     * @return array{dependencies: ServiceConfig}
     */
    public function __invoke(): array
    {
        return ['dependencies' => $this->getDependencies()];
    }
}
