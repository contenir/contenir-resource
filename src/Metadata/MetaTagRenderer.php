<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use function htmlspecialchars;
use function implode;
use function sprintf;

use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * Renders PageMetadata as escaped HTML for any template engine: output it
 * unescaped (for example `{{ meta|raw }}` in Twig) inside <head>.
 *
 * @api
 */
final class MetaTagRenderer
{
    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, encoding: 'UTF-8');
    }

    /**
     * The <title> element (when there is a title), then the tags.
     */
    public function render(PageMetadata $metadata): string
    {
        $tags = $this->renderTags($metadata);

        return null === $metadata->title
            ? $tags
            : sprintf("<title>%s</title>\n%s", self::escape($metadata->title), $tags);
    }

    /**
     * The canonical link and the meta tags, one per line, without <title>.
     */
    public function renderTags(PageMetadata $metadata): string
    {
        $lines = [];
        foreach ($metadata->getLinks() as $link) {
            $lines[] = sprintf('<link rel="%s" href="%s">', self::escape($link['rel']), self::escape($link['href']));
        }

        foreach ($metadata->getMetaTags() as $tag) {
            $lines[] = sprintf(
                '<meta %s="%s" content="%s">',
                $tag['attribute'],
                self::escape($tag['key']),
                self::escape($tag['content']),
            );
        }

        return implode("\n", $lines);
    }
}
