<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use function ltrim;
use function str_contains;
use function trim;

/**
 * What a site's section map adds to one resource's page: values for what
 * the resource's own getters leave empty, and what getters have no say in,
 * such as the robots directive, a canonical address and the schema type.
 * A record's own overrides, such as a share image set for it alone, win
 * over the getters. Every value is optional; null leaves the page as the
 * getters make it.
 *
 * @api
 */
final readonly class PageDefaults
{
    /**
     * @param string|null $canonical An absolute URL, or a path on the page's origin such as "/work/convoy"
     * @param string|null $robots    The robots meta tag's content, such as "noindex, follow"
     * @param string|null $schema    The page's main schema.org type, such as "CreativeWork"
     *
     * @mago-expect lint:excessive-parameter-list Defaults are built with named arguments; all are optional.
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $imageAlt = null,
        public ?string $canonical = null,
        public ?string $robots = null,
        public ?string $schema = null,
        public PageOverrides $overrides = new PageOverrides(),
    ) {}

    private static function text(?string $value): ?string
    {
        return null === $value || '' === trim($value) ? null : $value;
    }

    /**
     * The canonical address on $origin, such as "https://www.example.com":
     * an absolute URL as it is, a path joined to the origin, or null when
     * there is none.
     */
    public function canonicalOn(string $origin): ?string
    {
        $canonical = trim($this->canonical ?? '');
        if ('' === $canonical || str_contains($canonical, '://')) {
            return '' === $canonical ? null : $canonical;
        }

        return $origin . '/' . ltrim($canonical, characters: '/');
    }

    public function description(?string $own): ?string
    {
        return self::text($this->overrides->description) ?? self::text($own) ?? self::text($this->description);
    }

    public function image(?string $own): ?string
    {
        return self::text($this->overrides->image) ?? self::text($own) ?? self::text($this->image);
    }

    /**
     * The share image's alt text: the record's own override, else this
     * default's, else $fallback, such as the page's title for a site that
     * takes alt text from titles.
     */
    public function imageAlt(?string $fallback): ?string
    {
        return self::text($this->overrides->imageAlt) ?? self::text($this->imageAlt) ?? $fallback;
    }

    public function robots(): ?string
    {
        return self::text($this->robots);
    }

    public function schema(): ?string
    {
        return self::text($this->schema);
    }

    /**
     * The record's own override, else $own, a resource getter's value, unless
     * it is empty, else this default's.
     */
    public function title(?string $own): ?string
    {
        return self::text($this->overrides->title) ?? self::text($own) ?? self::text($this->title);
    }
}
