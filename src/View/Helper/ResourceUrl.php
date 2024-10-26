<?php

declare(strict_types=1);

namespace Contenir\Resource\View\Helper;

use Contenir\Db\Model\Entity\AbstractEntity;
use Laminas\View\Helper\AbstractHelper;

class ResourceUrl extends AbstractHelper
{
    public function __invoke($resourceId, $resourceUrl, $linkTarget = null): array
    {
        $url = null;

        if ($resourceId instanceof AbstractEntity) {
            $url = $this->view->Url($resourceId->getRouteId());
        } elseif ($resourceId) {
            $resource = $this->view->Resource($resourceId);
            if ($resource) {
                $url = $this->view->Url($resource->getRouteId());
            }
        } elseif ($resourceUrl) {
            $url        = $this->view->UrlFormat($resourceUrl);
            $linkTarget = '_blank';
        }

        return [$url, $linkTarget];
    }
}
