<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Container;

use Contenir\Db\Model\EntityManager;
use Contenir\Preview\PreviewContext;
use Contenir\Resource\Core\Container\PreviewResourceManagerDelegator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Preview\PreviewResourceManager;
use Contenir\Resource\Core\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Resource\Core\Tests\TestAsset\Entity\ResourceFactory;
use Contenir\Resource\Core\Tests\TestAsset\Manager\ListResourceManager;
use Contenir\Resource\Core\Tests\TestAsset\Preview\PreviewRecords;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * The resource manager wrapped only where CMS previews are rendered.
 */
#[Group('unit')]
final class PreviewResourceManagerDelegatorTest extends TestCase
{
    #[Test]
    public function leavesTheManagerAloneWhereNoPreviewsAreRendered(): void
    {
        $manager = new ListResourceManager();

        static::assertSame(
            $manager,
            (new PreviewResourceManagerDelegator())(
                new ArrayContainer([]),
                name: 'manager',
                callback: static fn(): ListResourceManager => $manager,
            ),
        );
    }

    #[Test]
    public function refusesAManagerOfAnotherKind(): void
    {
        $this->expectException(ConfigurationException::class);

        (new PreviewResourceManagerDelegator())(
            new ArrayContainer([]),
            name: 'manager',
            callback: static fn(): stdClass => new stdClass(),
        );
    }

    #[Test]
    public function wrapsTheManagerWherePreviewsAreRendered(): void
    {
        $container = new ArrayContainer([
            PreviewContext::class => PreviewRecords::context(PreviewRecords::saved(101, ['title' => 'Spa Rituals'])),
            EntityManager::class  => new EntityManager($this->createStub(AdapterInterface::class)),
        ]);

        $manager = (new PreviewResourceManagerDelegator())(
            $container,
            name: 'manager',
            callback: static fn(): ListResourceManager => new ListResourceManager([ResourceFactory::make(101)]),
        );

        static::assertSame([PreviewResourceManager::class, 'Spa Rituals'], [
            $manager::class,
            $manager->find(101)?->title,
        ]);
    }
}
