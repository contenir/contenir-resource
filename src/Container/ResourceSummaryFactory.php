<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Container;

use Contenir\Resource\Core\Content\ResourceSummary;
use Contenir\Resource\Core\Content\SectionRendererInterface;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Builds the ResourceSummary from "contenir_resource.summary_length"
 * (default 249) and the optional "contenir_resource.section_renderer"
 * service name.
 *
 * @api
 */
final class ResourceSummaryFactory
{
    /**
     * @throws ConfigurationException When a value or the section renderer service is not usable.
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ResourceSummary
    {
        $config   = ConfigReader::fromContainer($container);
        $renderer = $config->optionalString('section_renderer');

        return new ResourceSummary(
            null === $renderer
                ? null
                : ServiceLocator::get($container, $renderer, SectionRendererInterface::class),
            $config->positiveInt('summary_length', default: 249),
        );
    }
}
