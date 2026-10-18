<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Preview;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Preview\PreviewContext;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\ResourceManagerInterface;

use function array_key_exists;
use function count;

/**
 * Loads the resource the CMS is previewing with the editor's values in place of the stored
 * ones: a saved resource whatever its status, so a draft shows, or one not saved yet built from
 * its values alone. A preview counts only when it is of the resource table: keyed by
 * resource_id, or not saved yet and naming its resource_type_id.
 *
 * @internal
 */
final readonly class PreviewLoader
{
    /**
     * @param ResourceManagerInterface              $resources   The site's own, not the preview's
     * @param class-string<AbstractResourceEntity> $entityClass The site's resource entity
     */
    public function __construct(
        private PreviewContext $context,
        private ResourceManagerInterface $resources,
        private EditorValues $values,
        private string $entityClass,
    ) {}

    /**
     * Whether a preview has begun, so that what load() answers holds for the rest of the request.
     * Until it has (a resolver looking resources up while the preview is opened, say), there is
     * nothing to load yet.
     */
    public function isReady(): bool
    {
        return $this->context->isActive();
    }

    /**
     * @throws DbModelException
     */
    public function load(): ?AbstractResourceEntity
    {
        $record = $this->context->request()?->record;
        if (null === $record) {
            return null;
        }

        $id = $record->keys['resource_id'] ?? null;
        if (null !== $id && 1 === count($record->keys)) {
            $stored = $this->resources->find($id);

            return null === $stored ? null : $this->values->over($stored, $record->values);
        }

        return [] === $record->keys && array_key_exists('resource_type_id', $record->values)
            ? $this->values->create($this->entityClass, $record->values)
            : null;
    }
}
