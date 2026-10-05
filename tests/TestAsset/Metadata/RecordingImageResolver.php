<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\TestAsset\Metadata;

use Contenir\Resource\Core\Metadata\ImageUrlResolverInterface;
use Override;

/**
 * Resolves every image to "<origin>|<image>" and records the calls.
 */
final class RecordingImageResolver implements ImageUrlResolverInterface
{
    /** @var list<array{string, string}> */
    public array $calls = [];

    #[Override]
    public function resolve(string $image, string $origin): ?string
    {
        $this->calls[] = [$image, $origin];

        return "{$origin}|{$image}";
    }
}
