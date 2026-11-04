<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Metadata;

use Contenir\Metadata\MetadataInterface;

/**
 * Where PageMetadataBuilder finds what a site's section map says about a
 * resource's page. The SEO packages implement it from the <seo> blocks a
 * site's admin.xml declares; without one, a page is what its getters make.
 *
 * @api
 */
interface PageDefaultsInterface
{
    /**
     * The defaults for $resource's page, or null when nothing maps its type.
     */
    public function for(MetadataInterface $resource): ?PageDefaults;
}
