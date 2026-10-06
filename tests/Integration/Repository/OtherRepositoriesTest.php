<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Integration\Repository;

use Contenir\Resource\Core\Entity\ResourceCollectionEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Entity\ResourceTypeEntity;
use Contenir\Resource\Core\Repository\ResourceCollectionRepository;
use Contenir\Resource\Core\Repository\ResourceTypeRepository;
use Contenir\Resource\Core\Tests\Trait\SqliteDatabaseTrait;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
#[Group('repository')]
final class OtherRepositoriesTest extends TestCase
{
    use SqliteDatabaseTrait;

    #[Test]
    public function mapsEveryColumnOfTheCollectionTable(): void
    {
        $this->insert('resource_collection', [
            'resource_collection_id' => 4,
            'resource_type_id'       => 'product',
            'sequence'               => 2,
            'title'                  => 'Teas',
            'slug'                   => 'teas',
            'description'            => 'Leaf',
            'layout'                 => 'grid',
            'meta_title'             => 'Meta',
            'meta_description'       => 'Meta desc',
            'active'                 => 'active',
        ]);

        $collection = (new ResourceCollectionRepository($this->em))->find(4);

        static::assertSame(
            [
                ResourceCollectionEntity::class,
                4,
                'product',
                2,
                'Teas',
                'teas',
                'Leaf',
                'grid',
                'Meta',
                'Meta desc',
                ResourceStatus::Active,
            ],
            [
                null === $collection ? null : $collection::class,
                $collection?->resourceCollectionId,
                $collection?->resourceTypeId,
                $collection?->sequence,
                $collection?->title,
                $collection?->slug,
                $collection?->description,
                $collection?->layout,
                $collection?->metaTitle,
                $collection?->metaDescription,
                $collection?->status,
            ],
        );
    }

    #[Test]
    public function mapsEveryColumnOfTheTypeTable(): void
    {
        $this->insert('resource_type', [
            'resource_type_id' => 'page',
            'slug'             => 'pages',
            'sequence'         => 1,
            'type'             => 'content',
            'title'            => 'Page',
        ]);

        $type = (new ResourceTypeRepository($this->em))->find('page');

        static::assertSame(
            [ResourceTypeEntity::class, 'page', 'pages', 1, 'content', 'Page'],
            [
                null === $type ? null : $type::class,
                $type?->resourceTypeId,
                $type?->slug,
                $type?->sequence,
                $type?->type,
                $type?->title,
            ],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpDatabase();
    }
}
