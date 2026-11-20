<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use function trim;

/**
 * A record's own values for its page, such as a share title or image an
 * editor set for that record alone. Unlike the rest of PageDefaults, each
 * wins over the resource's getters; null leaves the page as it is. An
 * image of SITE_IMAGE takes the page's image away, so the site's is shared.
 *
 * @api
 */
final readonly class PageOverrides
{
    /**
     * The image a record gives to say it has no share image of its own, so
     * the page shares the site's, even though the resource has an image.
     */
    public const string SITE_IMAGE = '@site';

    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $imageAlt = null,
    ) {}

    /**
     * Whether the record asks for the site's share image in place of its own.
     */
    public function sharesSiteImage(): bool
    {
        return self::SITE_IMAGE === trim($this->image ?? '');
    }
}
