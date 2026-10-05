<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;

/**
 * Base class for Contenir resource type rows (the "resource_type" table).
 *
 * @api
 */
abstract class AbstractResourceTypeEntity
{
    #[Id]
    #[Column('resource_type_id')]
    public string $resourceTypeId = '';

    #[Column]
    public ?string $slug = null;

    #[Column]
    public ?int $sequence = null;

    #[Column]
    public ?string $type = null;

    #[Column]
    public ?string $title = null;
}
