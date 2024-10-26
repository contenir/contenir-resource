<?php

declare(strict_types=1);

namespace Contenir\Resource\Model\Entity;

use Contenir\Db\Model\Entity\AbstractEntity;
use Contenir\Metadata\MetadataInterface;
use Contenir\Mvc\Workflow\Resource\ResourceInterface;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

use function array_filter;
use function explode;
use function implode;
use function sprintf;

class BaseResourceEntity extends AbstractEntity implements
    MetadataInterface,
    ResourceInterface
{
    protected ?string $routeId   = null;
    protected ?string $routePath = null;

    /**
     * getRouteId
     *
     * @param  mixed $path
     * @return String
     */
    public function getRouteId(string $path = ''): string
    {
        if ($this->routeId === null) {
            $routeId = sprintf(
                '%s-%s',
                $this->resource_type_id ?? null,
                implode('-', $this->getPrimaryKeys() ?? null)
            );

            $this->routeId = $routeId;
        }

        return sprintf('%s', implode('/', array_filter([$this->routeId, $path])));
    }

    /**
     * getRoutePath
     *
     * @return String
     */
    public function getRoutePath(): string
    {
        if ($this->routePath === null) {
            $parts           = explode('/', $this->getSlug());
            $this->routePath = sprintf('/%s', implode('/', array_filter($parts)));
        }

        return $this->routePath;
    }

    public function getMetaTitle(): ?string
    {
        $fallbackTitle = implode(' ', array_filter([
            $this->title ?? null,
            $this->subtitle ?? null,
        ]));

        return $this->meta_title ?? $fallbackTitle;
    }

    public function getMetaDescription(): ?string
    {
        return $this->meta_description ?? $this->description ?? null;
    }

    public function getMetaImage(): ?string
    {
        return $this->image[0]->path ?? null;
    }

    /**
     * @throws Exception
     */
    public function getMetaModified(): ?DateTimeInterface
    {
        if ($this->updated ?? null) {
            return new DateTimeImmutable($this->updated ?? null);
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getMetaPublish(): ?DateTimeInterface
    {
        if ($this->created ?? null) {
            return new DateTimeImmutable($this->updated ?? null);
        }

        return null;
    }

    public function getSlug(): string
    {
        return $this->slug ?? '';
    }
}
