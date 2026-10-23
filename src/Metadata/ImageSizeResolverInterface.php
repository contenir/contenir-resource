<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

/**
 * Measures a resource's share image, for og:image:width and og:image:height.
 *
 * @api
 */
interface ImageSizeResolverInterface
{
    /**
     * @param string $image The non-empty path from MetadataInterface::getMetaImage().
     *
     * @return array{int<1, max>, int<1, max>}|null The width and height in pixels, or null when unknown.
     */
    public function size(string $image): ?array;
}
