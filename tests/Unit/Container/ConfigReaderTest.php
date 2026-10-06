<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Container;

use Contenir\Resource\Core\Container\ConfigReader;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Resource\Core\Tests\TestAsset\Entity\NotAnEntity;
use Contenir\Resource\Core\Tests\TestAsset\Entity\SectionResourceEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[Group('unit')]
final class ConfigReaderTest extends TestCase
{
    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidBaseUrlProvider(): array
    {
        return [
            'relative path'    => ['/site'],
            'no scheme'        => ['www.example.com'],
            'other scheme'     => ['ftp://example.com'],
            'with query'       => ['https://example.com/?a=1'],
            'with fragment'    => ['https://example.com/#a'],
            'with whitespace'  => ['https://example.com/a b'],
            'trailing newline' => ["https://example.com\n"],
            'scheme only'      => ['https://'],
            'not a string'     => [5],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidIntProvider(): array
    {
        return [
            'zero'           => [0],
            'negative'       => [-1],
            'numeric string' => ['5'],
            'float'          => [5.0],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidStringProvider(): array
    {
        return [
            'empty string' => [''],
            'integer'      => [5],
            'array'        => [['a']],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validBaseUrlProvider(): array
    {
        return [
            'origin'           => ['https://www.example.com'],
            'with port'        => ['http://localhost:8080'],
            'with path'        => ['https://example.com/en/'],
            'uppercase scheme' => ['HTTPS://example.com'],
        ];
    }

    /**
     * @param array<string, mixed> $values
     */
    private static function reader(array $values, string $section = ConfigReader::SECTION): ConfigReader
    {
        return ConfigReader::fromContainer(new ArrayContainer(['config' => [$section => $values]]), $section);
    }

    #[Test]
    public function aMissingConfigServiceReadsAsEmpty(): void
    {
        static::assertSame('d', ConfigReader::fromContainer(new ArrayContainer())->string('k', default: 'd'));
    }

    #[Test]
    public function aMissingSectionReadsAsEmpty(): void
    {
        $reader = ConfigReader::fromContainer(new ArrayContainer(['config' => ['other' => ['k' => 'v']]]));

        static::assertSame('d', $reader->string('k', default: 'd'));
    }

    #[Test]
    public function anAbsentBaseUrlIsNull(): void
    {
        static::assertNull(self::reader([])->optionalBaseUrl('k'));
    }

    #[Test]
    public function aNamedSectionIsRead(): void
    {
        static::assertSame('v', self::reader(['k' => 'v'], 'workflow_manager')->string('k', default: 'd'));
    }

    #[Test]
    #[DataProvider('invalidBaseUrlProvider')]
    public function anInvalidBaseUrlIsRejected(mixed $value): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessageMatches('/^Config "contenir_resource\.k" must be /');

        self::reader(['k' => $value])->optionalBaseUrl('k');
    }

    #[Test]
    public function anInvalidBaseUrlNamesTheExpectation(): void
    {
        $this->expectExceptionMessage('Config "contenir_resource.k" must be an absolute http(s) URL, got string');

        self::reader(['k' => '/x'])->optionalBaseUrl('k');
    }

    #[Test]
    #[DataProvider('invalidIntProvider')]
    public function anInvalidPositiveIntIsRejected(mixed $value): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Config "contenir_resource.k" must be a positive integer');

        self::reader(['k' => $value])->positiveInt('k', default: 3);
    }

    #[Test]
    #[DataProvider('invalidStringProvider')]
    public function anInvalidStringIsRejected(mixed $value): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Config "contenir_resource.k" must be a non-empty string');

        self::reader(['k' => $value])->optionalString('k');
    }

    #[Test]
    public function aNonArrayConfigServiceReadsAsEmpty(): void
    {
        $reader = ConfigReader::fromContainer(new ArrayContainer(['config' => 'nonsense']));

        static::assertSame('d', $reader->string('k', default: 'd'));
    }

    #[Test]
    public function aNullStringTakesTheDefault(): void
    {
        static::assertSame('d', self::reader(['k' => null])->string('k', default: 'd'));
    }

    #[Test]
    public function aSectionThatIsNotAnArrayIsRejected(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Config "contenir_resource" must be an array, got string');

        ConfigReader::fromContainer(new ArrayContainer(['config' => ['contenir_resource' => 'x']]));
    }

    #[Test]
    #[DataProvider('validBaseUrlProvider')]
    public function aValidBaseUrlIsReturned(string $url): void
    {
        static::assertSame($url, self::reader(['k' => $url])->optionalBaseUrl('k'));
    }

    #[Test]
    public function classNameRejectsAnUnrelatedClass(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(sprintf(
            'Config "contenir_resource.k" must name a concrete subclass of %s, got "%s"',
            AbstractResourceEntity::class,
            NotAnEntity::class,
        ));

        self::reader(['k' => NotAnEntity::class])->className('k', AbstractResourceEntity::class, ResourceEntity::class);
    }

    #[Test]
    public function classNameRejectsTheBaseClassItself(): void
    {
        $this->expectException(ConfigurationException::class);

        self::reader(['k' => AbstractResourceEntity::class])->className(
            'k',
            AbstractResourceEntity::class,
            ResourceEntity::class,
        );
    }

    #[Test]
    public function classNameReturnsAConfiguredSubclass(): void
    {
        static::assertSame(
            SectionResourceEntity::class,
            self::reader(['k' => SectionResourceEntity::class])->className(
                'k',
                AbstractResourceEntity::class,
                ResourceEntity::class,
            ),
        );
    }

    #[Test]
    public function classNameTakesTheDefault(): void
    {
        static::assertSame(
            ResourceEntity::class,
            self::reader([])->className('k', AbstractResourceEntity::class, ResourceEntity::class),
        );
    }

    #[Test]
    public function optionalStringIsNullWhenAbsent(): void
    {
        static::assertNull(self::reader([])->optionalString('k'));
    }

    #[Test]
    public function positiveIntAcceptsOne(): void
    {
        static::assertSame(1, self::reader(['k' => 1])->positiveInt('k', default: 3));
    }

    #[Test]
    public function positiveIntReturnsTheValue(): void
    {
        static::assertSame(7, self::reader(['k' => 7])->positiveInt('k', default: 3));
    }

    #[Test]
    public function positiveIntTakesTheDefaultWhenAbsent(): void
    {
        static::assertSame(3, self::reader([])->positiveInt('k', default: 3));
    }

    #[Test]
    public function stringReturnsTheValue(): void
    {
        static::assertSame('v', self::reader(['k' => 'v'])->string('k', default: 'd'));
    }

    #[Test]
    public function theSectionDefaultsToContenirResource(): void
    {
        $reader = ConfigReader::fromContainer(new ArrayContainer(['config' => ['contenir_resource' => ['k' => 'v']]]));

        static::assertSame('v', $reader->string('k', default: 'd'));
    }
}
