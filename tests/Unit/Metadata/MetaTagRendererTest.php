<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Metadata;

use Contenir\Resource\Core\Metadata\MetaTagRenderer;
use Contenir\Resource\Core\Metadata\PageMetadata;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function explode;

#[Group('unit')]
final class MetaTagRendererTest extends TestCase
{
    #[Test]
    public function escapesEveryValue(): void
    {
        $html = (new MetaTagRenderer())->render(new PageMetadata(
            url: 'https://s.test/"><script>',
            title: "</title><script>alert('x')</script>",
            description: "a & b \xC3\x28",
        ));

        static::assertSame(
            [
                '<title>&lt;/title&gt;&lt;script&gt;alert(&apos;x&apos;)&lt;/script&gt;</title>',
                '<link rel="canonical" href="https://s.test/&quot;&gt;&lt;script&gt;">',
                "<meta name=\"description\" content=\"a &amp; b \u{FFFD}(\">",
            ],
            [explode("\n", $html)[0], explode("\n", $html)[1], explode("\n", $html)[7]],
        );
    }

    #[Test]
    public function rendersNameTagsWithTheNameAttribute(): void
    {
        $html = (new MetaTagRenderer())->renderTags(new PageMetadata('https://s.test/', description: 'Hi'));

        static::assertStringContainsString("\n<meta name=\"description\" content=\"Hi\">\n", $html);
    }

    #[Test]
    public function rendersNoTitleElementWithoutATitle(): void
    {
        $renderer = new MetaTagRenderer();
        $metadata = new PageMetadata('https://s.test/a');

        static::assertSame($renderer->renderTags($metadata), $renderer->render($metadata));
    }

    #[Test]
    public function rendersTheTitleThenTheTags(): void
    {
        $html = (new MetaTagRenderer())->render(new PageMetadata('https://s.test/a', title: 'About'));

        static::assertSame(
            "<title>About</title>\n"
                . "<link rel=\"canonical\" href=\"https://s.test/a\">\n"
                . "<meta property=\"og:type\" content=\"website\">\n"
                . "<meta property=\"og:url\" content=\"https://s.test/a\">\n"
                . "<meta property=\"twitter:url\" content=\"https://s.test/a\">\n"
                . "<meta property=\"og:title\" content=\"About\">\n"
                . '<meta property="twitter:title" content="About">',
            $html,
        );
    }
}
