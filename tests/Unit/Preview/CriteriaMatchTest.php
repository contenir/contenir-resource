<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Preview;

use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Preview\CriteriaMatch;
use Contenir\Resource\Core\Tests\TestAsset\Entity\ResourceFactory;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Whether a resource answers a lookup's criteria, status passed over.
 */
#[Group('unit')]
final class CriteriaMatchTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, bool}>
     */
    public static function criteria(): array
    {
        return [
            'its slug'                          => [['slug' => 'spa-rituals'], true],
            'another slug'                      => [['slug' => 'elsewhere'], false],
            'its type among others'             => [['resourceTypeId' => ['page', 'article']], true],
            'none of the types'                 => [['resourceTypeId' => ['article', 'event']], false],
            'its status, by enum'               => [
                ['slug' => 'spa-rituals', 'status' => ResourceStatus::Active],
                true,
            ],
            'another status, passed over'       => [
                ['slug' => 'spa-rituals', 'status' => ResourceStatus::Archived],
                true,
            ],
            'hidden, passed over'               => [['slug' => 'spa-rituals', 'visible' => false], true],
            'only status, which proves nothing' => [['status' => ResourceStatus::Pending], false],
            'nothing at all'                    => [[], false],
            'a property it lacks'               => [['colour' => 'red'], false],
            'its id as text'                    => [['resourceId' => '101'], true],
            'its parent being none'             => [['parentId' => null], true],
            'a value that is no value for none' => [['parentId' => new stdClass()], false],
            'one of its ids'                    => [['resourceId' => [101, 102]], true],
            'none of the ids'                   => [['resourceId' => [102, 103]], false],
            'another creation time'             => [['created' => new DateTimeImmutable('2026-10-01 09:30:01')], false],
            'its status first'                  => [
                ['status' => ResourceStatus::Archived, 'slug' => 'spa-rituals'],
                true,
            ],
            'a parent it does not have'         => [['parentId' => 5], false],
            'its creation, to the second'       => [['created' => new DateTimeImmutable('2026-10-01 09:30:00')], true],
            'a value that is no value'          => [['slug' => new stdClass()], false],
            'its own value and a flag'          => [['slug' => 'spa-rituals', 'resourceTypeId' => 'page'], true],
            'its own value and a wrong one'     => [['slug' => 'spa-rituals', 'resourceTypeId' => 'event'], false],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>, bool}>
     */
    public static function typedCriteria(): array
    {
        return [
            'a flag set, as one'       => [['featured' => '1'], true],
            'a flag set, as true'      => [['featured' => true], true],
            'a flag set, as zero'      => [['featured' => '0'], false],
            'a flag cleared, as zero'  => [['archivable' => '0'], true],
            'a flag cleared, as false' => [['archivable' => false], true],
            'a flag cleared, as one'   => [['archivable' => '1'], false],
            'its kind'                 => [['kind' => ResourceStatus::Active], true],
            'its kind, by value'       => [['kind' => 'active'], true],
            'another kind'             => [['kind' => ResourceStatus::Archived], false],
        ];
    }

    /**
     * @param array<string, mixed> $criteria
     */
    #[Test]
    #[DataProvider('criteria')]
    public function aResourceAnswersCriteriaItsValuesMatch(array $criteria, bool $answers): void
    {
        $resource           = ResourceFactory::make(101, status: ResourceStatus::Pending);
        $resource->slug     = 'spa-rituals';
        $resource->created  = new DateTimeImmutable('2026-10-01 09:30:00');
        $resource->workflow = null;

        static::assertSame($answers, CriteriaMatch::answers($resource, $criteria));
    }

    /**
     * @param array<string, mixed> $criteria
     */
    #[Test]
    #[DataProvider('typedCriteria')]
    public function flagsAndEnumsMatchByTheirValues(array $criteria, bool $answers): void
    {
        $record = new class {
            public bool $featured = true;

            public bool $archivable = false;

            public ResourceStatus $kind = ResourceStatus::Active;
        };

        static::assertSame($answers, CriteriaMatch::answers($record, $criteria));
    }
}
