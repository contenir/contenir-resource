<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Metadata;

use Contenir\Resource\Core\Metadata\PageMetadata;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_slice;

#[Group('unit')]
final class PageMetadataTest extends TestCase
{
    /**
     * @return array<string, array{int|null, int|null}>
     */
    public static function halfKnownSizeProvider(): array
    {
        return [
            'only the width'  => [1200, null],
            'only the height' => [null, 630],
        ];
    }

    /**
     * @return array<string, array{string|null, string}>
     */
    public static function twitterCardProvider(): array
    {
        return [
            'with an image'    => ['https://example.com/i.jpg', 'summary_large_image'],
            'without an image' => [null, 'summary'],
        ];
    }

    #[DataProvider('halfKnownSizeProvider')]
    #[Test]
    public function aHalfKnownSizeIsLeftOut(?int $width, ?int $height): void
    {
        $metadata = new PageMetadata(
            url: 'https://example.com/a',
            image: 'https://example.com/i.jpg',
            imageWidth: $width,
            imageHeight: $height,
        );

        static::assertSame(
            [
                ['attribute' => 'property', 'key' => 'og:image', 'content' => 'https://example.com/i.jpg'],
                ['attribute' => 'name', 'key' => 'twitter:image', 'content' => 'https://example.com/i.jpg'],
            ],
            array_slice($metadata->getMetaTags(), offset: 4),
        );
    }

    #[Test]
    public function aPageAtAnotherPathKeepsItsOriginAndEveryOtherValue(): void
    {
        $published = new DateTimeImmutable('2024-01-30');
        $metadata  = new PageMetadata(
            'https://example.com:8080/work/convoy/commercial?page=2',
            title: 'Convoy',
            description: 'A service station.',
            image: 'https://example.com/i.jpg',
            updated: $published,
            siteName: 'Example',
            robots: 'noindex',
            imageAlt: 'Convoy',
            imageWidth: 1200,
            imageHeight: 630,
            type: PageMetadata::ARTICLE,
            published: $published,
        );

        static::assertEquals(
            new PageMetadata(
                'https://example.com:8080/work/convoy',
                title: 'Convoy',
                description: 'A service station.',
                image: 'https://example.com/i.jpg',
                updated: $published,
                siteName: 'Example',
                robots: 'noindex',
                imageAlt: 'Convoy',
                imageWidth: 1200,
                imageHeight: 630,
                type: PageMetadata::ARTICLE,
                published: $published,
            ),
            $metadata->withPath('/work/convoy'),
        );
    }

    #[Test]
    public function everyTagIsSetInTheLaminasMvcOrder(): void
    {
        $metadata = new PageMetadata(
            url: 'https://example.com/a',
            title: 'Title',
            description: 'Description',
            image: 'https://example.com/i.jpg',
            updated: new DateTimeImmutable('2024-01-02 03:04:05+10:00'),
        );

        static::assertSame(
            [
                ['attribute' => 'property', 'key' => 'og:type', 'content' => 'website'],
                ['attribute' => 'property', 'key' => 'og:url', 'content' => 'https://example.com/a'],
                ['attribute' => 'name', 'key' => 'twitter:url', 'content' => 'https://example.com/a'],
                ['attribute' => 'name', 'key' => 'twitter:card', 'content' => 'summary_large_image'],
                ['attribute' => 'property', 'key' => 'og:title', 'content' => 'Title'],
                ['attribute' => 'name', 'key' => 'twitter:title', 'content' => 'Title'],
                ['attribute' => 'name', 'key' => 'description', 'content' => 'Description'],
                ['attribute' => 'property', 'key' => 'og:description', 'content' => 'Description'],
                ['attribute' => 'name', 'key' => 'twitter:description', 'content' => 'Description'],
                ['attribute' => 'property', 'key' => 'og:image', 'content' => 'https://example.com/i.jpg'],
                ['attribute' => 'name', 'key' => 'twitter:image', 'content' => 'https://example.com/i.jpg'],
                ['attribute' => 'property', 'key' => 'og:updated_time', 'content' => '2024-01-02T03:04:05+10:00'],
            ],
            $metadata->getMetaTags(),
        );
    }

    #[Test]
    public function imageDetailsWithoutAnImageAreLeftOut(): void
    {
        $metadata = new PageMetadata(
            url: 'https://example.com/a',
            imageAlt: 'Alt',
            imageWidth: 1,
            imageHeight: 1,
        );

        static::assertCount(4, $metadata->getMetaTags());
    }

    #[Test]
    public function onlyTheTypeUrlAndCardTagsAreSetWithoutValues(): void
    {
        static::assertSame(
            [
                ['attribute' => 'property', 'key' => 'og:type', 'content' => 'website'],
                ['attribute' => 'property', 'key' => 'og:url', 'content' => 'https://example.com/a'],
                ['attribute' => 'name', 'key' => 'twitter:url', 'content' => 'https://example.com/a'],
                ['attribute' => 'name', 'key' => 'twitter:card', 'content' => 'summary'],
            ],
            (new PageMetadata('https://example.com/a'))->getMetaTags(),
        );
    }

    #[Test]
    public function theCanonicalLinkIsTheUrl(): void
    {
        static::assertSame(
            [['rel' => 'canonical', 'href' => 'https://example.com/a']],
            (new PageMetadata('https://example.com/a'))->getLinks(),
        );
    }

    #[Test]
    public function theSiteNameRobotsAndImageDetailsFollowTheCard(): void
    {
        $metadata = new PageMetadata(
            url: 'https://example.com/a',
            image: 'https://example.com/i.jpg',
            siteName: 'Example',
            robots: 'noindex, follow',
            imageAlt: 'The workshop',
            imageWidth: 1200,
            imageHeight: 630,
        );

        static::assertSame(
            [
                ['attribute' => 'property', 'key' => 'og:type', 'content' => 'website'],
                ['attribute' => 'property', 'key' => 'og:url', 'content' => 'https://example.com/a'],
                ['attribute' => 'name', 'key' => 'twitter:url', 'content' => 'https://example.com/a'],
                ['attribute' => 'name', 'key' => 'twitter:card', 'content' => 'summary_large_image'],
                ['attribute' => 'property', 'key' => 'og:site_name', 'content' => 'Example'],
                ['attribute' => 'name', 'key' => 'robots', 'content' => 'noindex, follow'],
                ['attribute' => 'property', 'key' => 'og:image', 'content' => 'https://example.com/i.jpg'],
                ['attribute' => 'property', 'key' => 'og:image:width', 'content' => '1200'],
                ['attribute' => 'property', 'key' => 'og:image:height', 'content' => '630'],
                ['attribute' => 'property', 'key' => 'og:image:alt', 'content' => 'The workshop'],
                ['attribute' => 'name', 'key' => 'twitter:image', 'content' => 'https://example.com/i.jpg'],
                ['attribute' => 'name', 'key' => 'twitter:image:alt', 'content' => 'The workshop'],
            ],
            $metadata->getMetaTags(),
        );
    }

    #[Test]
    #[DataProvider('twitterCardProvider')]
    public function theTwitterCardIsALargeImageOnlyWhenThereIsAnImage(?string $image, string $card): void
    {
        static::assertContains(
            ['attribute' => 'name', 'key' => 'twitter:card', 'content' => $card],
            (new PageMetadata('https://example.com/a', image: $image))->getMetaTags(),
        );
    }
}
