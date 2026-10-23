<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use DateTimeInterface;

use const DATE_ATOM;

/**
 * The resolved head metadata of one page: what the <title>, canonical link,
 * description, Open Graph and Twitter tags are rendered from. Every value is
 * plain text; renderers escape it.
 *
 * @psalm-type MetaTag = array{attribute: 'name'|'property', key: string, content: string}
 *
 * @api
 */
final readonly class PageMetadata
{
    /**
     * The og:type of a page that is not an article.
     */
    public const string TYPE = 'website';

    /**
     * The og:type of an article, which also gets article:published_time and
     * article:modified_time.
     */
    public const string ARTICLE = 'article';

    /**
     * @param string  $url         The canonical URL, also used for og:url and twitter:url.
     * @param ?string $siteName    The site's name, for og:site_name.
     * @param ?string $robots      The robots meta tag's content, such as "noindex, follow".
     * @param ?string $imageAlt    What the image shows, for og:image:alt and twitter:image:alt.
     * @param ?int    $imageWidth  The image's width in pixels; written with its height.
     * @param ?int    $imageHeight The image's height in pixels; written with its width.
     * @param string  $type        The og:type: TYPE, ARTICLE or another Open Graph type.
     * @param ?DateTimeInterface $published When an article was first published, for article:published_time.
     *
     * @mago-expect lint:excessive-parameter-list Metadata is built with named arguments; all but the URL are optional.
     */
    public function __construct(
        public string $url,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?DateTimeInterface $updated = null,
        public ?string $siteName = null,
        public ?string $robots = null,
        public ?string $imageAlt = null,
        public ?int $imageWidth = null,
        public ?int $imageHeight = null,
        public string $type = self::TYPE,
        public ?DateTimeInterface $published = null,
    ) {}

    /**
     * @return list<array{rel: string, href: string}>
     */
    public function getLinks(): array
    {
        return [['rel' => 'canonical', 'href' => $this->url]];
    }

    /**
     * The meta tags, in the order the laminas-mvc ResourceMeta helper added
     * them with twitter:card after twitter:url; tags whose value is not set
     * are left out. Open Graph tags use the property attribute and Twitter
     * tags the name attribute.
     *
     * @return list<MetaTag>
     */
    public function getMetaTags(): array
    {
        $tags = [
            ['attribute' => 'property', 'key' => 'og:type', 'content' => $this->type],
            ['attribute' => 'property', 'key' => 'og:url', 'content' => $this->url],
            ['attribute' => 'name', 'key' => 'twitter:url', 'content' => $this->url],
            [
                'attribute' => 'name',
                'key'       => 'twitter:card',
                'content'   => null === $this->image ? 'summary' : 'summary_large_image',
            ],
        ];

        if (null !== $this->siteName) {
            $tags[] = ['attribute' => 'property', 'key' => 'og:site_name', 'content' => $this->siteName];
        }

        if (null !== $this->robots) {
            $tags[] = ['attribute' => 'name', 'key' => 'robots', 'content' => $this->robots];
        }

        if (null !== $this->title) {
            $tags[] = ['attribute' => 'property', 'key' => 'og:title', 'content' => $this->title];
            $tags[] = ['attribute' => 'name', 'key' => 'twitter:title', 'content' => $this->title];
        }

        if (null !== $this->description) {
            $tags[] = ['attribute' => 'name', 'key' => 'description', 'content' => $this->description];
            $tags[] = ['attribute' => 'property', 'key' => 'og:description', 'content' => $this->description];
            $tags[] = ['attribute' => 'name', 'key' => 'twitter:description', 'content' => $this->description];
        }

        if (null !== $this->image) {
            $tags = [
                ...$tags,
                ...PageMetadataTags::image($this->image, $this->imageWidth, $this->imageHeight, $this->imageAlt),
            ];
        }

        if (null !== $this->updated) {
            $tags[] = [
                'attribute' => 'property',
                'key'       => 'og:updated_time',
                'content'   => $this->updated->format(DATE_ATOM),
            ];
        }

        return (
            self::ARTICLE === $this->type
                ? [...$tags, ...PageMetadataTags::article($this->published, $this->updated)]
                : $tags
        );
    }

    /**
     * The same page at another path on its origin, such as an article's own
     * address when it was requested through a listing's.
     *
     * @param string $path An absolute path, such as "/work/convoy".
     */
    public function withPath(string $path): self
    {
        return new self(
            url: RequestOrigin::of($this->url) . $path,
            title: $this->title,
            description: $this->description,
            image: $this->image,
            updated: $this->updated,
            siteName: $this->siteName,
            robots: $this->robots,
            imageAlt: $this->imageAlt,
            imageWidth: $this->imageWidth,
            imageHeight: $this->imageHeight,
            type: $this->type,
            published: $this->published,
        );
    }
}
