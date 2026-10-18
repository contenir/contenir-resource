<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Preview;

use Contenir\Db\Model\Exception\ExceptionInterface as DbModelException;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;

/**
 * The resource the CMS is previewing, loaded once the preview has begun and kept for the rest of
 * the request, and the lookups it answers:
 * by its id, by criteria its unsaved values match, or in place of itself wherever the site's
 * manager finds it.
 *
 * @internal
 */
final class PreviewedResource
{
    private bool $loaded = false;

    private ?AbstractResourceEntity $resource = null;

    public function __construct(
        private readonly PreviewLoader $loader,
    ) {}

    /**
     * The previewed resource when it has this id.
     *
     * @throws DbModelException
     */
    public function byId(int|string $resourceId): ?AbstractResourceEntity
    {
        $previewed = $this->get();

        return null !== $previewed && (string) $previewed->resourceId === (string) $resourceId ? $previewed : null;
    }

    /**
     * @throws DbModelException
     */
    public function get(): ?AbstractResourceEntity
    {
        if (! $this->loaded && $this->loader->isReady()) {
            $this->loaded   = true;
            $this->resource = $this->loader->load();
        }

        return $this->resource;
    }

    /**
     * The previewed resource when it answers a lookup's criteria with its unsaved values.
     *
     * @param array<string, mixed> $criteria Keyed by property, as the site's manager takes them
     *
     * @throws DbModelException
     */
    public function matching(array $criteria): ?AbstractResourceEntity
    {
        $previewed = $this->get();

        return null !== $previewed && CriteriaMatch::answers($previewed, $criteria) ? $previewed : null;
    }

    /**
     * The resource as found, or the previewed one in its place when it is the same resource.
     *
     * @throws DbModelException
     */
    public function over(?AbstractResourceEntity $resource): ?AbstractResourceEntity
    {
        $id = $resource?->resourceId;

        return null === $id ? $resource : $this->byId($id) ?? $resource;
    }

    /**
     * Resources as found, with the previewed one in its place wherever it is among them.
     *
     * @param list<AbstractResourceEntity> $resources
     *
     * @return list<AbstractResourceEntity>
     *
     * @throws DbModelException
     */
    public function overEach(array $resources): array
    {
        $found = [];
        foreach ($resources as $resource) {
            $found[] = $this->over($resource) ?? $resource;
        }

        return $found;
    }
}
