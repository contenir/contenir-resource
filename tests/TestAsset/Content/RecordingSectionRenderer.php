<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\TestAsset\Content;

use Contenir\Resource\Core\Content\SectionAwareInterface;
use Contenir\Resource\Core\Content\SectionRendererInterface;
use Override;

/**
 * Renders a fixed HTML string and records the resources it was given.
 */
final class RecordingSectionRenderer implements SectionRendererInterface
{
    /** @var list<SectionAwareInterface> */
    public array $rendered = [];

    public function __construct(
        private readonly string $html = "<section><h2>Rendered</h2>\n<p>section</p></section>",
    ) {}

    #[Override]
    public function render(SectionAwareInterface $resource): string
    {
        $this->rendered[] = $resource;

        return $this->html;
    }
}
