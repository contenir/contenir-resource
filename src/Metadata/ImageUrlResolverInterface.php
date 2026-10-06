<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

/**
 * Turns a resource's share image path into the absolute URL used for
 * og:image and twitter:image.
 *
 * @api
 */
interface ImageUrlResolverInterface
{
    /**
     * @param string $image  The non-empty path from MetadataInterface::getMetaImage().
     * @param string $origin The site origin ("https://example.com"), or '' when unknown.
     *
     * @return string|null The URL, or null to leave the image tags out.
     */
    public function resolve(string $image, string $origin): ?string;
}
