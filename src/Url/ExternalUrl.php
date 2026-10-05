<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Url;

use function in_array;
use function preg_match;
use function str_starts_with;
use function strtolower;
use function trim;

/**
 * Normalises an editor-entered link URL.
 *
 * Root-relative and protocol-relative URLs are kept, a URL without a
 * scheme ("example.com/page") gets https://, and only the http, https,
 * mailto and tel schemes are accepted: anything else (javascript:, data:,
 * vbscript:, ...) is rejected, so the result is safe as an href once
 * HTML-escaped.
 *
 * @api
 */
final class ExternalUrl
{
    private const array ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * @return string|null The URL, or null when it is empty or has a scheme that is not allowed.
     */
    public static function normalise(string $url): ?string
    {
        $url = trim($url);
        if ('' === $url) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $matches = [];
        if (1 === preg_match('~^([a-z][a-z0-9+.\-]*):~i', $url, $matches)) {
            return in_array(strtolower($matches[1] ?? ''), self::ALLOWED_SCHEMES, strict: true) ? $url : null;
        }

        return "https://{$url}";
    }
}
