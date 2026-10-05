<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Content;

use Contenir\Resource\Core\Content\ResourceSummary;
use Contenir\Resource\Core\Tests\TestAsset\Content\RecordingSectionRenderer;
use Contenir\Resource\Core\Tests\TestAsset\Entity\ResourceFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;

use function str_repeat;

#[Group('unit')]
final class ResourceSummaryTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string}>
     */
    public static function contentProvider(): array
    {
        return [
            'markup stripped'        => ['<p>Hello <b>world</b></p>', 'Hello world'],
            'nbsp becomes a space'   => ['a&nbsp;&nbsp;b', 'a b'],
            'entities decoded'       => ['Fish &amp; chips', 'Fish & chips'],
            'quote entities decoded' => ['&quot;x&quot; &#039;y&#039; &apos;z&apos;', '"x" \'y\' \'z\''],
            'decoded markup removed' => ['&lt;b&gt;bold&lt;/b&gt;', 'bold'],
            'whitespace collapsed'   => ["  a \r\n\t b  ", 'a b'],
            'integer'                => [42, '42'],
            'true'                   => [true, '1'],
            'null'                   => [null, ''],
            'array'                  => [['a'], ''],
            'object'                 => [new stdClass(), ''],
            'stringable'             => [
                new class implements Stringable {
                    public function __toString(): string
                    {
                        return '<i>text</i>';
                    }
                },
                'text',
            ],
        ];
    }

    #[Test]
    public function anEmptySectionIsNotRendered(): void
    {
        $renderer = new RecordingSectionRenderer();

        static::assertSame(
            ['', []],
            [(new ResourceSummary($renderer))->summarise(ResourceFactory::section()), $renderer->rendered],
        );
    }

    #[Test]
    public function aResourceWithoutDescriptionOrSectionSummarisesToNothing(): void
    {
        static::assertSame(
            '',
            (new ResourceSummary(new RecordingSectionRenderer()))->summarise(ResourceFactory::make()),
        );
    }

    #[Test]
    public function aSectionIsNotRenderedWithoutARenderer(): void
    {
        static::assertSame('', (new ResourceSummary())->summarise(ResourceFactory::section(section: '{"a":1}')));
    }

    #[Test]
    public function cutsLongTextWithAnEllipsis(): void
    {
        static::assertSame('éééé…', (new ResourceSummary(length: 4))->summarise('ééééé'));
    }

    #[Test]
    public function defaultsToTwoHundredAndFortyNineCharacters(): void
    {
        static::assertSame(
            str_repeat('a', times: 249) . '…',
            (new ResourceSummary())->summarise(str_repeat('a', times: 250)),
        );
    }

    #[Test]
    public function keepsTextOfExactlyTheLength(): void
    {
        static::assertSame('éééé', (new ResourceSummary(length: 4))->summarise('éééé'));
    }

    #[Test]
    public function rendersTheSectionWhenThereIsNoDescription(): void
    {
        $renderer = new RecordingSectionRenderer();
        $resource = ResourceFactory::section(
            description: ' ',
            section: '{"a":1}',
        );

        static::assertSame(
            ['Rendered section', [$resource]],
            [(new ResourceSummary($renderer))->summarise($resource), $renderer->rendered],
        );
    }

    #[Test]
    public function summarisesAResourceDescription(): void
    {
        $resource              = ResourceFactory::make();
        $resource->description = '<p>The description</p>';

        static::assertSame('The description', (new ResourceSummary())->summarise($resource));
    }

    #[Test]
    #[DataProvider('contentProvider')]
    public function summarisesContentAsPlainText(mixed $content, string $expected): void
    {
        static::assertSame($expected, (new ResourceSummary())->summarise($content));
    }

    #[Test]
    public function theDescriptionWinsOverTheSection(): void
    {
        $renderer = new RecordingSectionRenderer();
        $resource = ResourceFactory::section(
            description: 'Described',
            section: '{"a":1}',
        );

        static::assertSame(
            ['Described', []],
            [(new ResourceSummary($renderer))->summarise($resource), $renderer->rendered],
        );
    }
}
