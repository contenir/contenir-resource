<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Container;

use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\PathImageUrlResolver;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Builds the default image resolver from "contenir_resource.image_base_url".
 *
 * @api
 */
final class PathImageUrlResolverFactory
{
    /**
     * @throws ConfigurationException When image_base_url is not an absolute http(s) URL.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): PathImageUrlResolver
    {
        return new PathImageUrlResolver(ConfigReader::fromContainer($container)->optionalBaseUrl('image_base_url'));
    }
}
