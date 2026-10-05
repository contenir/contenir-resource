<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Content;

/**
 * Renders a resource's section content to HTML, for ResourceSummary. The
 * framework adapters implement it with their template engine.
 *
 * @api
 */
interface SectionRendererInterface
{
    public function render(SectionAwareInterface $resource): string;
}
