<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Integration\Repository;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Contenir\Resource\Core\Tests\TestAsset\Entity\SectionResourceEntity;
use Contenir\Resource\Core\Tests\Trait\SqliteDatabaseTrait;
use DateTimeImmutable;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('integration')]
#[Group('repository')]
final class ResourceRepositoryTest extends TestCase
{
    use SqliteDatabaseTrait;

    /**
     * The tree as nested [title => children] arrays.
     *
     * @param list<AbstractResourceEntity> $resources
     *
     * @return array<string, mixed>
     */
    private static function titles(array $resources): array
    {
        $tree = [];
        foreach ($resources as $resource) {
            $tree[(string) $resource->title] = self::titles($resource->getChildren());
        }

        return $tree;
    }

    #[Test]
    public function anEmptyDatabaseHasAnEmptyTree(): void
    {
        static::assertSame([], (new ResourceRepository($this->em))->findPageTree());
    }

    #[Test]
    public function aNestedPageTreeIsLoadedWithOneQueryPerLevel(): void
    {
        $this->insertResource(['resource_id' => 1, 'title' => 'Root']);
        $this->insertResource(['resource_id' => 2, 'title' => 'Level 1', 'parent_id' => 1]);
        $this->insertResource(['resource_id' => 3, 'title' => 'Level 2', 'parent_id' => 2]);
        $this->insertResource(['resource_id' => 4, 'title' => 'Level 3', 'parent_id' => 3]);

        $tree = (new ResourceRepository($this->em))->findPageTree();

        static::assertSame(['Root' => ['Level 1' => ['Level 2' => ['Level 3' => []]]]], self::titles($tree));
    }

    #[Test]
    public function aParentCycleBelowNoTopLevelPageIsNeverReached(): void
    {
        $this->insertResource(['resource_id' => 1, 'title' => 'Root']);
        $this->insertResource(['resource_id' => 2, 'title' => 'Loop A', 'parent_id' => 3]);
        $this->insertResource(['resource_id' => 3, 'title' => 'Loop B', 'parent_id' => 2]);

        static::assertSame(['Root' => []], self::titles((new ResourceRepository($this->em))->findPageTree()));
    }

    #[Test]
    public function loadsTheConfiguredEntityClass(): void
    {
        $this->insertResource(['resource_id' => 1, 'section' => '{"a":1}']);

        $resource = (new ResourceRepository($this->em, SectionResourceEntity::class))->find(1);

        static::assertInstanceOf(SectionResourceEntity::class, $resource);
    }

    #[Test]
    public function mapsEveryColumnOfTheResourceTable(): void
    {
        $this->insertResource([
            'resource_id'      => 3,
            'parent_id'        => 1,
            'resource_type_id' => 'article',
            'workflow'         => 'news',
            'sequence'         => 4,
            'slug'             => 'news/item',
            'title'            => 'Item',
            'title_short'      => 'It',
            'subtitle'         => 'Sub',
            'description'      => 'Desc',
            'meta_title'       => 'Meta',
            'meta_description' => 'Meta desc',
            'visible'          => 0,
            'created'          => '2020-01-02 03:04:05',
            'updated'          => '2021-01-02 03:04:05',
            'active'           => 'archived',
        ]);

        $resource = (new ResourceRepository($this->em))->find(3);

        static::assertEquals(
            [
                3,
                1,
                'article',
                'news',
                4,
                'news/item',
                'Item',
                'It',
                'Sub',
                'Desc',
                'Meta',
                'Meta desc',
                false,
                new DateTimeImmutable('2020-01-02 03:04:05'),
                new DateTimeImmutable('2021-01-02 03:04:05'),
                ResourceStatus::Archived,
                ResourceEntity::class,
            ],
            [
                $resource?->resourceId,
                $resource?->parentId,
                $resource?->resourceTypeId,
                $resource?->workflow,
                $resource?->sequence,
                $resource?->slug,
                $resource?->title,
                $resource?->titleShort,
                $resource?->subtitle,
                $resource?->description,
                $resource?->metaTitle,
                $resource?->metaDescription,
                $resource?->visible,
                $resource?->created,
                $resource?->updated,
                $resource?->status,
                null === $resource ? null : $resource::class,
            ],
        );
    }

    #[Test]
    public function reloadingTheTreeReplacesChildrenOfSharedInstances(): void
    {
        $this->insertResource(['resource_id' => 1, 'title' => 'Root']);
        $this->insertResource(['resource_id' => 2, 'title' => 'Child', 'parent_id' => 1]);
        $repository = new ResourceRepository($this->em);
        $repository->findPageTree();
        $this->pdo->exec("UPDATE resource SET active = 'inactive' WHERE resource_id = 2");

        static::assertSame(['Root' => []], self::titles($repository->findPageTree()));
    }

    #[Test]
    public function thePageTreeHoldsActiveTopLevelPagesInSequenceWithActiveDescendants(): void
    {
        $this->insertResource(['resource_id' => 1, 'title' => 'Second', 'sequence' => 2]);
        $this->insertResource(['resource_id' => 2, 'title' => 'First', 'sequence' => 1]);
        $this->insertResource(['resource_id' => 3, 'title' => 'Tie later id', 'sequence' => 3]);
        $this->insertResource(['resource_id' => 4, 'title' => 'Inactive', 'sequence' => 0, 'active' => 'inactive']);
        $this->insertResource(['resource_id' => 5, 'title' => 'Article root', 'resource_type_id' => 'article']);
        $this->insertResource(['resource_id' => 6, 'title' => 'Child B', 'parent_id' => 2, 'sequence' => 2]);
        $this->insertResource([
            'resource_id'      => 7,
            'title'            => 'Child A article',
            'parent_id'        => 2,
            'sequence'         => 1,
            'resource_type_id' => 'article',
        ]);
        $this->insertResource(['resource_id' => 8, 'title' => 'Grandchild', 'parent_id' => 6]);
        $this->insertResource([
            'resource_id' => 9,
            'title'       => 'Pending child',
            'parent_id'   => 2,
            'active'      => 'pending',
        ]);
        $this->insertResource(['resource_id' => 10, 'title' => 'Child of inactive', 'parent_id' => 4]);
        $this->insertResource(['resource_id' => 11, 'title' => 'Under pending', 'parent_id' => 9]);
        $this->insertResource(['resource_id' => 12, 'title' => 'Second child', 'parent_id' => 1]);
        $this->insertResource(['resource_id' => 0, 'title' => 'Tie earlier id', 'sequence' => 3]);

        $tree = (new ResourceRepository($this->em))->findPageTree();

        static::assertSame(
            [
                'First'          => ['Child A article' => [], 'Child B' => ['Grandchild' => []]],
                'Second'         => ['Second child' => []],
                'Tie earlier id' => [],
                'Tie later id'   => [],
            ],
            self::titles($tree),
        );
    }

    #[Test]
    public function theTreeReturnsEntities(): void
    {
        $this->insertResource(['resource_id' => 1]);

        static::assertSame(
            [1],
            array_map(
                static fn(AbstractResourceEntity $r): ?int => $r->resourceId,
                (new ResourceRepository($this->em))->findPageTree(),
            ),
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpDatabase();
    }
}
