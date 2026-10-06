<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\TestAsset\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Table;
use Contenir\Resource\Core\Content\SectionAwareInterface;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Override;

use function json_decode;

/**
 * A site entity with a JSON "section" column and an image path, as a site
 * would extend AbstractResourceEntity.
 */
#[Table('resource')]
final class SectionResourceEntity extends AbstractResourceEntity implements SectionAwareInterface
{
    #[Column]
    public ?string $section = null;

    public ?string $imagePath = null;

    #[Override]
    public function getMetaImage(): ?string
    {
        return $this->imagePath;
    }

    #[Override]
    public function getSection(): mixed
    {
        return json_decode((string) $this->section, associative: true);
    }

    #[Override]
    public function hasSection(): bool
    {
        return null !== $this->section && '' !== $this->section;
    }
}
