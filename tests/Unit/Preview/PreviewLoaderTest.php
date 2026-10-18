<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Preview;

use Contenir\Preview\PreviewRecord;
use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Preview\EditorValues;
use Contenir\Resource\Core\Preview\PreviewLoader;
use Contenir\Resource\Core\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Core\Tests\TestAsset\Manager\ListResourceManager;
use Contenir\Resource\Core\Tests\TestAsset\Preview\PreviewRecords;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The resource a preview is of, loaded with the editor's values.
 */
#[Group('unit')]
final class PreviewLoaderTest extends TestCase
{
    /**
     * @return array<string, array{PreviewRecord|null}>
     */
    public static function previewsOfSomethingElse(): array
    {
        return [
            'no preview'                        => [null],
            'a resource the site does not have' => [PreviewRecords::saved(999, ['title' => 'Gone'])],
            'a record of another table'         => [new PreviewRecord(
                'library.tags.tags',
                ['tag_id' => '7'],
                ['title' => 'Spa'],
            )],
            'a record keyed by more than an id' => [new PreviewRecord(
                'x.y.z',
                ['resource_id' => '101', 'lang' => 'en'],
                [],
            )],
            'a new record of another table'     => [PreviewRecords::unsaved(['title' => 'A tag'], 'library.tags.tags')],
        ];
    }

    #[Test]
    public function buildsAResourceNotSavedYetAsTheSitesOwnEntity(): void
    {
        $loaded = $this->loader(PreviewRecords::unsaved(['resource_type_id' => 'article', 'title' => 'Late checkout']))
            ->load();

        static::assertSame(
            [ResourceEntity::class, null, 'Late checkout'],
            [null === $loaded ? null : $loaded::class, $loaded?->resourceId, $loaded?->title],
        );
    }

    #[Test]
    public function loadsASavedResourceWithTheEditorsValuesWhateverItsStatus(): void
    {
        $loaded = $this->loader(
            PreviewRecords::saved(101, ['title' => 'Spa Rituals']),
            ResourceFactory::make(
                101,
                status: ResourceStatus::Pending,
            ),
        )->load();

        static::assertSame([101, 'Spa Rituals'], [$loaded?->resourceId, $loaded?->title]);
    }

    #[Test]
    #[DataProvider('previewsOfSomethingElse')]
    public function loadsNothingForAPreviewOfSomethingElse(?PreviewRecord $record): void
    {
        static::assertNull($this->loader($record, ResourceFactory::make(101))->load());
    }

    private function loader(?PreviewRecord $record, ResourceEntity ...$resources): PreviewLoader
    {
        return new PreviewLoader(
            PreviewRecords::context($record),
            new ListResourceManager($resources),
            new EditorValues(PreviewRecords::rows($this->createStub(AdapterInterface::class))),
            ResourceEntity::class,
        );
    }
}
