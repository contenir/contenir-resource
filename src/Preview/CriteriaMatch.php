<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Preview;

use BackedEnum;
use DateTimeInterface;

use function array_key_exists;
use function array_map;
use function get_object_vars;
use function in_array;
use function is_array;
use function is_bool;
use function is_scalar;

/**
 * Whether a resource answers a lookup's criteria, as the site's resource manager takes them:
 * keyed by property, each a value or a list of values to choose from. Status criteria are passed
 * over, so a draft answers as it would once published.
 *
 * @internal
 */
final class CriteriaMatch
{
    /** Criteria a previewed resource is shown through, as it would be once published */
    private const array STATUS = ['status', 'visible'];

    /**
     * True when every criterion but the status ones matches, and at least one did.
     *
     * @param array<string, mixed> $criteria
     */
    public static function answers(object $resource, array $criteria): bool
    {
        $properties = get_object_vars($resource);
        $matched    = false;
        /** @var mixed $wanted */
        foreach ($criteria as $property => $wanted) {
            if (in_array($property, self::STATUS, strict: true)) {
                continue;
            }
            if (! array_key_exists($property, $properties) || ! self::matches($properties[$property], $wanted)) {
                return false;
            }
            $matched = true;
        }

        return $matched;
    }

    /**
     * A value as criteria and properties compare: an enum by its value, a flag as 1 or 0, a date
     * to the second; anything else matches nothing.
     */
    private static function comparable(mixed $value): ?string
    {
        return match (true) {
            null === $value => null,
            $value instanceof BackedEnum => (string) $value->value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            is_bool($value)   => $value ? '1' : '0',
            is_scalar($value) => (string) $value,
            default           => "\0unmatched",
        };
    }

    private static function matches(mixed $actual, mixed $wanted): bool
    {
        $value = self::comparable($actual);

        return (
            is_array($wanted)
                ? in_array($value, array_map(self::comparable(...), $wanted), strict: true)
                : $value === self::comparable($wanted)
        );
    }
}
