<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Metadata;

use Contenir\Resource\Core\Metadata\PageMetadata;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_column;
use function array_combine;
use function array_key_exists;

/**
 * The og:type and article dates of a PageMetadata.
 */
#[Group('unit')]
final class ArticleMetadataTest extends TestCase
{
    /**
     * @return array<string, string>
     */
    private static function tags(PageMetadata $metadata): array
    {
        $tags = $metadata->getMetaTags();

        return array_combine(array_column($tags, 'key'), array_column($tags, 'content'));
    }

    #[Test]
    public function anArticleCarriesItsPublishAndChangeDates(): void
    {
        $tags = self::tags(new PageMetadata(
            url: 'https://s.test/news/a',
            updated: new DateTimeImmutable('2024-02-01T09:00:00+00:00'),
            type: PageMetadata::ARTICLE,
            published: new DateTimeImmutable('2024-01-30T14:09:33+00:00'),
        ));

        static::assertSame(
            [
                'article',
                'https://s.test/news/a',
                '2024-02-01T09:00:00+00:00',
                '2024-01-30T14:09:33+00:00',
                '2024-02-01T09:00:00+00:00',
            ],
            [
                $tags['og:type'],
                $tags['og:url'],
                $tags['og:updated_time'],
                $tags['article:published_time'],
                $tags['article:modified_time'],
            ],
        );
    }

    #[Test]
    public function anArticleNotChangedSinceItWasPublishedHasNoChangeDate(): void
    {
        $published = new DateTimeImmutable('2024-01-30T14:09:33+00:00');
        $tags      = self::tags(new PageMetadata(
            url: 'https://s.test/news/a',
            updated: $published,
            type: PageMetadata::ARTICLE,
            published: $published,
        ));

        static::assertSame(
            ['2024-01-30T14:09:33+00:00', false],
            [$tags['article:published_time'], array_key_exists('article:modified_time', $tags)],
        );
    }

    #[Test]
    public function anArticleWithoutAPublishDateHasNoDates(): void
    {
        $tags = self::tags(new PageMetadata(
            url: 'https://s.test/news/a',
            updated: new DateTimeImmutable('2024-02-01'),
            type: PageMetadata::ARTICLE,
        ));

        static::assertSame([false, false], [
            array_key_exists('article:published_time', $tags),
            array_key_exists('article:modified_time', $tags),
        ]);
    }

    #[Test]
    public function anyOpenGraphTypeIsWritten(): void
    {
        static::assertSame(
            'profile',
            self::tags(new PageMetadata(
                url: 'https://s.test/',
                type: 'profile',
            ))['og:type'],
        );
    }

    #[Test]
    public function aPageThatIsNotAnArticleHasNoArticleDates(): void
    {
        $tags = self::tags(new PageMetadata(
            url: 'https://s.test/about',
            updated: new DateTimeImmutable('2024-02-01'),
            published: new DateTimeImmutable('2024-01-01'),
        ));

        static::assertSame(['website', false], [$tags['og:type'], array_key_exists('article:published_time', $tags)]);
    }
}
