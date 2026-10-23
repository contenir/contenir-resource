<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\TestAsset\Metadata;

use Contenir\Resource\Core\Metadata\ImageSizeResolverInterface;
use Override;

/**
 * Measures every image at one size, or at none, and records what it was asked.
 */
final class FixedImageSizes implements ImageSizeResolverInterface
{
    /** @var list<string> */
    public array $calls = [];

    /**
     * @param array{int<1, max>, int<1, max>}|null $size
     */
    public function __construct(
        private readonly ?array $size = [1200, 630],
    ) {}

    #[Override]
    public function size(string $image): ?array
    {
        $this->calls[] = $image;

        return $this->size;
    }
}
