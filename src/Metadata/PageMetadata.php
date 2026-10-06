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
     * The og:type of every page.
     */
    public const string TYPE = 'website';

    /**
     * @param string $url The canonical URL, also used for og:url and twitter:url.
     */
    public function __construct(
        public string $url,
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?DateTimeInterface $updated = null,
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
     * them; tags whose value is not set are left out.
     *
     * @return list<MetaTag>
     */
    public function getMetaTags(): array
    {
        $tags = [
            ['attribute' => 'property', 'key' => 'og:type', 'content' => self::TYPE],
            ['attribute' => 'property', 'key' => 'og:url', 'content' => $this->url],
            ['attribute' => 'property', 'key' => 'twitter:url', 'content' => $this->url],
        ];

        if (null !== $this->title) {
            $tags[] = ['attribute' => 'property', 'key' => 'og:title', 'content' => $this->title];
            $tags[] = ['attribute' => 'property', 'key' => 'twitter:title', 'content' => $this->title];
        }

        if (null !== $this->description) {
            $tags[] = ['attribute' => 'name', 'key' => 'description', 'content' => $this->description];
            $tags[] = ['attribute' => 'property', 'key' => 'og:description', 'content' => $this->description];
            $tags[] = ['attribute' => 'property', 'key' => 'twitter:description', 'content' => $this->description];
        }

        if (null !== $this->image) {
            $tags[] = ['attribute' => 'property', 'key' => 'og:image', 'content' => $this->image];
            $tags[] = ['attribute' => 'property', 'key' => 'twitter:image', 'content' => $this->image];
        }

        if (null !== $this->updated) {
            $tags[] = [
                'attribute' => 'property',
                'key'       => 'og:updated_time',
                'content'   => $this->updated->format(DATE_ATOM),
            ];
        }

        return $tags;
    }
}
