<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Entity;

use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Exception\MissingResourceException;
use Contenir\Resource\Core\Tests\TestAsset\Entity\ResourceFactory;
use DateTimeImmutable;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class AbstractResourceEntityTest extends TestCase
{
    /**
     * @return array<string, array{string|null, string|null, string|null}>
     */
    public static function metaDescriptionProvider(): array
    {
        return [
            'meta description wins'      => ['Meta', 'Body', 'Meta'],
            'blank meta falls back'      => ["\n", 'Body', 'Body'],
            'description only'           => [null, 'Body', 'Body'],
            'blank description is unset' => [null, ' ', null],
            'nothing set'                => [null, null, null],
        ];
    }

    /**
     * @return array<string, array{string|null, string|null, string|null, string|null}>
     */
    public static function metaTitleProvider(): array
    {
        return [
            'meta title wins'             => ['SEO title', 'Title', 'Sub', 'SEO title'],
            'blank meta title falls back' => ['  ', 'Title', 'Sub', 'Title Sub'],
            'title only'                  => [null, 'Title', null, 'Title'],
            'subtitle only'               => [null, null, 'Sub', 'Sub'],
            'blank title skipped'         => [null, ' ', 'Sub', 'Sub'],
            'zero title kept'             => [null, '0', null, '0'],
            'nothing set'                 => [null, null, null, null],
            'only whitespace everywhere'  => [' ', ' ', ' ', null],
        ];
    }

    /**
     * @return array<string, array{string|null, string|null, string|null}>
     */
    public static function navigationLabelProvider(): array
    {
        return [
            'short title wins'          => ['Short', 'Long', 'Short'],
            'blank short title skipped' => [' ', 'Long', 'Long'],
            'title only'                => [null, 'Long', 'Long'],
            'nothing set'               => [null, null, null],
        ];
    }

    /**
     * @return array<string, array{ResourceStatus|null, bool}>
     */
    public static function statusProvider(): array
    {
        return [
            'active'   => [ResourceStatus::Active, true],
            'pending'  => [ResourceStatus::Pending, false],
            'inactive' => [ResourceStatus::Inactive, false],
            'archived' => [ResourceStatus::Archived, false],
            'not set'  => [null, false],
        ];
    }

    /**
     * @return array<string, array{bool|null, bool}>
     */
    public static function visibilityProvider(): array
    {
        return [
            'visible' => [true, true],
            'hidden'  => [false, false],
            'not set' => [null, false],
        ];
    }

    /**
     * @return array<string, array{string|null, string}>
     */
    public static function workflowProvider(): array
    {
        return [
            'named workflow' => ['article', 'article'],
            'empty'          => ['', 'page'],
            'whitespace'     => ['  ', 'page'],
            'not set'        => [null, 'page'],
        ];
    }

    #[Test]
    public function childrenAreEmptyUntilSet(): void
    {
        static::assertSame([], (new ResourceEntity())->getChildren());
    }

    #[Test]
    public function getIdOfAnUnsavedResourceThrows(): void
    {
        $this->expectException(MissingResourceException::class);
        $this->expectExceptionMessage(ResourceEntity::class
            . ' has no resource id; save it before routing or linking to it');

        ResourceFactory::make(resourceId: null)->getId();
    }

    #[Test]
    public function getIdReturnsTheResourceId(): void
    {
        static::assertSame(42, ResourceFactory::make(resourceId: 42)->getId());
    }

    #[Test]
    public function metaDatesAreNullWhenNotSet(): void
    {
        $resource = ResourceFactory::make();

        static::assertSame([null, null], [$resource->getMetaModified(), $resource->getMetaPublish()]);
    }

    #[Test]
    #[DataProvider('metaDescriptionProvider')]
    public function metaDescriptionFallsBackToDescription(
        ?string $metaDescription,
        ?string $description,
        ?string $expected,
    ): void {
        $resource                  = ResourceFactory::make();
        $resource->metaDescription = $metaDescription;
        $resource->description     = $description;

        static::assertSame($expected, $resource->getMetaDescription());
    }

    #[Test]
    public function metaImageIsNotSetByDefault(): void
    {
        static::assertNull(ResourceFactory::make()->getMetaImage());
    }

    #[Test]
    public function metaModifiedIsTheUpdatedDate(): void
    {
        $resource          = ResourceFactory::make();
        $resource->updated = new DateTimeImmutable('2024-05-06 07:08:09');
        $resource->created = new DateTimeImmutable('2020-01-01 00:00:00');

        static::assertSame($resource->updated, $resource->getMetaModified());
    }

    #[Test]
    public function metaPublishIsTheCreatedDateNotTheUpdatedDate(): void
    {
        $resource          = ResourceFactory::make();
        $resource->created = new DateTimeImmutable('2020-01-01 00:00:00');
        $resource->updated = new DateTimeImmutable('2024-05-06 07:08:09');

        static::assertSame($resource->created, $resource->getMetaPublish());
    }

    #[Test]
    #[DataProvider('metaTitleProvider')]
    public function metaTitleFallsBackToTitleAndSubtitle(
        ?string $metaTitle,
        ?string $title,
        ?string $subtitle,
        ?string $expected,
    ): void {
        $resource            = ResourceFactory::make(title: $title);
        $resource->metaTitle = $metaTitle;
        $resource->subtitle  = $subtitle;

        static::assertSame($expected, $resource->getMetaTitle());
    }

    #[Test]
    #[DataProvider('navigationLabelProvider')]
    public function navigationLabelPrefersTheShortTitle(?string $short, ?string $title, ?string $expected): void
    {
        $resource             = ResourceFactory::make(title: $title);
        $resource->titleShort = $short;

        static::assertSame($expected, $resource->getNavigationLabel());
    }

    #[Test]
    #[DataProvider('visibilityProvider')]
    public function onlyAnExplicitlyVisibleResourceIsVisible(?bool $visible, bool $expected): void
    {
        $resource          = ResourceFactory::make();
        $resource->visible = $visible;

        static::assertSame($expected, $resource->isVisible());
    }

    #[Test]
    #[DataProvider('statusProvider')]
    public function onlyTheActiveStatusIsActive(?ResourceStatus $status, bool $expected): void
    {
        $resource         = ResourceFactory::make();
        $resource->status = $status;

        static::assertSame($expected, $resource->isActive());
    }

    #[Test]
    public function primaryKeysAreKeyedByThePropertyName(): void
    {
        static::assertSame(['resourceId' => 5], ResourceFactory::make(resourceId: 5)->getPrimaryKeys());
    }

    #[Test]
    public function routeNameIsTypeAndId(): void
    {
        static::assertSame(
            'article-12',
            ResourceFactory::make(
                resourceId: 12,
                type: 'article',
            )->getRouteName(),
        );
    }

    #[Test]
    public function setChildrenAcceptsAnyIterableAndStoresAList(): void
    {
        $first    = ResourceFactory::make(resourceId: 2);
        $second   = ResourceFactory::make(resourceId: 3);
        $resource = ResourceFactory::make();

        $resource->setChildren(
            (static function () use ($first, $second): Generator {
                yield 'a' => $first;
                yield 'b' => $second;
            })(),
        );

        static::assertSame([$first, $second], $resource->getChildren());
    }

    #[Test]
    public function setChildrenReplacesEarlierChildren(): void
    {
        $resource = ResourceFactory::make();
        $resource->setChildren([ResourceFactory::make(resourceId: 2)]);
        $resource->setChildren([]);

        static::assertSame([], $resource->getChildren());
    }

    #[Test]
    public function slugAndTypeComeFromTheColumns(): void
    {
        $resource       = ResourceFactory::make(type: 'page');
        $resource->slug = 'about/team';

        static::assertSame(['about/team', 'page'], [$resource->getSlug(), $resource->getType()]);
    }

    #[Test]
    public function slugAndTypeDefaultToEmptyStrings(): void
    {
        $resource = ResourceFactory::make(type: null);

        static::assertSame(['', ''], [$resource->getSlug(), $resource->getType()]);
    }

    #[Test]
    #[DataProvider('workflowProvider')]
    public function workflowNameDefaultsToPage(?string $workflow, string $expected): void
    {
        $resource           = ResourceFactory::make();
        $resource->workflow = $workflow;

        static::assertSame($expected, $resource->getWorkflowName());
    }
}
