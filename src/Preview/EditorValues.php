<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Preview;

use Contenir\Db\Model\Exception\HydrationException;
use Contenir\Db\Model\Exception\MappingException;
use Contenir\Db\Model\Exception\TypeConversionException;
use Contenir\Db\Model\RowValues;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;

use function is_array;

/**
 * The CMS editor's values laid over a resource, each converted as a stored column would be. Only
 * plain columns take them; lists are related records (images, tags), which stay as stored. A
 * value the site cannot convert, such as a half-typed date, is left out rather than failing the
 * page.
 *
 * @internal
 */
final readonly class EditorValues
{
    public function __construct(
        private RowValues $rows,
    ) {}

    /**
     * A resource not saved yet, of the site's class, holding the editor's values.
     *
     * @param class-string<AbstractResourceEntity>      $className
     * @param array<string, string|list<string>|null> $values
     *
     * @throws HydrationException
     * @throws MappingException
     * @throws TypeConversionException
     */
    public function create(string $className, array $values): AbstractResourceEntity
    {
        return $this->over($this->rows->fromRow($className, []), $values);
    }

    /**
     * A copy of the resource with the editor's values in place of its own.
     *
     * @param array<string, string|list<string>|null> $values Keyed by column, as the CMS sends them
     *
     * @throws MappingException When the resource's class is not a valid mapping
     */
    public function over(AbstractResourceEntity $resource, array $values): AbstractResourceEntity
    {
        $row = [];
        foreach ($values as $column => $value) {
            if (is_array($value)) {
                continue;
            }
            $row[$column] = $value;
        }

        try {
            return $this->rows->withRow($resource, $row);
        } catch (HydrationException|TypeConversionException) {
            foreach ($row as $column => $value) {
                $resource = $this->column($resource, $column, $value);
            }

            return $resource;
        }
    }

    /**
     * @throws MappingException
     */
    private function column(AbstractResourceEntity $resource, string $column, ?string $value): AbstractResourceEntity
    {
        try {
            return $this->rows->withRow($resource, [$column => $value]);
        } catch (HydrationException|TypeConversionException) {
            return $resource;
        }
    }
}
