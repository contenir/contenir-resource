<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Container;

use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\ImageUrlResolverInterface;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Builds the PageMetadataBuilder from "contenir_resource.base_url" and
 * "contenir_resource.description_length" (default 160), with the
 * ImageUrlResolverInterface service.
 *
 * @api
 */
final class PageMetadataBuilderFactory
{
    /**
     * @throws ConfigurationException When a value or the image resolver service is not usable.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): PageMetadataBuilder
    {
        $config = ConfigReader::fromContainer($container);

        return new PageMetadataBuilder(
            ServiceLocator::get($container, ImageUrlResolverInterface::class, ImageUrlResolverInterface::class),
            $config->optionalBaseUrl('base_url'),
            $config->positiveInt('description_length', default: 160),
        );
    }
}
