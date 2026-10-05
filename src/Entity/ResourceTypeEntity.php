<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Entity;

use Contenir\Db\Model\Mapping\Table;

/**
 * The default resource type entity, mapped to the "resource_type" table.
 *
 * @api
 */
#[Table('resource_type')]
final class ResourceTypeEntity extends AbstractResourceTypeEntity {}
