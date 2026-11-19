<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use function in_array;
use function parse_url;
use function strtolower;
use function trim;

use const PHP_URL_HOST;
use const PHP_URL_SCHEME;

/**
 * The links a PageMetadata keeps for its author: their own page and their
 * other profiles, as absolute http(s) addresses, each once.
 *
 * @internal
 */
final class AuthorLinks
{
    /**
     * Their own page, or null when it is no web address.
     */
    public static function page(?string $url): ?string
    {
        $url    = trim($url ?? '');
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host   = (string) parse_url($url, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], strict: true) && '' !== $host ? $url : null;
    }

    /**
     * The profiles that are web addresses, in order, each once, without their own page.
     *
     * @param iterable<?string> $profiles
     * @return list<string>
     */
    public static function profiles(iterable $profiles, ?string $page): array
    {
        $links = [];
        foreach ($profiles as $profile) {
            $profile = self::page($profile);
            if (null !== $profile && $profile !== $page && ! in_array($profile, $links, strict: true)) {
                $links[] = $profile;
            }
        }

        return $links;
    }
}
