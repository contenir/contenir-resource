<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Integration\Container;

use Contenir\Db\Model\ConfigProvider as DbModelConfigProvider;
use Contenir\Resource\Core\ConfigProvider;
use Contenir\Resource\Core\Content\ResourceSummary;
use Contenir\Resource\Core\Metadata\ImageUrlResolverInterface;
use Contenir\Resource\Core\Metadata\MetaTagRenderer;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\Metadata\PathImageUrlResolver;
use Contenir\Resource\Core\Repository\ResourceTypeRepository;
use Contenir\Resource\Core\ResourceManager;
use Contenir\Resource\Core\ResourceManagerInterface;
use Contenir\Resource\Core\Tests\Trait\SqliteDatabaseTrait;
use Laminas\ServiceManager\ServiceManager;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_merge_recursive;

#[Group('integration')]
final class ContainerWiringTest extends TestCase
{
    use SqliteDatabaseTrait;

    private ServiceManager $container;

    #[Test]
    public function aResourceFromTheDatabaseGetsItsPageMetadata(): void
    {
        $this->insertResource([
            'resource_id'      => 1,
            'title'            => 'About',
            'meta_description' => '<p>All about us</p>',
            'created'          => '2024-01-02 03:04:05',
        ]);

        $resource = $this->container->get(ResourceManagerInterface::class)->findActive(1);
        static::assertNotNull($resource);
        $html = $this->container->get(MetaTagRenderer::class)->render(
            $this->container->get(PageMetadataBuilder::class)->build($resource, 'https://evil.test/about?x=1'),
        );

        static::assertStringContainsString(
            "<title>About</title>\n<link rel=\"canonical\" href=\"https://www.site.test/about\">",
            $html,
        );
        static::assertStringContainsString('<meta name="description" content="All about us">', $html);
        static::assertStringContainsString(
            '<meta property="og:updated_time" content="2024-01-02T03:04:05+00:00">',
            $html,
        );
    }

    #[Test]
    public function everyServiceResolves(): void
    {
        static::assertSame(
            [
                ResourceManager::class,
                ResourceTypeRepository::class,
                PathImageUrlResolver::class,
                MetaTagRenderer::class,
                ResourceSummary::class,
            ],
            [
                $this->container->get(ResourceManagerInterface::class)::class,
                $this->container->get(ResourceTypeRepository::class)::class,
                $this->container->get(ImageUrlResolverInterface::class)::class,
                $this->container->get(MetaTagRenderer::class)::class,
                $this->container->get(ResourceSummary::class)::class,
            ],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpDatabase();
        $dependencies = array_merge_recursive(
            (new DbModelConfigProvider())()['dependencies'],
            (new ConfigProvider())()['dependencies'],
        );
        $dependencies['services'] = [
            'config'                => ['contenir_resource' => ['base_url' => 'https://www.site.test']],
            AdapterInterface::class => $this->adapter,
        ];
        $this->container = new ServiceManager($dependencies);
    }
}
