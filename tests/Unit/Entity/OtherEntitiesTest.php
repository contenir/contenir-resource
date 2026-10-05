<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Entity;

use Contenir\Resource\Core\Entity\ResourceCollectionEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Entity\ResourceTypeEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class OtherEntitiesTest extends TestCase
{
    /**
     * @return array<string, array{ResourceStatus|null, bool}>
     */
    public static function statusProvider(): array
    {
        return [
            'active'  => [ResourceStatus::Active, true],
            'pending' => [ResourceStatus::Pending, false],
            'not set' => [null, false],
        ];
    }

    #[Test]
    public function aNewResourceTypeHasAnEmptyId(): void
    {
        static::assertSame('', (new ResourceTypeEntity())->resourceTypeId);
    }

    #[Test]
    #[DataProvider('statusProvider')]
    public function onlyAnActiveCollectionIsActive(?ResourceStatus $status, bool $expected): void
    {
        $collection         = new ResourceCollectionEntity();
        $collection->status = $status;

        static::assertSame($expected, $collection->isActive());
    }
}
