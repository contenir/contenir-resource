<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Url;

/**
 * A link to a resource or an external URL: the href (null when there is
 * nothing to link to) and the target attribute.
 *
 * @api
 */
final readonly class ResourceLink
{
    public function __construct(
        public ?string $url = null,
        public ?string $target = null,
    ) {}

    /**
     * The [url, target] pair the laminas-mvc ResourceUrl helper returned.
     *
     * @return array{0: string|null, 1: string|null}
     */
    public function toArray(): array
    {
        return [$this->url, $this->target];
    }
}
