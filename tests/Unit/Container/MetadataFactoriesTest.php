<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Container;

use Contenir\Resource\Core\Container\PageMetadataBuilderFactory;
use Contenir\Resource\Core\Container\PathImageUrlResolverFactory;
use Contenir\Resource\Core\Metadata\ImageUrlResolverInterface;
use Contenir\Resource\Core\Metadata\PathImageUrlResolver;
use Contenir\Resource\Core\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\FixedMetadata;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\RecordingImageResolver;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function mb_strlen;
use function str_repeat;

#[Group('unit')]
final class MetadataFactoriesTest extends TestCase
{
    #[Test]
    public function theBuilderDefaultsToTheRequestOriginAndOneHundredAndSixtyCharacters(): void
    {
        $builder = (new PageMetadataBuilderFactory())(new ArrayContainer([
            ImageUrlResolverInterface::class => new PathImageUrlResolver(),
        ]));
        $metadata = $builder->build(new FixedMetadata(description: str_repeat('a', times: 170)), 'https://s.test/a');

        static::assertSame(['https://s.test/a', 160], [$metadata->url, mb_strlen((string) $metadata->description)]);
    }

    #[Test]
    public function theBuilderUsesTheConfiguredBaseUrlLengthAndResolver(): void
    {
        $builder = (new PageMetadataBuilderFactory())(new ArrayContainer([
            'config'                         => [
                'contenir_resource' => ['base_url' => 'https://www.site.test', 'description_length' => 3],
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
            ['https://www.site.test/about', 'one', 'https://www.site.test|i.jpg'],
            [$metadata->url, $metadata->description, $metadata->image],
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
