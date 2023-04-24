<?php

namespace Contenir\Resource\View\Helper;

use Application\Entity\ResourceEntity;
use Laminas\Filter\Callback;
use Laminas\Filter\FilterChain;
use Laminas\Filter\PregReplace;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\View\Helper\AbstractHelper;

class ResourceContent extends AbstractHelper
{
    public function __construct()
    {
        $this->filterChain = new FilterChain();

        $this->filterChain->attach(new StripTags());
        $this->filterChain->attach(new PregReplace([
            'pattern'     => '/\&nbsp;/',
            'replacement' => ' '
        ]));
        $this->filterChain->attach(new Callback('html_entity_decode'));
        $this->filterChain->attach(new PregReplace([
            'pattern'     => '/[\r\n ]+/',
            'replacement' => ' '
        ]));
        $this->filterChain->attach(new StringTrim());
        $this->filterChain->attach(new Callback(function ($value) {
            if (strlen($value) > 249) {
                $value = substr($value, 0, 249) . '…';
            }
            return $value;
        }));
    }

    public function __invoke($content)
    {
        $html = '';

        if ($content instanceof ResourceEntity) {
            if ($resourceEntity->description !== null) {
                $content = $resourceEntity->description;
            } elseif ($resourceEntity->getSection()) {
                $content = $this->getView()->Partial(
                    'application/component/_section',
                    [
                        'section'  => $resourceEntity->getSection(),
                        'resource' => $resourceEntity
                    ]
                );
            } else {
                $content = "";
            }
        }

        $html = $this->filterChain->filter("{$content}");

        return $html;
    }
}
