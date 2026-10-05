<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Container;

use Contenir\Resource\Core\Exception\ConfigurationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Fetches a container service and checks its type.
 *
 * @api Shared with the framework adapters.
 */
final class ServiceLocator
{
    /**
     * @template T of object
     *
     * @param class-string<T> $type
     *
     * @return T
     *
     * @throws ConfigurationException When the service is not a $type.
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment Container services are untyped; the type is checked here.
     */
    public static function get(ContainerInterface $container, string $name, string $type): object
    {
        $service = $container->get($name);

        return $service instanceof $type
            ? $service
            : throw ConfigurationException::invalidService(
                $name,
                $type,
                $service,
            );
    }
}
