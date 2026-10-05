<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use Contenir\Metadata\MetadataInterface;

use function array_key_exists;
use function is_array;
use function is_string;
use function parse_url;
use function rtrim;
use function trim;

use const PHP_URL_PATH;

/**
 * Builds the PageMetadata of a resource for the URL it was requested at.
 *
 * The canonical URL is the request path on the configured base URL, or on
 * the request's own origin when no base URL is configured. Configure one in
 * production: the request origin comes from the Host header, which the
 * client controls. The query string, fragment and any user info are never
 * part of the canonical URL.
 *
 * @api
 */
final readonly class PageMetadataBuilder
{
    /**
     * @param string|null $baseUrl           The site's base URL, for example "https://www.example.com".
     * @param int         $descriptionLength The maximum description length, in characters.
     */
    public function __construct(
        private ImageUrlResolverInterface $images = new PathImageUrlResolver(),
        private ?string $baseUrl = null,
        private int $descriptionLength = 160,
    ) {}

    private static function nonEmpty(?string $value): ?string
    {
        return null === $value || '' === trim($value) ? null : $value;
    }

    /**
     * The scheme, host and port of a URL, or '' when it has no host.
     */
    private static function origin(string $url): string
    {
        $parts = parse_url($url);
        if (! is_array($parts) || ! array_key_exists('host', $parts)) {
            return '';
        }

        $scheme = $parts['scheme'] ?? 'http';
        $port   = array_key_exists('port', $parts) ? ":{$parts['port']}" : '';

        return "{$scheme}://{$parts['host']}{$port}";
    }

    /**
     * @param string $requestUrl The absolute URL the page was requested at.
     */
    public function build(MetadataInterface $resource, string $requestUrl): PageMetadata
    {
        $origin      = null === $this->baseUrl ? self::origin($requestUrl) : rtrim($this->baseUrl, characters: '/');
        $path        = parse_url($requestUrl, PHP_URL_PATH);
        $description = self::nonEmpty($resource->getMetaDescription());
        $image       = self::nonEmpty($resource->getMetaImage());

        return new PageMetadata(
            url: $origin . (is_string($path) ? $path : '/'),
            title: self::nonEmpty($resource->getMetaTitle()),
            description: null === $description
                ? null
                : self::nonEmpty(MetaText::summarise($description, $this->descriptionLength)),
            image: null === $image ? null : $this->images->resolve(trim($image), $origin),
            updated: $resource->getMetaPublish() ?? $resource->getMetaModified(),
        );
    }
}
