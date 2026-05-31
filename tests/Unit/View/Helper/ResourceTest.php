<?php

declare(strict_types=1);

namespace Contenir\Resource\Tests\Unit\View\Helper;

use Contenir\Db\Model\Entity\EntityInterface;
use Contenir\Resource\ResourceManager;
use Contenir\Resource\View\Helper\Resource;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ResourceTest extends TestCase
{
    public function testReturnsHelperItselfWhenInvokedWithoutResourceId(): void
    {
        $resourceManager = $this->createMock(ResourceManager::class);
        $resourceManager->expects(self::never())->method('findOne');

        $helper = new Resource($resourceManager);

        self::assertSame($helper, $helper());
    }

    public function testReturnsEntityWhenResourceExists(): void
    {
        $entity = $this->createMock(EntityInterface::class);

        $resourceManager = $this->createMock(ResourceManager::class);
        $resourceManager->method('findOne')->willReturn($entity);

        $helper = new Resource($resourceManager);

        self::assertSame($entity, $helper('existing-resource'));
    }

    public function testReturnsNullWhenResourceDoesNotExist(): void
    {
        $resourceManager = $this->createMock(ResourceManager::class);
        $resourceManager->method('findOne')->willReturn(null);

        $helper = new Resource($resourceManager);

        self::assertNull($helper('deleted-resource'));
    }
}
