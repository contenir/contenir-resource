<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Url;

use Contenir\Resource\Core\Url\ExternalUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ExternalUrlTest extends TestCase
{
    /**
     * @return array<string, array{string, string|null}>
     */
    public static function urlProvider(): array
    {
        return [
            'https kept'               => ['https://example.com/a?b=1#c', 'https://example.com/a?b=1#c'],
            'http kept'                => ['http://example.com', 'http://example.com'],
            'scheme case-insensitive'  => ['HTTPS://example.com', 'HTTPS://example.com'],
            'mailto kept'              => ['mailto:hi@example.com', 'mailto:hi@example.com'],
            'tel kept'                 => ['tel:+61400000000', 'tel:+61400000000'],
            'root relative kept'       => ['/contact', '/contact'],
            'protocol relative kept'   => ['//cdn.example.com/x', '//cdn.example.com/x'],
            'no scheme gets https'     => ['example.com/page', 'https://example.com/page'],
            'colon later is no scheme' => ['example.com/a?b=c:d', 'https://example.com/a?b=c:d'],
            'trimmed'                  => ['  example.com  ', 'https://example.com'],
            'empty'                    => ['', null],
            'whitespace'               => ['   ', null],
            'javascript rejected'      => ['javascript:alert(1)', null],
            'mixed-case javascript'    => ['JaVaScRiPt:alert(1)', null],
            'padded javascript'        => [' javascript:alert(1)', null],
            'data rejected'            => ['data:text/html,<script>', null],
            'vbscript rejected'        => ['vbscript:msgbox', null],
            'ftp rejected'             => ['ftp://files.example.com', null],
        ];
    }

    #[Test]
    #[DataProvider('urlProvider')]
    public function normalisesOrRejectsUrls(string $url, ?string $expected): void
    {
        static::assertSame($expected, ExternalUrl::normalise($url));
    }
}
