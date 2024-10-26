<?php

declare(strict_types=1);

namespace Contenir\Resource\View\Helper;

use Contenir\Db\Model\Entity\EntityInterface;
use Contenir\Resource\ResourceManager;
use Laminas\View\Helper\AbstractHelper;

class Resource extends AbstractHelper
{
    protected ?ResourceManager $resourceManager;

    public function __construct(?ResourceManager $resourceManager = null)
    {
        $this->resourceManager = $resourceManager;
    }

    public function __invoke(int|iterable|null $resourceId = null): EntityInterface|self
    {
        if ($resourceId === null) {
            return $this;
        }

        return $this->resourceManager->findOne($resourceId, [
            'active' => 'active',
        ]);
    }

    public function findBySlug(string $slug): ?EntityInterface
    {
        return $this->resourceManager->findOneByField('slug', $slug, [
            'active' => 'active',
        ]);
    }

    public function findByWorkflow(string $workflow): ?EntityInterface
    {
        return $this->resourceManager->findOneByField('workflow', $workflow, [
            'active' => 'active',
        ]);
    }

    public function findActivePageByWorkflow(string $workflow): ?EntityInterface
    {
        return $this->resourceManager->findActivePageByWorkflow($workflow);
    }
}
