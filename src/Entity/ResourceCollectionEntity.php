<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Entity;

use Contenir\Db\Model\Mapping\Table;

/**
 * The default resource collection entity, mapped to the
 * "resource_collection" table.
 *
 * @api
 */
#[Table('resource_collection')]
final class ResourceCollectionEntity extends AbstractResourceCollectionEntity {}
