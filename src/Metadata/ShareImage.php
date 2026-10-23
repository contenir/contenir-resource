<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use function trim;

/**
 * A page's share image: its URL and, when it could be measured, its size.
 *
 * @internal
 */
final readonly class ShareImage
{
    private function __construct(
        public string $url,
        public ?int $width = null,
        public ?int $height = null,
    ) {}

    /**
     * The image at a resource's image path, or null when there is no path or
     * the resolver leaves it out.
     */
    public static function resolve(
        ?string $path,
        string $origin,
        ImageUrlResolverInterface $urls,
        ?ImageSizeResolverInterface $sizes,
    ): ?self {
        $path = null === $path ? '' : trim($path);
        $url  = '' === $path ? null : $urls->resolve($path, $origin);
        if (null === $url) {
            return null;
        }

        $size = $sizes?->size($path);

        return new self($url, $size[0] ?? null, $size[1] ?? null);
    }
}
