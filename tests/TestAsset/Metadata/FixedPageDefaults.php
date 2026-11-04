<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\TestAsset\Metadata;

use Contenir\Metadata\MetadataInterface;
use Contenir\Resource\Core\Metadata\PageDefaults;
use Contenir\Resource\Core\Metadata\PageDefaultsInterface;
use Override;

/**
 * The same defaults for every resource, or none.
 */
final readonly class FixedPageDefaults implements PageDefaultsInterface
{
    public function __construct(
        private ?PageDefaults $defaults,
    ) {}

    #[Override]
    public function for(MetadataInterface $resource): ?PageDefaults
    {
        return $this->defaults;
    }
}
