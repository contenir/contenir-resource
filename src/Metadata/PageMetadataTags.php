<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use DateTimeInterface;

use const DATE_ATOM;

/**
 * The image and article tags of a PageMetadata.
 *
 * @psalm-import-type MetaTag from PageMetadata
 *
 * @internal
 */
final class PageMetadataTags
{
    /**
     * An article's dates: when it was published, and when it last changed if
     * that was later.
     *
     * @return list<MetaTag>
     */
    public static function article(?DateTimeInterface $published, ?DateTimeInterface $updated): array
    {
        $tags = [];
        if (null !== $published) {
            $tags[] = [
                'attribute' => 'property',
                'key'       => 'article:published_time',
                'content'   => $published->format(DATE_ATOM),
            ];
        }

        if (null !== $published && null !== $updated && $updated > $published) {
            $tags[] = [
                'attribute' => 'property',
                'key'       => 'article:modified_time',
                'content'   => $updated->format(DATE_ATOM),
            ];
        }

        return $tags;
    }

    /**
     * The image's tags: its URL, its size when both sides are known, and
     * what it shows.
     *
     * @return list<MetaTag>
     */
    public static function image(string $image, ?int $width, ?int $height, ?string $alt): array
    {
        $tags = [['attribute' => 'property', 'key' => 'og:image', 'content' => $image]];
        if (null !== $width && null !== $height) {
            $tags[] = ['attribute' => 'property', 'key' => 'og:image:width', 'content' => (string) $width];
            $tags[] = ['attribute' => 'property', 'key' => 'og:image:height', 'content' => (string) $height];
        }

        if (null !== $alt) {
            $tags[] = ['attribute' => 'property', 'key' => 'og:image:alt', 'content' => $alt];
        }

        $tags[] = ['attribute' => 'name', 'key' => 'twitter:image', 'content' => $image];
        if (null !== $alt) {
            $tags[] = ['attribute' => 'name', 'key' => 'twitter:image:alt', 'content' => $alt];
        }

        return $tags;
    }
}
