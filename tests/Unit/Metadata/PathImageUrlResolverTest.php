<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Metadata;

use Contenir\Resource\Core\Metadata\PathImageUrlResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PathImageUrlResolverTest extends TestCase
{
    /**
     * @return array<string, array{string|null, string, string, string|null}>
     */
    public static function imageProvider(): array
    {
        return [
            'https url kept'                    => [
                null,
                'https://cdn.test/i.jpg',
                'https://site.test',
                'https://cdn.test/i.jpg',
            ],
            'http url kept, any case'           => [
                null,
                'HTTP://cdn.test/i.jpg',
                'https://site.test',
                'HTTP://cdn.test/i.jpg',
            ],
            'protocol relative takes scheme'    => [
                null,
                '//cdn.test/i.jpg',
                'http://site.test',
                'http://cdn.test/i.jpg',
            ],
            'protocol relative, unknown origin' => [null, '//cdn.test/i.jpg', '', 'https://cdn.test/i.jpg'],
            'path on the origin'                => [
                null,
                '/assets/i.jpg',
                'https://site.test',
                'https://site.test/assets/i.jpg',
            ],
            'relative path on the origin'       => [
                null,
                'assets/i.jpg',
                'https://site.test/',
                'https://site.test/assets/i.jpg',
            ],
            'path on the base url'              => [
                'https://img.test/media/',
                'assets/i.jpg',
                'https://site.test',
                'https://img.test/media/assets/i.jpg',
            ],
            'base url without slash'            => [
                'https://img.test/media',
                '/i.jpg',
                'https://site.test',
                'https://img.test/media/i.jpg',
            ],
            'javascript rejected'               => [null, 'javascript:alert(1)', 'https://site.test', null],
            'uppercase scheme rejected'         => [null, 'JAVASCRIPT:alert(1)', 'https://site.test', null],
            'data uri rejected'                 => [null, 'data:image/png;base64,AAAA', 'https://site.test', null],
            'ftp rejected'                      => [null, 'ftp://files.test/i.jpg', 'https://site.test', null],
            'http embedded later is a path'     => [
                null,
                'x/http://i.jpg',
                'https://site.test',
                'https://site.test/x/http://i.jpg',
            ],
        ];
    }

    #[Test]
    #[DataProvider('imageProvider')]
    public function resolvesImagesToAbsoluteUrls(
        ?string $baseUrl,
        string $image,
        string $origin,
        ?string $expected,
    ): void {
        static::assertSame($expected, (new PathImageUrlResolver($baseUrl))->resolve($image, $origin));
    }
}
