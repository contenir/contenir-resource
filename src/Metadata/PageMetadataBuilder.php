<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use Contenir\Metadata\MetadataInterface;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;

use function in_array;
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
 * A resource whose type is one of the article types is an article: its
 * og:type is "article", with its publish and change dates.
 *
 * @api
 */
final readonly class PageMetadataBuilder
{
    /**
     * @param string|null $baseUrl           The site's base URL, for example "https://www.example.com".
     * @param int         $descriptionLength The maximum description length, in characters.
     * @param string|null $siteName          The site's name, for og:site_name on every page.
     * @param list<string> $articleTypes      The resource types that are articles.
     * @param ImageSizeResolverInterface|null $imageSizes Measures the share image, for its width and height.
     * @param bool        $imageAltFromTitle  Whether the share image's alt text is the page's title.
     * @param string      $descriptionEllipsis What a description cut short ends with, such as "…".
     *
     * @mago-expect lint:excessive-parameter-list The builder is configured with named arguments; all are optional.
     */
    public function __construct(
        private ImageUrlResolverInterface $images = new PathImageUrlResolver(),
        private ?string $baseUrl = null,
        private int $descriptionLength = 160,
        private ?string $siteName = null,
        private array $articleTypes = [],
        private ?ImageSizeResolverInterface $imageSizes = null,
        private bool $imageAltFromTitle = false,
        private string $descriptionEllipsis = '',
    ) {}

    private static function nonEmpty(?string $value): ?string
    {
        return null === $value || '' === trim($value) ? null : $value;
    }

    /**
     * @param string $requestUrl The absolute URL the page was requested at.
     */
    public function build(MetadataInterface $resource, string $requestUrl): PageMetadata
    {
        $origin = null === $this->baseUrl
            ? RequestOrigin::of($requestUrl)
            : rtrim($this->baseUrl, characters: '/');
        $path        = parse_url($requestUrl, PHP_URL_PATH);
        $title       = self::nonEmpty($resource->getMetaTitle());
        $description = self::nonEmpty($resource->getMetaDescription());
        $image       = ShareImage::resolve($resource->getMetaImage(), $origin, $this->images, $this->imageSizes);
        $article     = $this->isArticle($resource);

        return new PageMetadata(
            url: $origin . (is_string($path) ? $path : '/'),
            title: $title,
            description: null === $description
                ? null
                : self::nonEmpty(MetaText::summarise(
                    $description,
                    $this->descriptionLength,
                    $this->descriptionEllipsis,
                )),
            image: $image?->url,
            updated: $resource->getMetaModified() ?? $resource->getMetaPublish(),
            siteName: self::nonEmpty($this->siteName),
            imageAlt: null !== $image && $this->imageAltFromTitle ? $title : null,
            imageWidth: $image?->width,
            imageHeight: $image?->height,
            type: $article ? PageMetadata::ARTICLE : PageMetadata::TYPE,
            published: $article ? $resource->getMetaPublish() : null,
        );
    }

    private function isArticle(MetadataInterface $resource): bool
    {
        return $resource instanceof AbstractResourceEntity
            && in_array($resource->getType(), $this->articleTypes, strict: true);
    }
}
