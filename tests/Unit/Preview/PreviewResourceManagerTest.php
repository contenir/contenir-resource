<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Preview;

use Contenir\Preview\PreviewRecord;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceCollectionEntity;
use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Entity\ResourceStatus;
use Contenir\Resource\Core\Preview\EditorValues;
use Contenir\Resource\Core\Preview\PreviewedResource;
use Contenir\Resource\Core\Preview\PreviewLoader;
use Contenir\Resource\Core\Preview\PreviewResourceManager;
use Contenir\Resource\Core\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Core\Tests\TestAsset\Manager\ListResourceManager;
use Contenir\Resource\Core\Tests\TestAsset\Preview\PreviewRecords;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

/**
 * The site's resource manager while the CMS previews a resource: lookups the previewed resource
 * answers find it with its unsaved values, and everything else finds what the site would.
 */
#[Group('unit')]
final class PreviewResourceManagerTest extends TestCase
{
    /**
     * @return array<string, array{callable(PreviewResourceManager): ?AbstractResourceEntity, string}>
     */
    public static function lookupsItDoesNotAnswer(): array
    {
        return [
            'another id'               => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->find(102),
                'find',
            ],
            'another id, if active'    => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findActive(102),
                'findActive',
            ],
            'another slug'             => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findActiveBySlug('contact'),
                'findActiveBySlug',
            ],
            'another workflow'         => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findActiveByWorkflow('contact'),
                'findActiveByWorkflow',
            ],
            'another workflow\'s page' => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findActivePageByWorkflow(
                    'contact',
                ),
                'findActivePageByWorkflow',
            ],
            'other criteria'           => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findOneBy(['slug' => 'contact']),
                'findOneBy',
            ],
            'another type'             => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findOneByType('event', [
                    'slug' => 'spa-rituals',
                ]),
                'findOneByType',
            ],
            'its type, other criteria' => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findOneByType('page', [
                    'slug' => 'contact',
                ]),
                'findOneByType',
            ],
        ];
    }

    /**
     * @return array<string, array{callable(PreviewResourceManager): ?AbstractResourceEntity}>
     */
    public static function lookupsTheDraftAnswers(): array
    {
        return [
            'by id'                 => [static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->find(101)],
            'by id, if active'      => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findActive('101'),
            ],
            'by its new slug'       => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findActiveBySlug('spa-rituals'),
            ],
            'by its workflow'       => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findActiveByWorkflow('spa'),
            ],
            'as a workflow\'s page' => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findActivePageByWorkflow('spa'),
            ],
            'by criteria'           => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findOneBy([
                    'slug' => 'spa-rituals',
                ]),
            ],
            'by type and criteria'  => [
                static fn(PreviewResourceManager $m): ?AbstractResourceEntity => $m->findOneByType('page', [
                    'slug' => 'spa-rituals',
                ]),
            ],
        ];
    }

    #[Test]
    public function aRecordThatIsNotAPageIsNoWorkflowsPage(): void
    {
        $article           = ResourceFactory::make(201, type: 'article', title: 'Late checkout');
        $article->workflow = 'news';
        $page              = ResourceFactory::make(105, title: 'News');
        $page->workflow    = 'news';
        $site              = new ListResourceManager([$article, $page]);

        $found = $this->decorate($site, PreviewRecords::saved(201, ['title' => 'Late checkout, all summer']))
            ->findActivePageByWorkflow('news');

        static::assertSame(105, $found?->resourceId);
    }

    /**
     * @param callable(PreviewResourceManager): ?AbstractResourceEntity $lookup
     */
    #[Test]
    #[DataProvider('lookupsItDoesNotAnswer')]
    public function asksTheSiteForEverythingElse(callable $lookup, string $asked): void
    {
        $site = $this->site($this->draft());
        $lookup($this->decorate($site, $this->draft()));

        static::assertSame(['find', $asked], $site->calls);
    }

    /**
     * @param callable(PreviewResourceManager): ?AbstractResourceEntity $lookup
     */
    #[Test]
    #[DataProvider('lookupsTheDraftAnswers')]
    public function findsADraftWithItsUnsavedValues(callable $lookup): void
    {
        $found = $lookup($this->manager($this->draft()));

        static::assertSame([101, 'Spa Rituals'], [$found?->resourceId, $found?->title]);
    }

    #[Test]
    public function findsTheSitesOwnResourceWithoutAPreview(): void
    {
        $found = $this->manager(null)->findActiveBySlug('contact');

        static::assertSame([102, 'Contact'], [$found?->resourceId, $found?->title]);
    }

    #[Test]
    public function leavesCollectionsToTheSite(): void
    {
        $collection = new ResourceCollectionEntity();

        static::assertSame([$collection], $this->manager($this->draft(), [$collection])->findCollectionByType('page'));
    }

    #[Test]
    public function liftsTheUnsavedValuesIntoListsTheResourceIsIn(): void
    {
        $manager = $this->manager($this->draft());

        static::assertSame(
            [
                ['Spa Rituals', 'Contact'],
                ['Spa Rituals', 'Contact'],
            ],
            [
                array_map(static fn(AbstractResourceEntity $r): ?string => $r->title, $manager->findBy()),
                array_map(static fn(AbstractResourceEntity $r): ?string => $r->title, $manager->findByType('page')),
            ],
        );
    }

    #[Test]
    public function putsTheUnsavedValuesInPlaceWhereverTheSiteFindsTheResource(): void
    {
        $manager = $this->manager(PreviewRecords::saved(102, ['title' => 'Get in touch']));

        static::assertSame(
            [
                'Get in touch',
                'Get in touch',
                'Get in touch',
                'Get in touch',
                'Get in touch',
                'Get in touch',
                'Get in touch',
            ],
            [
                $manager->find(102)?->title,
                $manager->findActive(102)?->title,
                $manager->findActiveBySlug('contact')?->title,
                $manager->findActiveByWorkflow('contact')?->title,
                $manager->findActivePageByWorkflow('contact')?->title,
                $manager->findOneBy(['title' => 'Contact'])?->title,
                $manager->findOneByType('page', ['title' => 'Contact'])?->title,
            ],
        );
    }

    private function decorate(ListResourceManager $site, ?PreviewRecord $record): PreviewResourceManager
    {
        return new PreviewResourceManager($site, new PreviewedResource(new PreviewLoader(
            PreviewRecords::context($record),
            $site,
            new EditorValues(PreviewRecords::rows($this->createStub(AdapterInterface::class))),
            ResourceEntity::class,
        )));
    }

    private function draft(): PreviewRecord
    {
        return PreviewRecords::saved(101, ['title' => 'Spa Rituals', 'slug' => 'spa-rituals', 'workflow' => 'spa']);
    }

    /**
     * @param list<ResourceCollectionEntity> $collections
     */
    private function manager(?PreviewRecord $record, array $collections = []): PreviewResourceManager
    {
        return $this->decorate($this->site($record, $collections), $record);
    }

    /**
     * The site's own resources: the draft (101) and Contact (102), the previewed one first.
     *
     * @param list<ResourceCollectionEntity> $collections
     */
    private function site(?PreviewRecord $record, array $collections = []): ListResourceManager
    {
        $draft             = ResourceFactory::make(101, title: 'Old title', status: ResourceStatus::Pending);
        $draft->slug       = 'old-slug';
        $contact           = ResourceFactory::make(102, title: 'Contact');
        $contact->slug     = 'contact';
        $contact->workflow = 'contact';

        return (
            '102' === ($record?->keys['resource_id'] ?? null)
                ? new ListResourceManager([$contact, $draft], $collections)
                : new ListResourceManager([$draft, $contact], $collections)
        );
    }
}
