<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Entity;

/**
 * The values of the Contenir "active" column on resource and
 * resource_collection rows.
 *
 * @api
 */
enum ResourceStatus: string
{
    case Active   = 'active';
    case Archived = 'archived';
    case Inactive = 'inactive';
    case Pending  = 'pending';
}
