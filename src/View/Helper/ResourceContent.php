<?php

declare(strict_types=1);

namespace Contenir\Resource\View\Helper;

use Contenir\Resource\Model\Entity\BaseResourceEntity;
use Laminas\Filter\Callback;
use Laminas\Filter\FilterChain;
use Laminas\Filter\PregReplace;
use Laminas\Filter\StringTrim;
use Laminas\Filter\StripTags;
use Laminas\View\Helper\AbstractHelper;

use function strlen;
use function substr;

class ResourceContent extends AbstractHelper
{
    protected FilterChain $filterChain;

    public function __construct()
    {
        $this->filterChain = new FilterChain();

        $this->filterChain->attach(new StripTags());
        $this->filterChain->attach(new PregReplace([
            'pattern'     => '/\&nbsp;/',
            'replacement' => ' ',
        ]));
        $this->filterChain->attach(new Callback('html_entity_decode'));
        $this->filterChain->attach(new PregReplace([
            'pattern'     => '/[\r\n ]+/',
            'replacement' => ' ',
        ]));
        $this->filterChain->attach(new StringTrim());
        $this->filterChain->attach(new Callback(function ($value) {
            if (strlen($value) > 249) {
                $value = substr($value, 0, 249) . '…';
            }
            return $value;
        }));
    }

    public function __invoke(mixed $content = null): string
    {
        $html = '';

        if ($content instanceof BaseResourceEntity) {
            if ($content->description !== null) {
                $content = $content->description;
            } elseif ($content->getSection()) {
                $content = $this->getView()->Partial(
                    'application/component/_section',
                    [
                        'section'  => $content->getSection(),
                        'resource' => $content,
                    ]
                );
            } else {
                $content = "";
            }
        }

        return $this->filterChain->filter("$content");
    }
}
