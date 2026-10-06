<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit;

use Contenir\Resource\Core\ConfigProvider;
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
use Contenir\Resource\Core\ResourceManager;
use Contenir\Resource\Core\ResourceManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function contributesOnlyDependencies(): void
    {
        $provider = new ConfigProvider();

        static::assertSame(['dependencies' => $provider->getDependencies()], $provider());
    }

    #[Test]
    public function registersEveryService(): void
    {
        static::assertSame(
            [
                'aliases'    => [
                    ResourceManagerInterface::class  => ResourceManager::class,
                    ImageUrlResolverInterface::class => PathImageUrlResolver::class,
                ],
                'invokables' => [MetaTagRenderer::class => MetaTagRenderer::class],
                'factories'  => [
                    ResourceRepository::class           => RepositoryFactory::class,
                    ResourceCollectionRepository::class => RepositoryFactory::class,
                    ResourceTypeRepository::class       => RepositoryFactory::class,
                    ResourceManager::class              => ResourceManagerFactory::class,
                    PageMetadataBuilder::class          => PageMetadataBuilderFactory::class,
                    PathImageUrlResolver::class         => PathImageUrlResolverFactory::class,
                    ResourceSummary::class              => ResourceSummaryFactory::class,
                ],
            ],
            (new ConfigProvider())->getDependencies(),
        );
    }
}
