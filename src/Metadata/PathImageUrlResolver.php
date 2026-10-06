<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use Override;

use function ltrim;
use function preg_match;
use function rtrim;
use function str_starts_with;
use function strstr;

/**
 * The default image resolver. http and https URLs are used as they are, a
 * protocol-relative URL gets the origin's scheme, and a path is appended to
 * the configured image base URL (or else the site origin). Any other scheme,
 * such as javascript: or data:, is rejected.
 *
 * @api
 */
final readonly class PathImageUrlResolver implements ImageUrlResolverInterface
{
    public function __construct(
        private ?string $baseUrl = null,
    ) {}

    #[Override]
    public function resolve(string $image, string $origin): ?string
    {
        if (1 === preg_match('~^https?://~i', $image)) {
            return $image;
        }

        if (str_starts_with($image, '//')) {
            $scheme = strstr($origin, needle: '://', before_needle: true);

            return (false === $scheme ? 'https' : $scheme) . ':' . $image;
        }

        if (1 === preg_match('~^[a-z][a-z0-9+.\-]*:~i', $image)) {
            return null;
        }

        return rtrim($this->baseUrl ?? $origin, characters: '/') . '/' . ltrim($image, characters: '/');
    }
}
