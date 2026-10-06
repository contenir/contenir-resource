<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Metadata\MetadataInterface;
use Contenir\Resource\Core\Exception\MissingResourceException;
use DateTimeImmutable;
use DateTimeInterface;
use Override;

use function array_filter;
use function implode;
use function trim;

/**
 * Base class for Contenir resource rows (the "resource" table): the columns
 * every Contenir site has, the tree and routing accessors the framework
 * adapters build routes and navigation from, and the contenir-metadata
 * MetadataInterface used for the page head. It has no framework dependency.
 *
 * Extend it with a final class carrying #[Table('resource')] (or use
 * ResourceEntity) and add the site's own columns and relations there. The
 * metadata getters are the hooks to override, for example getMetaImage()
 * to return the path of the resource's first image.
 *
 * @api
 *
 * @mago-expect lint:too-many-properties One property per column of the resource table.
 * @mago-expect lint:too-many-methods The tree, routing and metadata accessors over the columns.
 */
abstract class AbstractResourceEntity implements MetadataInterface
{
    /**
     * The key of the resource id in getPrimaryKeys(), and so in the "id"
     * default of every workflow route.
     */
    public const string PRIMARY_KEY = 'resourceId';

    #[Id(generated: true)]
    #[Column('resource_id')]
    public ?int $resourceId = null;

    #[Column('parent_id')]
    public ?int $parentId = null;

    #[Column('resource_type_id')]
    public ?string $resourceTypeId = null;

    #[Column]
    public ?string $workflow = null;

    #[Column]
    public ?int $sequence = null;

    #[Column]
    public ?string $slug = null;

    #[Column]
    public ?string $title = null;

    #[Column('title_short')]
    public ?string $titleShort = null;

    #[Column]
    public ?string $subtitle = null;

    #[Column]
    public ?string $description = null;

    #[Column('meta_title')]
    public ?string $metaTitle = null;

    #[Column('meta_description')]
    public ?string $metaDescription = null;

    #[Column]
    public ?bool $visible = null;

    #[Column]
    public ?DateTimeImmutable $created = null;

    #[Column]
    public ?DateTimeImmutable $updated = null;

    #[Column('active')]
    public ?ResourceStatus $status = null;

    /**
     * The child resources assembled by ResourceRepository::findPageTree().
     *
     * @var list<AbstractResourceEntity>
     */
    private array $children = [];

    /**
     * The value, or null when it is null or only whitespace.
     */
    private static function nonEmpty(?string $value): ?string
    {
        return null === $value || '' === trim($value) ? null : $value;
    }

    /**
     * @return list<AbstractResourceEntity>
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @throws MissingResourceException When the resource has not been saved.
     */
    public function getId(): int
    {
        return $this->resourceId ?? throw MissingResourceException::unsaved(static::class);
    }

    /**
     * The meta description, or else the description.
     */
    #[Override]
    public function getMetaDescription(): ?string
    {
        return self::nonEmpty($this->metaDescription) ?? self::nonEmpty($this->description);
    }

    /**
     * The share image path. Resources carry no image column, so this is
     * null; override it to return, for example, the first related asset's path.
     */
    #[Override]
    public function getMetaImage(): ?string
    {
        return null;
    }

    #[Override]
    public function getMetaModified(): ?DateTimeInterface
    {
        return $this->updated;
    }

    /**
     * When the resource was published, taken to be when its row was created.
     */
    #[Override]
    public function getMetaPublish(): ?DateTimeInterface
    {
        return $this->created;
    }

    /**
     * The meta title, or else the title and subtitle joined by a space.
     */
    #[Override]
    public function getMetaTitle(): ?string
    {
        $metaTitle = self::nonEmpty($this->metaTitle);
        if (null !== $metaTitle) {
            return $metaTitle;
        }

        return self::nonEmpty(implode(' ', array_filter(
            [self::nonEmpty($this->title), self::nonEmpty($this->subtitle)],
            static fn(?string $part): bool => null !== $part,
        )));
    }

    /**
     * The navigation label: the short title, or else the title.
     */
    public function getNavigationLabel(): ?string
    {
        return self::nonEmpty($this->titleShort) ?? self::nonEmpty($this->title);
    }

    /**
     * @return array{resourceId: int}
     *
     * @throws MissingResourceException When the resource has not been saved.
     */
    public function getPrimaryKeys(): array
    {
        return [self::PRIMARY_KEY => $this->getId()];
    }

    /**
     * The name of the route the default workflows register for this
     * resource: "<resource type>-<resource id>".
     *
     * @throws MissingResourceException When the resource has not been saved.
     */
    public function getRouteName(): string
    {
        return "{$this->getType()}-{$this->getId()}";
    }

    public function getSlug(): string
    {
        return $this->slug ?? '';
    }

    public function getType(): string
    {
        return $this->resourceTypeId ?? '';
    }

    /**
     * The workflow plugin that routes this resource: the "workflow" column,
     * or "page" when it is empty.
     */
    public function getWorkflowName(): string
    {
        return self::nonEmpty($this->workflow) ?? 'page';
    }

    public function isActive(): bool
    {
        return ResourceStatus::Active === $this->status;
    }

    public function isVisible(): bool
    {
        return true === $this->visible;
    }

    /**
     * @param iterable<AbstractResourceEntity> $children
     */
    public function setChildren(iterable $children): void
    {
        $list = [];
        foreach ($children as $child) {
            $list[] = $child;
        }

        $this->children = $list;
    }
}
