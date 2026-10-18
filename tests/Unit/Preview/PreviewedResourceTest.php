<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Preview;

use Contenir\Preview\PreviewRecord;
use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Preview\EditorValues;
use Contenir\Resource\Core\Preview\PreviewedResource;
use Contenir\Resource\Core\Preview\PreviewLoader;
use Contenir\Resource\Core\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Core\Tests\TestAsset\Manager\ListResourceManager;
use Contenir\Resource\Core\Tests\TestAsset\Preview\PreviewRecords;
use LogicException;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

/**
 * The previewed resource, loaded once, and the lookups it answers.
 */
#[Group('unit')]
final class PreviewedResourceTest extends TestCase
{
    #[Test]
    public function answersByItsIdOnly(): void
    {
        $previewed = $this->previewed(PreviewRecords::saved(101, ['title' => 'Spa Rituals']));

        static::assertSame(['Spa Rituals', null], [$previewed->byId('101')?->title, $previewed->byId(102)]);
    }

    #[Test]
    public function answersCriteriaItsUnsavedValuesMatch(): void
    {
        $previewed = $this->previewed(PreviewRecords::saved(101, ['slug' => 'spa-rituals']));

        static::assertSame(
            ['spa-rituals', null],
            [$previewed->matching(['slug' => 'spa-rituals'])?->slug, $previewed->matching(['slug' => 'about'])],
        );
    }

    #[Test]
    public function answersNothingWithoutAPreview(): void
    {
        $previewed = $this->previewed(null);

        static::assertSame(
            [null, null, null],
            [$previewed->get(), $previewed->byId(101), $previewed->matching(['slug' => 'about'])],
        );
    }

    #[Test]
    public function loadsThePreviewedResourceOnceForTheRequest(): void
    {
        $manager   = new ListResourceManager([ResourceFactory::make(101)]);
        $previewed = $this->previewed(PreviewRecords::saved(101, ['title' => 'Spa Rituals']), $manager);

        $previewed->get();
        $previewed->byId(101);
        $previewed->matching(['title' => 'Spa Rituals']);

        static::assertSame(['find'], $manager->calls);
    }

    #[Test]
    public function loadsThePreviewedResourceOnceThePreviewHasBegun(): void
    {
        $context   = PreviewRecords::context();
        $previewed = new PreviewedResource(new PreviewLoader(
            $context,
            new ListResourceManager([ResourceFactory::make(101)]),
            new EditorValues(PreviewRecords::rows($this->createStub(AdapterInterface::class))),
            ResourceEntity::class,
        ));

        $before = $previewed->get();
        $context->begin(
            PreviewRecords::context(PreviewRecords::saved(101, ['title' => 'Spa Rituals']))->request()
            ?? throw new LogicException('No preview request.'),
        );

        static::assertSame([null, 'Spa Rituals'], [$before, $previewed->get()?->title]);
    }

    #[Test]
    public function standsInForItselfAmongResourcesFound(): void
    {
        $other     = ResourceFactory::make(102, title: 'Contact');
        $unsaved   = ResourceFactory::make(null, title: 'Draft');
        $previewed = $this->previewed(PreviewRecords::saved(101, ['title' => 'Spa Rituals']));

        static::assertSame(
            ['Spa Rituals', 'Contact', 'Spa Rituals', 'Draft', null],
            [
                ...array_map(
                    static fn(ResourceEntity $resource): ?string => $resource->title,
                    $previewed->overEach([ResourceFactory::make(101), $other]),
                ),
                $previewed->over(ResourceFactory::make(101))?->title,
                $previewed->over($unsaved)?->title,
                $previewed->over(null),
            ],
        );
    }

    private function previewed(?PreviewRecord $record, ?ListResourceManager $manager = null): PreviewedResource
    {
        return new PreviewedResource(new PreviewLoader(
            PreviewRecords::context($record),
            $manager ?? new ListResourceManager([ResourceFactory::make(101)]),
            new EditorValues(PreviewRecords::rows($this->createStub(AdapterInterface::class))),
            ResourceEntity::class,
        ));
    }
}
