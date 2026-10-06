<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use function array_count_values;
use function array_filter;
use function array_keys;
use function array_slice;
use function arsort;
use function html_entity_decode;
use function implode;
use function in_array;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function strip_tags;
use function trim;

use const ARRAY_FILTER_USE_KEY;
use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * Plain-text helpers for page metadata: markup-free, whitespace-normalised
 * text, word-preserving summaries and keyword extraction.
 *
 * @api
 */
final class MetaText
{
    /**
     * Decode entities, strip markup, turn typographic quotes into plain
     * ones and collapse every run of whitespace (non-breaking and other
     * Unicode spaces included) into one space.
     */
    public static function clean(string $text): string
    {
        $text = strip_tags(html_entity_decode($text, ENT_QUOTES | ENT_HTML5, encoding: 'UTF-8'));
        $text = preg_replace(
            ['/[\s\x{00A0}\x{2000}-\x{200A}\x{202F}]+/u', '/[\x{2018}\x{2019}]/u', '/[\x{201C}\x{201D}]/u'],
            [' ', "'", '"'],
            $text,
        ) ?? '';

        return trim($text);
    }

    /**
     * The most frequent words of the cleaned text, most frequent first,
     * comma separated. Words of $minWordLength characters or fewer, and banned
     * words, are left out.
     *
     * @param list<string> $bannedWords
     */
    public static function keywords(
        string $text,
        int $maxKeywords = 25,
        int $minWordLength = 4,
        array $bannedWords = [],
    ): string {
        $counts = array_filter(
            array_count_values(self::words(self::clean($text))),
            static fn(string $word): bool => (
                mb_strlen($word) > $minWordLength
                && ! in_array($word, $bannedWords, strict: true)
            ),
            ARRAY_FILTER_USE_KEY,
        );
        arsort($counts);

        return implode(', ', array_slice(array_keys($counts), offset: 0, length: $maxKeywords));
    }

    /**
     * The cleaned text, cut to at most $length characters at the last word
     * break, or mid-word when the first $length characters hold no break.
     */
    public static function summarise(string $text, int $length = 160): string
    {
        $text = self::clean($text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $matches = [];
        if (1 !== preg_match("/^.{0,{$length}}(?=\\s)/su", $text, $matches)) {
            return mb_substr($text, start: 0, length: $length);
        }

        return $matches[0] ?? '';
    }

    /**
     * The words of a text: runs of letters, apostrophes and hyphens.
     *
     * @return array<array-key, string>
     */
    private static function words(string $text): array
    {
        $matches = [];
        preg_match_all("/[\\p{L}'-]+/u", $text, $matches);

        return $matches[0] ?? [];
    }
}
