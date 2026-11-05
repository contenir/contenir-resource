<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Metadata;

use Contenir\Resource\Core\Metadata\PageDefaults;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\Metadata\PageOverrides;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\FixedMetadata;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\FixedPageDefaults;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * What a site's section map adds to a page: it fills what a resource's
 * getters leave empty and gives what getters have no say in, and a getter's
 * value always wins.
 */
#[Group('unit')]
final class PageDefaultsTest extends TestCase
{
    private const array FULL = [
        'title'       => 'Mapped title',
        'description' => 'Mapped description.',
        'image'       => '/mapped.jpg',
        'imageAlt'    => 'Mapped alt',
        'canonical'   => '/work/house',
        'robots'      => 'noindex, follow',
        'schema'      => 'CreativeWork',
    ];

    /**
     * @return array<string, array{string|null, string|null}>
     */
    public static function canonicalProvider(): array
    {
        return [
            'a path joins the origin'  => ['/work/house', 'https://www.site.test/work/house'],
            'a path without its slash' => ['work/house', 'https://www.site.test/work/house'],
            'an absolute url is kept'  => ['https://elsewhere.test/house', 'https://elsewhere.test/house'],
            'blank gives none'         => ['  ', null],
            'none gives none'          => [null, null],
        ];
    }

    #[Test]
    public function aBlankGetterValueLeavesRoomForTheDefault(): void
    {
        $page = $this->builder(new PageDefaults(...self::FULL))->build(
            new FixedMetadata(
                title: ' ',
                description: '',
                image: ' ',
            ),
            'https://s.test/house',
        );

        static::assertSame(
            ['Mapped title', 'Mapped description.', 'https://s.test/mapped.jpg'],
            [$page->title, $page->description, $page->image],
        );
    }

    #[Test]
    #[DataProvider('canonicalProvider')]
    public function aCanonicalAddressIsMadeAbsoluteOnTheOrigin(?string $canonical, ?string $expected): void
    {
        static::assertSame($expected, (new PageDefaults(canonical: $canonical))->canonicalOn('https://www.site.test'));
    }

    #[Test]
    public function aGettersValueWinsOverTheDefault(): void
    {
        $page = $this->builder(new PageDefaults(...self::FULL))->build(
            new FixedMetadata(
                title: 'Own title',
                description: 'Own description.',
                image: '/own.jpg',
            ),
            'https://s.test/house',
        );

        static::assertSame(
            ['Own title', 'Own description.', 'https://s.test/own.jpg'],
            [$page->title, $page->description, $page->image],
        );
    }

    #[Test]
    public function aMappedAltTextWinsOverTheTitle(): void
    {
        $page = (new PageMetadataBuilder(
            imageAltFromTitle: true,
            defaults: new FixedPageDefaults(new PageDefaults(imageAlt: 'The house from the garden')),
        ))->build(new FixedMetadata(
            title: 'House',
            image: '/house.jpg',
        ), 'https://s.test/house');

        static::assertSame('The house from the garden', $page->imageAlt);
    }

    #[Test]
    public function anOverriddenAltTextWinsOverTheTitle(): void
    {
        $page = (new PageMetadataBuilder(
            imageAltFromTitle: true,
            defaults: new FixedPageDefaults(new PageDefaults(overrides: new PageOverrides(imageAlt: 'Share alt'))),
        ))->build(new FixedMetadata(
            title: 'House',
            image: '/house.jpg',
        ), 'https://s.test/house');

        static::assertSame('Share alt', $page->imageAlt);
    }

    #[Test]
    public function aPageWithoutAnImageHasNoAltText(): void
    {
        static::assertNull(
            $this->builder(new PageDefaults(imageAlt: 'Mapped alt'))->build(
                new FixedMetadata(),
                'https://s.test/',
            )->imageAlt,
        );
    }

    #[Test]
    public function aPageWithoutItsOwnCanonicalMovesToAnotherPath(): void
    {
        $page = $this->builder(new PageDefaults())->build(new FixedMetadata(), 'https://s.test/a');

        static::assertSame('https://s.test/b', $page->withPath('/b')->url);
    }

    #[Test]
    public function aPinnedPageIsStillPinnedAtAnotherPath(): void
    {
        $page = $this->builder(new PageDefaults(canonical: 'https://elsewhere.test/first'))->build(
            new FixedMetadata(),
            'https://s.test/a',
        );

        static::assertTrue($page->withPath('/b')->pinned);
    }

    #[Test]
    public function aRecordsOwnCanonicalStaysAtAnotherPath(): void
    {
        $page = $this->builder(new PageDefaults(canonical: 'https://elsewhere.test/first'))->build(
            new FixedMetadata(),
            'https://s.test/a',
        );

        static::assertSame('https://elsewhere.test/first', $page->withPath('/b')->url);
    }

    #[Test]
    public function aRecordsOwnOverridesWinOverItsGettersAndTheDefaults(): void
    {
        $page = $this->builder(new PageDefaults(
            ...self::FULL,
            overrides: new PageOverrides('Share title', 'Share description.', '/share.jpg', 'Share alt'),
        ))->build(
            new FixedMetadata(
                title: 'Own title',
                description: 'Own description.',
                image: '/own.jpg',
            ),
            'https://s.test/house',
        );

        static::assertSame(
            ['Share title', 'Share description.', 'https://s.test/share.jpg', 'Share alt'],
            [$page->title, $page->description, $page->image, $page->imageAlt],
        );
    }

    #[Test]
    public function blankDefaultsLeaveThePageAsTheGettersMakeIt(): void
    {
        $page = $this->builder(new PageDefaults(' ', ' ', ' ', ' ', ' ', ' ', ' '))->build(
            new FixedMetadata(),
            'https://s.test/house',
        );

        static::assertSame(
            [null, null, null, null, 'https://s.test/house', null, null],
            [$page->title, $page->description, $page->image, $page->imageAlt, $page->url, $page->robots, $page->schema],
        );
    }

    #[Test]
    public function blankOverridesLeaveTheGettersAndDefaultsAsTheyWere(): void
    {
        $page = $this->builder(new PageDefaults(
            ...self::FULL,
            overrides: new PageOverrides(' ', '', ' ', ' '),
        ))->build(new FixedMetadata(
            title: 'Own title',
            image: '/own.jpg',
        ), 'https://s.test/house');

        static::assertSame(
            ['Own title', 'Mapped description.', 'https://s.test/own.jpg', 'Mapped alt'],
            [$page->title, $page->description, $page->image, $page->imageAlt],
        );
    }

    #[Test]
    public function defaultsFillWhatTheGettersLeaveEmpty(): void
    {
        $page = $this->builder(new PageDefaults(...self::FULL))->build(new FixedMetadata(), 'https://s.test/house');

        static::assertSame(
            ['Mapped title', 'Mapped description.', 'https://s.test/mapped.jpg', 'Mapped alt'],
            [$page->title, $page->description, $page->image, $page->imageAlt],
        );
    }

    #[Test]
    public function defaultsGiveTheRobotsCanonicalAndSchema(): void
    {
        $page = $this->builder(new PageDefaults(...self::FULL))->build(new FixedMetadata(), 'https://s.test/listing');

        static::assertSame(
            ['noindex, follow', 'https://s.test/work/house', 'CreativeWork'],
            [$page->robots, $page->url, $page->schema],
        );
    }

    #[Test]
    public function defaultsHaveNoOverridesUnlessGiven(): void
    {
        static::assertEquals(new PageOverrides(), (new PageDefaults())->overrides);
    }

    #[Test]
    public function noDefaultsLeaveThePageAsTheGettersMakeIt(): void
    {
        $page = $this->builder(null)->build(new FixedMetadata(title: 'Own'), 'https://s.test/house');

        static::assertSame(['Own', 'https://s.test/house', null, null], [
            $page->title,
            $page->url,
            $page->robots,
            $page->schema,
        ]);
    }

    #[Test]
    public function theImagesAltFallsBackToTheTitleWhereTheSiteSaysSo(): void
    {
        $page = (new PageMetadataBuilder(
            imageAltFromTitle: true,
            defaults: new FixedPageDefaults(new PageDefaults()),
        ))->build(new FixedMetadata(
            title: 'House',
            image: '/house.jpg',
        ), 'https://s.test/house');

        static::assertSame('House', $page->imageAlt);
    }

    #[Test]
    public function thePageKeepsItsSchemaAtAnotherPath(): void
    {
        $page = $this->builder(new PageDefaults(schema: 'CreativeWork'))->build(
            new FixedMetadata(),
            'https://s.test/a',
        );

        static::assertSame('CreativeWork', $page->withPath('/b')->schema);
    }

    private function builder(?PageDefaults $defaults): PageMetadataBuilder
    {
        return new PageMetadataBuilder(defaults: new FixedPageDefaults($defaults));
    }
}
