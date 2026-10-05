<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Exception;

use RuntimeException;

use function sprintf;

/**
 * A resource that was required is not available: none was resolved for the
 * current request, or an entity has not been saved yet.
 *
 * @api
 */
final class MissingResourceException extends RuntimeException implements ExceptionInterface
{
    public static function notResolved(string $hint): self
    {
        return new self(sprintf('No resource was resolved for this request; %s', $hint));
    }

    public static function unsaved(string $className): self
    {
        return new self(sprintf('%s has no resource id; save it before routing or linking to it', $className));
    }
}
