<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;

/**
 * Base class for Contenir resource collection rows (the
 * "resource_collection" table): grouped content such as product categories.
 *
 * Extend it with a final class carrying #[Table('resource_collection')]
 * (or use ResourceCollectionEntity) to add relations.
 *
 * @api
 */
abstract class AbstractResourceCollectionEntity
{
    #[Id(generated: true)]
    #[Column('resource_collection_id')]
    public ?int $resourceCollectionId = null;

    #[Column('resource_type_id')]
    public ?string $resourceTypeId = null;

    #[Column]
    public ?int $sequence = null;

    #[Column]
    public ?string $title = null;

    #[Column]
    public ?string $slug = null;

    #[Column]
    public ?string $description = null;

    #[Column]
    public ?string $layout = null;

    #[Column('meta_title')]
    public ?string $metaTitle = null;

    #[Column('meta_description')]
    public ?string $metaDescription = null;

    #[Column('active')]
    public ?ResourceStatus $status = null;

    public function isActive(): bool
    {
        return ResourceStatus::Active === $this->status;
    }
}
