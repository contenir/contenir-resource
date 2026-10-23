<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Metadata;

use Contenir\Resource\Core\Metadata\PageMetadata;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\FixedImageSizes;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\FixedMetadata;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\RecordingImageResolver;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The builder's article types, image size, image alt and ellipsis.
 */
#[Group('unit')]
final class PageMetadataBuilderOptionsTest extends TestCase
{
    #[Test]
    public function aDescriptionCutShortEndsWithTheEllipsis(): void
    {
        $metadata = (new PageMetadataBuilder(
            descriptionLength: 12,
            descriptionEllipsis: '…',
        ))->build(
            new FixedMetadata(description: 'Conceived as, an extension'),
            'https://s.test/',
        );

        static::assertSame('Conceived…', $metadata->description);
    }

    #[Test]
    public function anImageThatCannotBeMeasuredHasNoSize(): void
    {
        $metadata = (new PageMetadataBuilder(imageSizes: new FixedImageSizes(null)))->build(
            new FixedMetadata(image: '/a.jpg'),
            'https://s.test/',
        );

        static::assertSame(
            ['https://s.test/a.jpg', null, null],
            [$metadata->image, $metadata->imageWidth, $metadata->imageHeight],
        );
    }

    #[Test]
    public function anImageTheResolverLeavesOutIsNotMeasured(): void
    {
        $sizes    = new FixedImageSizes();
        $metadata = (new PageMetadataBuilder(imageSizes: $sizes))->build(
            new FixedMetadata(image: 'javascript:alert(1)'),
            'https://s.test/',
        );

        static::assertSame([null, null, []], [$metadata->image, $metadata->imageWidth, $sizes->calls]);
    }

    #[Test]
    public function aPageWithoutAnImageHasNoImageAltText(): void
    {
        $metadata = (new PageMetadataBuilder(imageAltFromTitle: true))->build(
            new FixedMetadata(title: 'Convoy'),
            'https://s.test/',
        );

        static::assertNull($metadata->imageAlt);
    }

    #[Test]
    public function aResourceOfAnArticleTypeIsAnArticleDatedFromItsPublishDate(): void
    {
        $project = ResourceFactory::make(
            type: 'project',
            title: 'Convoy',
        );
        $project->created = new DateTimeImmutable('2024-01-30 14:09:33');

        $metadata = (new PageMetadataBuilder(articleTypes: ['project', 'news']))->build(
            $project,
            'https://s.test/work/convoy',
        );

        static::assertSame([PageMetadata::ARTICLE, $project->created], [$metadata->type, $metadata->published]);
    }

    #[Test]
    public function aResourceOfAnyOtherTypeIsAWebsitePage(): void
    {
        $page          = ResourceFactory::make(type: 'page');
        $page->created = new DateTimeImmutable('2024-01-30');

        $metadata = (new PageMetadataBuilder(articleTypes: ['project']))->build($page, 'https://s.test/about');

        static::assertSame([PageMetadata::TYPE, null], [$metadata->type, $metadata->published]);
    }

    #[Test]
    public function metadataThatIsNotAResourceIsAWebsitePage(): void
    {
        $metadata = (new PageMetadataBuilder(articleTypes: ['project']))->build(
            new FixedMetadata(publish: new DateTimeImmutable('2024-01-30')),
            'https://s.test/',
        );

        static::assertSame([PageMetadata::TYPE, null], [$metadata->type, $metadata->published]);
    }

    #[Test]
    public function theImagesAltTextIsThePageTitleOnlyWhenAskedFor(): void
    {
        $page = new FixedMetadata(
            title: 'Convoy',
            image: '/a.jpg',
        );

        static::assertSame(['Convoy', null], [
            (new PageMetadataBuilder(imageAltFromTitle: true))->build($page, 'https://s.test/')->imageAlt,
            (new PageMetadataBuilder())->build($page, 'https://s.test/')->imageAlt,
        ]);
    }

    #[Test]
    public function theShareImageIsMeasuredByItsPath(): void
    {
        $sizes    = new FixedImageSizes([1800, 1199]);
        $metadata = (new PageMetadataBuilder(new RecordingImageResolver(), imageSizes: $sizes))->build(
            new FixedMetadata(image: ' /library/hero.jpg '),
            'https://s.test/',
        );

        static::assertSame(
            [1800, 1199, ['/library/hero.jpg']],
            [$metadata->imageWidth, $metadata->imageHeight, $sizes->calls],
        );
    }
}
