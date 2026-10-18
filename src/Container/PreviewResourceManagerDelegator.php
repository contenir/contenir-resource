<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Container;

use Contenir\Db\Model\EntityManager;
use Contenir\Db\Model\RowValues;
use Contenir\Preview\PreviewContext;
use Contenir\Resource\Core\Entity\AbstractResourceEntity;
use Contenir\Resource\Core\Entity\ResourceEntity;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Preview\EditorValues;
use Contenir\Resource\Core\Preview\PreviewedResource;
use Contenir\Resource\Core\Preview\PreviewLoader;
use Contenir\Resource\Core\Preview\PreviewResourceManager;
use Contenir\Resource\Core\ResourceManagerInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Wraps the resource manager in a {@see PreviewResourceManager} when the application renders
 * CMS previews (contenir-preview-laminas-mvc registers the preview context), so the resource a
 * preview shows carries the editor's unsaved values. Without previews the manager is left as it
 * is.
 *
 * @api
 */
final class PreviewResourceManagerDelegator
{
    /**
     * @param callable(): mixed $callback Builds the resource manager
     *
     * @throws ConfigurationException When a service or the resource entity is not usable
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $name, callable $callback): ResourceManagerInterface
    {
        /** @var mixed $resources */
        $resources = $callback();
        if (! $resources instanceof ResourceManagerInterface) {
            throw ConfigurationException::invalidService($name, ResourceManagerInterface::class, $resources);
        }
        if (! $container->has(PreviewContext::class)) {
            return $resources;
        }

        return new PreviewResourceManager($resources, new PreviewedResource(new PreviewLoader(
            ServiceLocator::get($container, PreviewContext::class, PreviewContext::class),
            $resources,
            new EditorValues(new RowValues(ServiceLocator::get(
                $container,
                EntityManager::class,
                EntityManager::class,
            ))),
            ConfigReader::fromContainer($container)->className(
                'resource_entity',
                AbstractResourceEntity::class,
                ResourceEntity::class,
            ),
        )));
    }
}
