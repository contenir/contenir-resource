<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Content;

use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Stringable;

use function html_entity_decode;
use function is_scalar;
use function mb_strlen;
use function mb_substr;
use function preg_replace;
use function strip_tags;
use function trim;

use const ENT_HTML5;
use const ENT_QUOTES;

/**
 * A short plain-text summary of a resource or of a piece of content: the
 * resource's description, or else its rendered section content, with markup
 * removed, whitespace collapsed and cut to $length characters with an
 * ellipsis.
 *
 * The result never contains markup, but it is still text: escape it when
 * writing it into HTML.
 *
 * @api
 */
final readonly class ResourceSummary
{
    /**
     * @param int $length The maximum length in characters, before the ellipsis.
     */
    public function __construct(
        private ?SectionRendererInterface $sections = null,
        private int $length = 249,
    ) {}

    /**
     * @param mixed $content A resource entity, a string or anything stringable; anything else summarises to ''.
     */
    public function summarise(mixed $content): string
    {
        $text = match (true) {
            $content instanceof AbstractResourceEntity => $this->resourceText($content),
            is_scalar($content), $content instanceof Stringable => (string) $content,
            default => '',
        };

        $text = strip_tags(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, encoding: 'UTF-8'));
        $text = trim(preg_replace('/\s+/u', replacement: ' ', subject: $text) ?? '');

        return mb_strlen($text) > $this->length ? mb_substr($text, start: 0, length: $this->length) . '…' : $text;
    }

    private function resourceText(AbstractResourceEntity $resource): string
    {
        $description = $resource->description;
        if (null !== $description && '' !== trim($description)) {
            return $description;
        }

        if (null !== $this->sections && $resource instanceof SectionAwareInterface && $resource->hasSection()) {
            return $this->sections->render($resource);
        }

        return '';
    }
}
