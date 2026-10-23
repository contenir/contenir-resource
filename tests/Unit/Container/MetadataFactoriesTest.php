<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Container;

use Contenir\Resource\Core\Container\PageMetadataBuilderFactory;
use Contenir\Resource\Core\Container\PathImageUrlResolverFactory;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\ImageSizeResolverInterface;
use Contenir\Resource\Core\Metadata\ImageUrlResolverInterface;
use Contenir\Resource\Core\Metadata\PageMetadata;
use Contenir\Resource\Core\Metadata\PathImageUrlResolver;
use Contenir\Resource\Core\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Resource\Core\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\FixedImageSizes;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\FixedMetadata;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\RecordingImageResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function mb_strlen;
use function str_repeat;

#[Group('unit')]
final class MetadataFactoriesTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidBuilderSettingProvider(): array
    {
        return [
            'article_types not a list'          => [['article_types' => ['a' => 'project']]],
            'article_types not an array'        => [['article_types' => 'project']],
            'article_types with an empty name'  => [['article_types' => ['project', '']]],
            'article_types with a non-string'   => [['article_types' => [1]]],
            'image_alt_from_title not a bool'   => [['image_alt_from_title' => 'yes']],
            'description_ellipsis empty string' => [['description_ellipsis' => '']],
        ];
    }

    #[Test]
    public function theBuilderDefaultsToTheRequestOriginAndOneHundredAndSixtyCharacters(): void
    {
        $builder = (new PageMetadataBuilderFactory())(new ArrayContainer([
            ImageUrlResolverInterface::class => new PathImageUrlResolver(),
        ]));
        $metadata = $builder->build(new FixedMetadata(description: str_repeat('a', times: 170)), 'https://s.test/a');

        static::assertSame(
            ['https://s.test/a', 160, null, null],
            [
                $metadata->url,
                mb_strlen((string) $metadata->description),
                $metadata->siteName,
                $builder->build(new FixedMetadata(
                    title: 'T',
                    image: '/a.jpg',
                ), 'https://s.test/')->imageAlt,
            ],
        );
    }

    #[Test]
    public function theBuilderMeasuresImagesUnderTheImageRoot(): void
    {
        $builder = (new PageMetadataBuilderFactory())(new ArrayContainer([
            'config'                         => ['contenir_resource' => ['image_root' => __DIR__]],
            ImageUrlResolverInterface::class => new PathImageUrlResolver(),
        ]));

        static::assertNull($builder->build(new FixedMetadata(image: '/missing.jpg'), 'https://s.test/')->imageWidth);
    }

    #[Test]
    public function theBuilderPrefersAnImageSizeResolverService(): void
    {
        $sizes   = new FixedImageSizes([640, 480]);
        $builder = (new PageMetadataBuilderFactory())(new ArrayContainer([
            'config'                          => ['contenir_resource' => ['image_root' => '/nowhere']],
            ImageUrlResolverInterface::class  => new PathImageUrlResolver(),
            ImageSizeResolverInterface::class => $sizes,
        ]));

        static::assertSame(640, $builder->build(new FixedMetadata(image: '/a.jpg'), 'https://s.test/')->imageWidth);
    }

    #[Test]
    public function theBuilderReadsArticleTypesAltTextAndEllipsis(): void
    {
        $builder = (new PageMetadataBuilderFactory())(new ArrayContainer([
            'config'                         => [
                'contenir_resource' => [
                    'article_types'        => ['project'],
                    'image_alt_from_title' => true,
                    'description_ellipsis' => '…',
                    'description_length'   => 3,
                ],
            ],
            ImageUrlResolverInterface::class => new PathImageUrlResolver(),
        ]));
        $project = ResourceFactory::make(
            type: 'project',
            title: 'Convoy',
        );

        $metadata = $builder->build(new FixedMetadata(
            title: 'Convoy',
            description: 'one two',
            image: '/a.jpg',
        ), 'https://s.test/');

        static::assertSame(
            [PageMetadata::ARTICLE, 'Convoy', 'one…'],
            [$builder->build($project, 'https://s.test/')->type, $metadata->imageAlt, $metadata->description],
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    #[Test]
    #[DataProvider('invalidBuilderSettingProvider')]
    public function theBuilderRefusesAnInvalidSetting(array $settings): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('contenir_resource');

        (new PageMetadataBuilderFactory())(new ArrayContainer([
            'config'                         => ['contenir_resource' => $settings],
            ImageUrlResolverInterface::class => new PathImageUrlResolver(),
        ]));
    }

    #[Test]
    public function theBuilderUsesTheConfiguredBaseUrlLengthAndResolver(): void
    {
        $builder = (new PageMetadataBuilderFactory())(new ArrayContainer([
            'config'                         => [
                'contenir_resource' => [
                    'base_url'           => 'https://www.site.test',
                    'description_length' => 3,
                    'site_name'          => 'Site',
                ],
            ],
            ImageUrlResolverInterface::class => new RecordingImageResolver(),
        ]));

        $metadata = $builder->build(
            new FixedMetadata(
                description: 'one two',
                image: 'i.jpg',
            ),
            'https://evil.test/about',
        );

        static::assertSame(
            ['https://www.site.test/about', 'one', 'https://www.site.test|i.jpg', 'Site'],
            [$metadata->url, $metadata->description, $metadata->image, $metadata->siteName],
        );
    }

    #[Test]
    public function theImageResolverFallsBackToTheOrigin(): void
    {
        $resolver = (new PathImageUrlResolverFactory())(new ArrayContainer());

        static::assertSame('https://site.test/a.jpg', $resolver->resolve('a.jpg', 'https://site.test'));
    }

    #[Test]
    public function theImageResolverUsesTheConfiguredImageBaseUrl(): void
    {
        $resolver = (new PathImageUrlResolverFactory())(new ArrayContainer([
            'config' => ['contenir_resource' => ['image_base_url' => 'https://img.test']],
        ]));

        static::assertSame('https://img.test/a.jpg', $resolver->resolve('a.jpg', 'https://site.test'));
    }
}
