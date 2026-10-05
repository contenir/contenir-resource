<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Exception;

use InvalidArgumentException;

use function get_debug_type;
use function sprintf;

/**
 * The "contenir_resource" (or "workflow_manager") configuration, or a
 * service it names, is not usable.
 *
 * @api
 */
final class ConfigurationException extends InvalidArgumentException implements ExceptionInterface
{
    public static function invalidEntityClass(string $key, string $className, string $baseClass): self
    {
        return new self(sprintf(
            'Config "%s" must name a concrete subclass of %s, got "%s"',
            $key,
            $baseClass,
            $className,
        ));
    }

    public static function invalidSection(string $section, mixed $value): self
    {
        return new self(sprintf('Config "%s" must be an array, got %s', $section, get_debug_type($value)));
    }

    public static function invalidService(string $name, string $expected, mixed $service): self
    {
        return new self(sprintf(
            'Service "%s" must be a %s, got %s',
            $name,
            $expected,
            get_debug_type($service),
        ));
    }

    public static function invalidValue(string $section, string $key, string $expected, mixed $value): self
    {
        return new self(sprintf(
            'Config "%s.%s" must be %s, got %s',
            $section,
            $key,
            $expected,
            get_debug_type($value),
        ));
    }

    public static function unknownRepository(string $name): self
    {
        return new self(sprintf('No repository "%s" is built by this factory', $name));
    }
}
