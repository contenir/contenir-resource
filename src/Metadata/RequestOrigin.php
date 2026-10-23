<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use function array_key_exists;
use function is_array;
use function parse_url;

/**
 * The scheme, host and port of a requested URL.
 *
 * @internal
 */
final class RequestOrigin
{
    /**
     * The origin, or '' when the URL has no host.
     */
    public static function of(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ! array_key_exists('host', $parts)) {
            return '';
        }

        $scheme = $parts['scheme'] ?? 'http';
        $port   = array_key_exists('port', $parts) ? ":{$parts['port']}" : '';

        return "{$scheme}://{$parts['host']}{$port}";
    }
}
