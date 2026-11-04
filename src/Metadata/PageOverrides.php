<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

/**
 * A record's own values for its page, such as a share title or image an
 * editor set for that record alone. Unlike the rest of PageDefaults, each
 * wins over the resource's getters; null leaves the page as it is.
 *
 * @api
 */
final readonly class PageOverrides
{
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public ?string $imageAlt = null,
    ) {}
}
