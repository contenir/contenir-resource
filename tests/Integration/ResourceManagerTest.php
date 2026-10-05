<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Integration;

use Contenir\Resource\Core\Entity\AbstractResourceCollectionEntity;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Repository\ResourceCollectionRepository;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Contenir\Resource\Core\ResourceManager;
use Contenir\Resource\Core\Tests\Trait\SqliteDatabaseTrait;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('integration')]
final class ResourceManagerTest extends TestCase
{
    use SqliteDatabaseTrait;

    private ResourceManager $manager;

    /**
     * @return array<string, array{int|string}>
     */
    public static function invalidIdProvider(): array
    {
        return [
            'zero'             => [0],
            'negative'         => [-1],
            'zero string'      => ['0'],
            'leading zero'     => ['01'],
            'not digits'       => ['abc'],
            'digits then text' => ['1abc'],
            'text then digits' => ['a1'],
            'trailing newline' => ["1\n"],
            'empty'            => [''],
            'negative string'  => ['-1'],
        ];
    }

    /**
     * @param list<AbstractResourceEntity> $resources
     *
     * @return list<int|null>
     */
    private static function ids(array $resources): array
    {
        return array_map(static fn(AbstractResourceEntity $resource): ?int => $resource->resourceId, $resources);
    }

    #[Test]
    #[DataProvider('invalidIdProvider')]
    public function anInvalidIdFindsNothing(int|string $resourceId): void
    {
        static::assertSame([null, null], [$this->manager->find($resourceId), $this->manager->findActive($resourceId)]);
    }

    #[Test]
    public function findActiveSkipsResourcesThatAreNotActive(): void
    {
        static::assertSame(
            [1, null, null],
            [
                $this->manager->findActive(1)?->resourceId,
                $this->manager->findActive(4),
                $this->manager->findActive(99),
            ],
        );
    }

    #[Test]
    public function findByUsesPropertyCriteriaAndOrdering(): void
    {
        static::assertSame(
            [3, 5, 1],
            self::ids($this->manager->findBy(
                ['status' => ResourceStatus::Active, 'visible' => true],
                ['sequence' => 'ASC', 'resourceId' => 'ASC'],
            )),
        );
    }

    #[Test]
    public function findByWithoutArgumentsReturnsEveryResource(): void
    {
        static::assertCount(6, $this->manager->findBy());
    }

    #[Test]
    public function findReturnsNullForAnUnknownId(): void
    {
        static::assertNull($this->manager->find(99));
    }

    #[Test]
    public function findsActiveCollectionsOfATypeInSequence(): void
    {
        $this->insert('resource_collection', [
            'resource_collection_id' => 1,
            'resource_type_id'       => 'product',
            'sequence'               => 2,
            'active'                 => 'active',
        ]);
        $this->insert('resource_collection', [
            'resource_collection_id' => 2,
            'resource_type_id'       => 'product',
            'sequence'               => 1,
            'active'                 => 'active',
        ]);
        $this->insert('resource_collection', [
            'resource_collection_id' => 3,
            'resource_type_id'       => 'product',
            'sequence'               => 0,
            'active'                 => 'inactive',
        ]);
        $this->insert('resource_collection', [
            'resource_collection_id' => 4,
            'resource_type_id'       => 'gallery',
            'sequence'               => 0,
            'active'                 => 'active',
        ]);

        static::assertSame(
            [[2, 1], [4, 2, 1]],
            [
                array_map(
                    static fn(AbstractResourceCollectionEntity $c): ?int => $c->resourceCollectionId,
                    $this->manager->findCollectionByType('product'),
                ),
                array_map(
                    static fn(AbstractResourceCollectionEntity $c): ?int => $c->resourceCollectionId,
                    $this->manager->findCollectionByType(['product', 'gallery']),
                ),
            ],
        );
    }

    #[Test]
    public function findsActiveResourcesBySlug(): void
    {
        static::assertSame(
            [1, null],
            [$this->manager->findActiveBySlug('about')?->resourceId, $this->manager->findActiveBySlug('draft')],
        );
    }

    #[Test]
    public function findsActiveResourcesByWorkflow(): void
    {
        static::assertSame(
            [1, null],
            [$this->manager->findActiveByWorkflow('page')?->resourceId, $this->manager->findActiveByWorkflow('shop')],
        );
    }

    #[Test]
    public function findsAResourceByIdWhateverItsStatus(): void
    {
        static::assertSame([4, 3], [$this->manager->find(4)?->resourceId, $this->manager->find('3')?->resourceId]);
    }

    #[Test]
    public function findsOneResourceByCriteriaOrType(): void
    {
        static::assertSame(
            [5, 2, 3],
            [
                $this->manager->findOneBy(['workflow' => 'blog'], [
                    'sequence'   => 'ASC',
                    'resourceId' => 'DESC',
                ])?->resourceId,
                $this->manager->findOneByType('page', ['visible' => false])?->resourceId,
                $this->manager->findOneByType(['article'], [], ['sequence' => 'DESC'])?->resourceId,
            ],
        );
    }

    #[Test]
    public function findsResourcesByTypeOrTypes(): void
    {
        static::assertSame(
            [[3], [3, 5]],
            [
                self::ids($this->manager->findByType('article')),
                self::ids($this->manager->findByType(
                    ['article', 'page'],
                    ['workflow' => 'blog', 'visible' => true],
                    [
                        'sequence'   => 'ASC',
                        'resourceId' => 'ASC',
                    ],
                )),
            ],
        );
    }

    #[Test]
    public function findsTheActiveVisiblePageOfAWorkflow(): void
    {
        static::assertSame(
            [5, null],
            [
                $this->manager->findActivePageByWorkflow('blog')?->resourceId,
                $this->manager->findActivePageByWorkflow('shop'),
            ],
        );
    }

    #[Test]
    public function oneByTypeOrdersTheMatches(): void
    {
        static::assertSame(
            [5, 4],
            [
                $this->manager->findOneByType('page', [], ['sequence' => 'ASC', 'resourceId' => 'DESC'])?->resourceId,
                $this->manager->findOneByType('page', [], ['sequence' => 'DESC'])?->resourceId,
            ],
        );
    }

    #[Test]
    public function theTypeArgumentWinsOverATypeCriterion(): void
    {
        static::assertSame([3], self::ids($this->manager->findByType('article', ['resourceTypeId' => 'page'])));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->manager = new ResourceManager(
            new ResourceRepository($this->em),
            new ResourceCollectionRepository($this->em),
        );

        $this->insertResource(['resource_id' => 1, 'slug' => 'about', 'workflow' => 'page', 'sequence' => 2]);
        $this->insertResource([
            'resource_id' => 2,
            'slug'        => 'hidden',
            'workflow'    => 'blog',
            'visible'     => 0,
            'sequence'    => 3,
        ]);
        $this->insertResource([
            'resource_id'      => 3,
            'slug'             => 'news',
            'workflow'         => 'blog',
            'resource_type_id' => 'article',
            'sequence'         => 1,
        ]);
        $this->insertResource([
            'resource_id' => 4,
            'slug'        => 'draft',
            'workflow'    => 'shop',
            'active'      => 'pending',
            'sequence'    => 4,
        ]);
        $this->insertResource(['resource_id' => 5, 'slug' => 'blog', 'workflow' => 'blog', 'sequence' => 1]);
        $this->insertResource(['resource_id' => 0, 'resource_type_id' => 'zero', 'active' => 'active', 'visible' => 0]);
    }
}
