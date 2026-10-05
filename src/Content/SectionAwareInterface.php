<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Content;

/**
 * A resource with structured "section" content (for example the decoded
 * JSON of a section column), summarised by rendering it when the resource
 * has no description.
 *
 * @api
 */
interface SectionAwareInterface
{
    /**
     * The section content handed to the section renderer.
     */
    public function getSection(): mixed;

    /**
     * Whether there is section content to render.
     */
    public function hasSection(): bool;
}
