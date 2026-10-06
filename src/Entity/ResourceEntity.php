<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Entity;

use Contenir\Db\Model\Mapping\Table;

/**
 * The default resource entity, mapped to the "resource" table with the
 * columns of AbstractResourceEntity.
 *
 * @api
 */
#[Table('resource')]
final class ResourceEntity extends AbstractResourceEntity {}
