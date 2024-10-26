<?php

declare(strict_types=1);

namespace Contenir\Resource\View\Helper;

use Contenir\Metadata\MetadataInterface;
use Laminas\View\Helper\AbstractHelper;

use function array_count_values;
use function array_keys;
use function array_slice;
use function html_entity_decode;
use function implode;
use function in_array;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function rtrim;
use function str_word_count;
use function strip_tags;
use function strlen;
use function trim;
use function uasort;

use const ENT_COMPAT;
use const ENT_QUOTES;

class ResourceMeta extends AbstractHelper
{
    public function __invoke(?MetadataInterface $resource = null): void
    {
        if ($resource === null) {
            return;
        }

        $description  = null;
        $doctype      = $this->view->Doctype()->getDoctype();
        $uri          = $this->view->ServerUrl(true);
        $canonicalUrl = preg_replace('/\?.+/', '', $uri);

        $this->view->HeadLink(['rel' => 'canonical', 'href' => $canonicalUrl]);

        $this->view->HeadMeta()->setProperty('og:type', 'website');
        $this->view->HeadMeta()->setProperty('og:url', $uri);
        $this->view->HeadMeta()->setProperty('twitter:url', $uri);

        if ($resource->getMetaTitle()) {
            $this->view->HeadTitle($resource->getMetaTitle(), 'SET');
            $this->view->HeadMeta()->setProperty('og:title', $resource->getMetaTitle());
            $this->view->HeadMeta()->setProperty('twitter:title', $resource->getMetaTitle());
        }

        if ($resource->getMetaDescription()) {
            $description = $this->stripTags($this->view->RichContent($resource->getMetaDescription()));
        }

        if ($description) {
            $this->view->HeadMeta()->setName('description', $this->getText($description));
            $this->view->HeadMeta()->setProperty('og:description', $this->getText($description));
            $this->view->HeadMeta()->setProperty('twitter:description', $this->getText($description));
        }

        if ($resource->getMetaImage()) {
            $this->view->HeadMeta()->setProperty('og:image', $this->view->Asset($resource->getMetaImage()));
            $this->view->HeadMeta()->setProperty('twitter:image', $this->view->Asset($resource->getMetaImage()));
        }

        if ($resource->getMetaModified() || $resource->getMetaPublish()) {
            $modified = $resource->getMetaPublish() ? $resource->getMetaPublish() : $resource->getMetaModified();
            if ($modified) {
                $this->view->HeadMeta()->setProperty('og:updated_time', $modified->format('Y-m-d H:i:s'));
            }
        }
    }

    protected function stripTags($text): string
    {
        return strip_tags(html_entity_decode($text, ENT_COMPAT, 'UTF-8'));
    }

    /**
     * banned words in english feel free to change them
     */
    public array $banned_words = [];

    /**
     * min len for a word in the keywords
     */
    public int $min_word_length = 4;

    /**
     * SEO for text length
     * returns a text with text
     *
     * @param         $text
     * @param integer $length of the description
     */
    public function getText($text, int $length = 160): string
    {
        return $this->limitCharacters(
            $this->clean($text),
            $length,
            '',
            true
        );
    }

    /**
     * gets the keyword from the text in the construct
     *
     * @param         $text
     * @param integer $max_keys number of keywords
     */
    public function getKeywords($text, int $max_keys = 25): string
    {
        //array to keep word->number of repetitions
        $wordcount = array_count_values(str_word_count($this->clean($text), 1));

        //remove small words
        foreach ($wordcount as $key => $value) {
            if ((strlen($key) <= $this->min_word_length) or in_array($key, $this->banned_words)) {
                unset($wordcount[$key]);
            }
        }

        //sort keywords from most repetitions to less
        uasort($wordcount, ['self', 'cmp']);

        //keep only X keywords
        $wordcount = array_slice($wordcount, 0, $max_keys);

        //return keywords on a string
        return implode(', ', array_keys($wordcount));
    }

    /**
     * cleans an string from HTML spaces etc...
     */
    private function clean(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/[\x{202F}\x{00A0}\x{2000}\x{2001}\x{2003}]+/u', ' ', $text);
        $text = preg_replace('/[\x{2018}\x{2019}]+/u', '\'', $text);
        $text = preg_replace('/[\x{201c}\x{201d}]+/u', '"', $text);
        $text = preg_replace('/(.*)/', '$1', $text);
        return trim($text);
    }

    /**
     * sort for uasort descendent numbers , compares values
     */
    private function cmp(int $a, int $b): int
    {
        if ($a == $b) {
            return 0;
        }

        return $a < $b ? 1 : -1;
    }

    /**
     * Limits a phrase to a given number of characters.
     * ported from kohana text class, so this class can remain as independent as possible
     *     $text = Text::limitCharacters($text);
     *
     * @param string      $str            phrase to limit characters of
     * @param integer     $limit          number of characters to limit to
     * @param string|null $end_char       end character or entity
     * @param boolean     $preserve_words enable or disable the preservation of words while limiting
     */
    private function limitCharacters(string $str, int $limit = 100, ?string $end_char = null, bool $preserve_words = false): string
    {
        $end_char = $end_char ?? '…';
        if (trim($str) === '' or mb_strlen($str) <= $limit) {
            return $str;
        }

        if ($limit <= 0) {
            return $end_char;
        }

        if ($preserve_words === false) {
            return rtrim(mb_substr($str, 0, $limit)) . $end_char;
        }

        // Don't preserve words. The limit is considered the top limit.
        // No strings with a length longer than $limit should be returned.
        if (! preg_match('/^.{0,' . $limit . '}\s/us', $str, $matches)) {
            return $end_char;
        }

        return rtrim($matches[0]) . (strlen($matches[0]) === strlen($str) ? '' : $end_char);
    }
}
