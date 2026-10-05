<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Container;

use Contenir\Resource\Core\Exception\ConfigurationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function is_array;
use function is_int;
use function is_string;
use function is_subclass_of;
use function preg_match;

/**
 * Reads one section of the application config ("contenir_resource" by
 * default). An absent key takes its default; a key that is present with a
 * value of the wrong type or shape is an error, never silently ignored.
 *
 * @internal
 */
final readonly class ConfigReader
{
    public const string SECTION = 'contenir_resource';

    /**
     * @param array<array-key, mixed> $values
     */
    private function __construct(
        private string $section,
        private array $values,
    ) {}

    /**
     * @throws ConfigurationException When the section is present but not an array.
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment The config service is untyped; its shape is checked here.
     */
    public static function fromContainer(ContainerInterface $container, string $section = self::SECTION): self
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $values = is_array($config) ? $config[$section] ?? [] : [];

        return is_array($values)
            ? new self($section, $values)
            : throw ConfigurationException::invalidSection(
                $section,
                $values,
            );
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $baseClass
     * @param class-string<T> $default
     *
     * @return class-string<T>
     *
     * @throws ConfigurationException When the value does not name a subclass of $baseClass.
     */
    public function className(string $key, string $baseClass, string $default): string
    {
        $value = $this->string($key, $default);
        if (is_subclass_of($value, $baseClass)) {
            return $value;
        }

        throw ConfigurationException::invalidEntityClass("{$this->section}.{$key}", $value, $baseClass);
    }

    /**
     * An absolute http or https URL, or null when the key is absent or null.
     *
     * @throws ConfigurationException When the value is not an absolute http(s) URL.
     */
    public function optionalBaseUrl(string $key): ?string
    {
        $value = $this->optionalString($key);
        if (null === $value || 1 === preg_match('~^https?://[^/?#\s]+(/[^?#\s]*)?$~iD', $value)) {
            return $value;
        }

        throw ConfigurationException::invalidValue($this->section, $key, 'an absolute http(s) URL', $value);
    }

    /**
     * A non-empty string, or null when the key is absent or null.
     *
     * @throws ConfigurationException When the value is not a non-empty string.
     *
     * @mago-expect analysis:mixed-assignment Config values are untyped; the type is checked here.
     */
    public function optionalString(string $key): ?string
    {
        $value = $this->values[$key] ?? null;
        if (null === $value || is_string($value) && '' !== $value) {
            return $value;
        }

        throw ConfigurationException::invalidValue($this->section, $key, 'a non-empty string', $value);
    }

    /**
     * @throws ConfigurationException When the value is not a positive integer.
     *
     * @mago-expect analysis:mixed-assignment Config values are untyped; the type is checked here.
     */
    public function positiveInt(string $key, int $default): int
    {
        $value = $this->values[$key] ?? $default;
        if (is_int($value) && $value > 0) {
            return $value;
        }

        throw ConfigurationException::invalidValue($this->section, $key, 'a positive integer', $value);
    }

    /**
     * @throws ConfigurationException When the value is not a non-empty string.
     */
    public function string(string $key, string $default): string
    {
        return $this->optionalString($key) ?? $default;
    }
}
