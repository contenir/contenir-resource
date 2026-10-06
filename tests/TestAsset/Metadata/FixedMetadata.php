<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\TestAsset\Metadata;

use Contenir\Metadata\MetadataInterface;
use DateTimeInterface;
use Override;

/**
 * MetadataInterface with fixed values.
 */
final readonly class FixedMetadata implements MetadataInterface
{
    public function __construct(
        private ?string $title = null,
        private ?string $description = null,
        private ?string $image = null,
        private ?DateTimeInterface $modified = null,
        private ?DateTimeInterface $publish = null,
    ) {}

    #[Override]
    public function getMetaDescription(): ?string
    {
        return $this->description;
    }

    #[Override]
    public function getMetaImage(): ?string
    {
        return $this->image;
    }

    #[Override]
    public function getMetaModified(): ?DateTimeInterface
    {
        return $this->modified;
    }

    #[Override]
    public function getMetaPublish(): ?DateTimeInterface
    {
        return $this->publish;
    }

    #[Override]
    public function getMetaTitle(): ?string
    {
        return $this->title;
    }
}
