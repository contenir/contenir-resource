<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Integration\Metadata;

use Contenir\Resource\Core\Metadata\LocalImageSizeResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function base64_decode;
use function basename;
use function file_put_contents;
use function is_dir;
use function is_file;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

/**
 * Measures real files in a temporary web root.
 */
#[Group('integration')]
final class LocalImageSizeResolverTest extends TestCase
{
    /** A 3 × 2 pixel PNG. */
    private const string PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAMAAAACCAIAAAASFvFNAAAAEklEQVR4nGP4z8DAwMDAxMDAAAAeAAJyEw4XAAAAAElFTkSuQmCC';

    /** A 1 × 1 pixel PNG. */
    private const string PIXEL = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    private string $root;

    private string $outside;

    /**
     * @return array<string, array{string}>
     */
    public static function unmeasuredProvider(): array
    {
        return [
            'an absolute URL'         => ['https://cdn.test/library/hero.png'],
            'a protocol-relative URL' => ['//cdn.test/library/hero.png'],
            'a URL on another host'   => ['//library/hero.png'],
            'a missing file'          => ['/library/missing.png'],
            'a directory'             => ['/library'],
            'not an image'            => ['/library/notes.txt'],
        ];
    }

    #[Test]
    public function anImageOutsideTheRootIsNotMeasured(): void
    {
        static::assertNull((new LocalImageSizeResolver($this->root))->size('/../' . basename($this->outside)));
    }

    #[Test]
    public function anImageUnderTheRootIsMeasured(): void
    {
        static::assertSame([3, 2], (new LocalImageSizeResolver($this->root))->size('/library/hero.png'));
    }

    #[Test]
    public function aOnePixelImageIsMeasured(): void
    {
        static::assertSame([1, 1], (new LocalImageSizeResolver($this->root))->size('/library/pixel.png'));
    }

    #[Test]
    public function aPathWithoutALeadingSlashIsUnderTheRootToo(): void
    {
        static::assertSame([3, 2], (new LocalImageSizeResolver("{$this->root}/"))->size('library/hero.png'));
    }

    #[Test]
    public function aPathWithRepeatedSlashesIsStillUnderTheRoot(): void
    {
        static::assertSame([3, 2], (new LocalImageSizeResolver($this->root))->size('/library//hero.png'));
    }

    #[Test]
    public function aRootThatDoesNotExistMeasuresNothing(): void
    {
        static::assertNull((new LocalImageSizeResolver("{$this->root}/nowhere"))->size('/library/hero.png'));
    }

    #[Test]
    #[DataProvider('unmeasuredProvider')]
    public function otherPathsAreNotMeasured(string $image): void
    {
        static::assertNull((new LocalImageSizeResolver($this->root))->size($image));
    }

    protected function setUp(): void
    {
        $this->root    = sys_get_temp_dir() . '/' . uniqid('contenir-image-root-', more_entropy: true);
        $this->outside = "{$this->root}-outside.png";
        mkdir("{$this->root}/library", recursive: true);
        file_put_contents("{$this->root}/library/hero.png", base64_decode(self::PNG, strict: true));
        file_put_contents("{$this->root}/library/notes.txt", data: 'not an image');
        file_put_contents("{$this->root}/library/pixel.png", base64_decode(self::PIXEL, strict: true));
        file_put_contents($this->outside, base64_decode(self::PNG, strict: true));
    }

    protected function tearDown(): void
    {
        $files = [
            "{$this->root}/library/hero.png",
            "{$this->root}/library/notes.txt",
            "{$this->root}/library/pixel.png",
            $this->outside,
        ];
        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            unlink($file);
        }

        foreach (["{$this->root}/library", $this->root] as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            rmdir($directory);
        }
    }
}
