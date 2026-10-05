<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Metadata;

use Contenir\Resource\Core\Metadata\MetaText;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function chr;
use function explode;
use function implode;
use function intdiv;
use function rtrim;
use function str_repeat;

#[Group('unit')]
final class MetaTextTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function cleanProvider(): array
    {
        return [
            'markup stripped'        => ['<p>Fish <b>and</b> chips</p>', 'Fish and chips'],
            'entities decoded'       => ['Fish &amp; chips &quot;x&quot; &#039;y&#039;', 'Fish & chips "x" \'y\''],
            'encoded markup removed' => ['&lt;script&gt;alert(1)&lt;/script&gt;ok', 'alert(1)ok'],
            'whitespace collapsed'   => ["a \n\t b", 'a b'],
            'non-breaking spaces'    => ["a&nbsp;\u{00A0}b\u{2003}c\u{202F}d\u{2000}e\u{200A}f", 'a b c d e f'],
            'curly single quotes'    => ["\u{2018}hi\u{2019}", "'hi'"],
            'curly double quotes'    => ["\u{201C}hi\u{201D}", '"hi"'],
            'trimmed'                => ['  padded  ', 'padded'],
            'empty'                  => ['', ''],
        ];
    }

    /**
     * @return array<string, array{string, int, string}>
     */
    public static function summariseProvider(): array
    {
        return [
            'short text unchanged'           => ['one two three', 13, 'one two three'],
            'cut at the last break'          => ['one two three four', 9, 'one two'],
            'break right after the limit'    => ['one two', 3, 'one'],
            'no break: cut mid word'         => ['abcdefghij klm', 5, 'abcde'],
            'counted in characters'          => ['ééééé éé', 5, 'ééééé'],
            'cleaned first'                  => ['<p>one</p>  <p>two</p>', 7, 'one two'],
            'newline in the text is a break' => ["one\ntwo three", 8, 'one two'],
            'multibyte text that fits'       => ['éé éé', 5, 'éé éé'],
        ];
    }

    #[Test]
    #[DataProvider('cleanProvider')]
    public function cleanProducesPlainText(string $html, string $expected): void
    {
        static::assertSame($expected, MetaText::clean($html));
    }

    #[Test]
    public function keywordsAreTheMostFrequentLongWords(): void
    {
        static::assertSame(
            'banana, apple',
            MetaText::keywords('apple apple banana banana banana cherry tiny tiny tiny tiny', maxKeywords: 2),
        );
    }

    #[Test]
    public function keywordsCountCharactersAndKeepLetters(): void
    {
        static::assertSame(
            "cafés, l'été, x-ray",
            MetaText::keywords("cafés éééé l'été x-ray, nope", maxKeywords: 25, minWordLength: 4),
        );
    }

    #[Test]
    public function keywordsDefaultToTwentyFiveWords(): void
    {
        $words = [];
        for ($i = 0; $i < 30; ++$i) {
            $words[] = 'word' . chr(97 + intdiv($i, num2: 26)) . chr(97 + ($i % 26));
        }

        static::assertCount(25, explode(', ', MetaText::keywords(implode(' ', $words))));
    }

    #[Test]
    public function keywordsDefaultToWordsLongerThanFourBytes(): void
    {
        static::assertSame('words, these, count', MetaText::keywords('<b>these</b> words words count four'));
    }

    #[Test]
    public function keywordsLeaveOutShortAndBannedWords(): void
    {
        static::assertSame(
            'Apple, apple',
            MetaText::keywords('Apple apple pears plum', maxKeywords: 25, minWordLength: 4, bannedWords: ['pears']),
        );
    }

    #[Test]
    public function keywordsOfAnEmptyTextAreEmpty(): void
    {
        static::assertSame('', MetaText::keywords(''));
    }

    #[Test]
    #[DataProvider('summariseProvider')]
    public function summariseCutsAtAWordBreak(string $text, int $length, string $expected): void
    {
        static::assertSame($expected, MetaText::summarise($text, $length));
    }

    #[Test]
    public function summariseCutsAtOneHundredAndSixtyCharactersByDefault(): void
    {
        static::assertSame(str_repeat('a', times: 160), MetaText::summarise(str_repeat('a', times: 170)));
    }

    #[Test]
    public function summariseDefaultsToOneHundredAndSixtyCharacters(): void
    {
        $text = str_repeat('word ', times: 40);

        static::assertSame(rtrim(str_repeat('word ', times: 32)), MetaText::summarise($text));
    }
}
