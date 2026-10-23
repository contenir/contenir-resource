<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Container;

use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\ImageSizeResolverInterface;
use Contenir\Resource\Core\Metadata\ImageUrlResolverInterface;
use Contenir\Resource\Core\Metadata\LocalImageSizeResolver;
use Contenir\Resource\Core\Metadata\PageMetadataBuilder;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function array_is_list;
use function is_array;
use function is_bool;
use function is_string;

/**
 * Builds the PageMetadataBuilder from "contenir_resource.base_url",
 * "contenir_resource.description_length" (default 160),
 * "contenir_resource.description_ellipsis", "contenir_resource.site_name",
 * "contenir_resource.article_types" and
 * "contenir_resource.image_alt_from_title", with the ImageUrlResolverInterface
 * service. The share image is measured by the ImageSizeResolverInterface
 * service when there is one, or else under "contenir_resource.image_root".
 *
 * @api
 */
final class PageMetadataBuilderFactory
{
    /**
     * @return list<string>
     *
     * @throws ConfigurationException When article_types is not a list of non-empty strings.
     *
     * @mago-expect analysis:mixed-assignment Config values are untyped; the shape is checked here.
     */
    private static function articleTypes(ConfigReader $config): array
    {
        $value = $config->values()['article_types'] ?? [];
        if (! is_array($value) || ! array_is_list($value)) {
            throw self::notArticleTypes($value);
        }

        $types = [];
        foreach ($value as $type) {
            if (! is_string($type) || '' === $type) {
                throw self::notArticleTypes($value);
            }

            $types[] = $type;
        }

        return $types;
    }

    /**
     * @throws ConfigurationException When the value is set but not a boolean.
     *
     * @mago-expect analysis:mixed-assignment Config values are untyped; the type is checked here.
     */
    private static function flag(ConfigReader $config, string $key): bool
    {
        $value = $config->values()[$key] ?? false;

        return is_bool($value)
            ? $value
            : throw ConfigurationException::invalidValue(ConfigReader::SECTION, $key, 'a boolean', $value);
    }

    /**
     * @throws ConfigurationException When the size resolver service or image_root is not usable.
     * @throws ContainerExceptionInterface
     */
    private static function imageSizes(ContainerInterface $container, ConfigReader $config): ?ImageSizeResolverInterface
    {
        if ($container->has(ImageSizeResolverInterface::class)) {
            return ServiceLocator::get(
                $container,
                ImageSizeResolverInterface::class,
                ImageSizeResolverInterface::class,
            );
        }

        $root = $config->optionalString('image_root');

        return null === $root ? null : new LocalImageSizeResolver($root);
    }

    private static function notArticleTypes(mixed $value): ConfigurationException
    {
        return ConfigurationException::invalidValue(
            ConfigReader::SECTION,
            'article_types',
            'a list of non-empty strings',
            $value,
        );
    }

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
            $config->optionalString('site_name'),
            self::articleTypes($config),
            self::imageSizes($container, $config),
            self::flag($config, 'image_alt_from_title'),
            $config->optionalString('description_ellipsis') ?? '',
        );
    }
}
