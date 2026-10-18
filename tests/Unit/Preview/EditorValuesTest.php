<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Preview;

use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Preview\EditorValues;
use Contenir\Resource\Core\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Core\Tests\TestAsset\Preview\PreviewRecords;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The CMS editor's values laid over a resource, converted as stored columns would be.
 */
#[Group('unit')]
final class EditorValuesTest extends TestCase
{
    #[Test]
    public function buildsAResourceNotSavedYetFromItsValues(): void
    {
        $created = $this->values()->create(ResourceEntity::class, [
            'resource_type_id' => 'article',
            'title'            => 'Late checkout',
            'slug'             => 'late-checkout',
        ]);

        static::assertSame(
            [ResourceEntity::class, null, 'article', 'Late checkout', 'late-checkout'],
            [$created::class, $created->resourceId, $created->resourceTypeId, $created->title, $created->slug],
        );
    }

    #[Test]
    public function convertsEachValueAsAStoredColumnWouldBe(): void
    {
        $over = $this->values()->over(ResourceFactory::make(101), [
            'title'       => 'Spa Rituals',
            'title_short' => 'Spa',
            'visible'     => '0',
            'active'      => 'pending',
            'created'     => '2026-10-01 09:30:00',
        ]);

        static::assertSame(
            ['Spa Rituals', 'Spa', false, ResourceStatus::Pending, '2026-10-01 09:30:00'],
            [$over->title, $over->titleShort, $over->visible, $over->status, $over->created?->format('Y-m-d H:i:s')],
        );
    }

    #[Test]
    public function leavesListsOfRelatedRecordsAsStored(): void
    {
        $over = $this->values()->over(ResourceFactory::make(101), ['slug' => ['401', '402'], 'title' => 'Spa Rituals']);

        static::assertSame(['Spa Rituals', null], [$over->title, $over->slug]);
    }

    #[Test]
    public function leavesOutAValueTheSiteCannotConvert(): void
    {
        $over = $this->values()->over(ResourceFactory::make(101), [
            'title'   => 'Spa Rituals',
            'created' => 'next Tuesday-ish',
            'active'  => 'not a status',
        ]);

        static::assertSame(
            ['Spa Rituals', null, ResourceStatus::Active],
            [$over->title, $over->created, $over->status],
        );
    }

    #[Test]
    public function leavesTheResourceItWasGivenAsItWas(): void
    {
        $resource = ResourceFactory::make(101);

        $over = $this->values()->over($resource, ['title' => 'Spa Rituals']);

        static::assertSame(['About', false], [$resource->title, $resource === $over]);
    }

    private function values(): EditorValues
    {
        return new EditorValues(PreviewRecords::rows($this->createStub(AdapterInterface::class)));
    }
}
