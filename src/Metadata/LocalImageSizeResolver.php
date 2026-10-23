<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use Override;

use function getimagesize;
use function is_file;
use function preg_match;
use function realpath;
use function str_starts_with;

use const DIRECTORY_SEPARATOR;

/**
 * Measures a share image stored under the site's web root, such as
 * "/library/project/lg/hero.jpg" in "public". URLs, and paths that leave the
 * root, are not measured.
 *
 * @api
 */
final readonly class LocalImageSizeResolver implements ImageSizeResolverInterface
{
    /**
     * @param string $root The directory image paths are relative to, usually the web root.
     */
    public function __construct(
        private string $root,
    ) {}

    #[Override]
    public function size(string $image): ?array
    {
        $path = $this->path($image);
        if (null === $path) {
            return null;
        }

        $size = getimagesize($path);
        if (false === $size || $size[0] < 1 || $size[1] < 1) {
            return null;
        }

        return [$size[0], $size[1]];
    }

    /**
     * The image's file under the root, or null for a URL, a path outside the
     * root, or a file that does not exist.
     */
    private function path(string $image): ?string
    {
        if (1 === preg_match('~^([a-z][a-z0-9+.\-]*:|//)~i', $image)) {
            return null;
        }

        $root = realpath($this->root);
        $file = false === $root ? false : realpath("{$root}/{$image}");
        if (false === $root || false === $file || ! is_file($file)) {
            return null;
        }

        return str_starts_with($file, $root . DIRECTORY_SEPARATOR) ? $file : null;
    }
}
