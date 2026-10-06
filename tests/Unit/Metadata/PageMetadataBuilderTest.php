<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Metadata;

use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\FixedMetadata;
use Contenir\Resource\Core\Tests\TestAsset\Metadata\RecordingImageResolver;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function mb_strlen;
use function str_repeat;

#[Group('unit')]
final class PageMetadataBuilderTest extends TestCase
{
    /**
     * @return array<string, array{string|null}>
     */
    public static function blankDescriptionProvider(): array
    {
        return [
            'not set'     => [null],
            'whitespace'  => ['  '],
            'only markup' => ['<p> </p>'],
        ];
    }

    /**
     * @return array<string, array{string|null, string, string}>
     */
    public static function canonicalProvider(): array
    {
        return [
            'request origin and path'          => [null, 'https://site.test/about', 'https://site.test/about'],
            'port kept'                        => [null, 'http://site.test:8080/about', 'http://site.test:8080/about'],
            'query and fragment dropped'       => [
                null,
                'https://site.test/about?utm=x#top',
                'https://site.test/about',
            ],
            'user info dropped'                => [null, 'https://user:pw@site.test/about', 'https://site.test/about'],
            'empty path is the root'           => [null, 'https://site.test', 'https://site.test/'],
            'scheme defaults to http'          => [null, '//site.test/about', 'http://site.test/about'],
            'no host means no origin'          => [null, '/about', '/about'],
            'unparseable url'                  => [null, 'http://:80', '/'],
            'base url replaces a spoofed host' => [
                'https://www.site.test',
                'https://evil.test/about?x=1',
                'https://www.site.test/about',
            ],
            'base url trailing slash trimmed'  => [
                'https://www.site.test/',
                'https://evil.test/about',
                'https://www.site.test/about',
            ],
            'base url with a path prefix'      => [
                'https://site.test/en',
                'https://site.test/about',
                'https://site.test/en/about',
            ],
        ];
    }

    #[Test]
    #[DataProvider('blankDescriptionProvider')]
    public function aBlankDescriptionIsNotSet(?string $description): void
    {
        $metadata = (new PageMetadataBuilder())->build(new FixedMetadata(description: $description), 'https://s.test/');

        static::assertNull($metadata->description);
    }

    #[Test]
    public function aBlankImageIsNotResolved(): void
    {
        $images   = new RecordingImageResolver();
        $metadata = (new PageMetadataBuilder($images))->build(new FixedMetadata(image: ' '), 'https://s.test/');

        static::assertSame([null, []], [$metadata->image, $images->calls]);
    }

    #[Test]
    public function aBlankTitleIsNotSet(): void
    {
        static::assertNull((new PageMetadataBuilder())->build(new FixedMetadata(title: ' '), 'https://s.test/')->title);
    }

    #[Test]
    public function aDescriptionWithoutBreaksIsCutAtOneHundredAndSixtyCharacters(): void
    {
        $metadata = (new PageMetadataBuilder())->build(
            new FixedMetadata(description: str_repeat('a', times: 170)),
            'https://s.test/',
        );

        static::assertSame(160, mb_strlen((string) $metadata->description));
    }

    #[Test]
    public function theDefaultResolverIsUsedWhenNoneIsGiven(): void
    {
        $metadata = (new PageMetadataBuilder())->build(new FixedMetadata(image: 'i.jpg'), 'https://s.test/x');

        static::assertSame('https://s.test/i.jpg', $metadata->image);
    }

    #[Test]
    public function theDescriptionDefaultsToOneHundredAndSixtyCharacters(): void
    {
        $metadata = (new PageMetadataBuilder())->build(
            new FixedMetadata(description: str_repeat('word ', times: 40)),
            'https://s.test/',
        );

        static::assertSame(159, mb_strlen((string) $metadata->description));
    }

    #[Test]
    public function theDescriptionIsCleanedAndSummarised(): void
    {
        $builder  = new PageMetadataBuilder(descriptionLength: 9);
        $metadata = $builder->build(new FixedMetadata(description: '<p>one &amp; two three</p>'), 'https://s.test/');

        static::assertSame('one & two', $metadata->description);
    }

    #[Test]
    public function theImageIsResolvedAgainstTheBaseUrl(): void
    {
        $images   = new RecordingImageResolver();
        $metadata = (new PageMetadataBuilder($images, 'https://www.s.test/'))->build(
            new FixedMetadata(image: 'i.jpg'),
            'https://evil.test/about',
        );

        static::assertSame('https://www.s.test|i.jpg', $metadata->image);
    }

    #[Test]
    public function theImageIsTrimmedAndResolvedAgainstTheOrigin(): void
    {
        $images   = new RecordingImageResolver();
        $metadata = (new PageMetadataBuilder($images))->build(
            new FixedMetadata(image: ' /i.jpg '),
            'https://s.test/about',
        );

        static::assertSame(['https://s.test|/i.jpg', [['/i.jpg', 'https://s.test']]], [
            $metadata->image,
            $images->calls,
        ]);
    }

    #[Test]
    public function theModifiedDateIsTheFallbackUpdatedTime(): void
    {
        $modified = new DateTimeImmutable('2024-01-01');
        $metadata = (new PageMetadataBuilder())->build(new FixedMetadata(modified: $modified), 'https://s.test/');

        static::assertSame($modified, $metadata->updated);
    }

    #[Test]
    public function thePublishDateIsPreferredForTheUpdatedTime(): void
    {
        $publish  = new DateTimeImmutable('2020-01-01');
        $metadata = (new PageMetadataBuilder())->build(
            new FixedMetadata(
                modified: new DateTimeImmutable('2024-01-01'),
                publish: $publish,
            ),
            'https://s.test/',
        );

        static::assertSame($publish, $metadata->updated);
    }

    #[Test]
    public function theTitleIsKeptAsIs(): void
    {
        $metadata = (new PageMetadataBuilder())->build(new FixedMetadata(title: 'About <us>'), 'https://s.test/');

        static::assertSame('About <us>', $metadata->title);
    }

    #[Test]
    #[DataProvider('canonicalProvider')]
    public function theUrlIsTheCanonicalRequestUrl(?string $baseUrl, string $requestUrl, string $expected): void
    {
        $builder = new PageMetadataBuilder(baseUrl: $baseUrl);

        static::assertSame($expected, $builder->build(new FixedMetadata(), $requestUrl)->url);
    }
}
