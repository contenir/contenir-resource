<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Metadata;

use Contenir\Resource\Core\Metadata\PageMetadata;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PageMetadataTest extends TestCase
{
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
    #[DataProvider('twitterCardProvider')]
    public function theTwitterCardIsALargeImageOnlyWhenThereIsAnImage(?string $image, string $card): void
    {
        static::assertContains(
            ['attribute' => 'name', 'key' => 'twitter:card', 'content' => $card],
            (new PageMetadata('https://example.com/a', image: $image))->getMetaTags(),
        );
    }
}
