<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Integration\Container;

use Contenir\Db\Model\EntityManager;
use Contenir\Resource\Core\Container\RepositoryFactory;
use Contenir\Resource\Core\Container\ResourceManagerFactory;
use Contenir\Resource\Core\Entity\ResourceCollectionEntity;
use Contenir\Resource\Core\Entity\ResourceTypeEntity;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Repository\ResourceCollectionRepository;
use Contenir\Resource\Core\Repository\ResourceRepository;
use Contenir\Resource\Core\Repository\ResourceTypeRepository;
use Contenir\Resource\Core\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Resource\Core\Tests\TestAsset\Entity\NotAnEntity;
use Contenir\Resource\Core\Tests\TestAsset\Entity\SectionResourceEntity;
use Contenir\Resource\Core\Tests\Trait\SqliteDatabaseTrait;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Group('integration')]
final class RepositoryFactoryTest extends TestCase
{
    use SqliteDatabaseTrait;

    /**
     * @return array<string, array{class-string, string}>
     */
    public static function entityKeyProvider(): array
    {
        return [
            'resource'            => [ResourceRepository::class, 'resource_entity'],
            'resource collection' => [ResourceCollectionRepository::class, 'resource_collection_entity'],
            'resource type'       => [ResourceTypeRepository::class, 'resource_type_entity'],
        ];
    }

    #[Test]
    public function buildsTheDefaultRepositories(): void
    {
        $this->insertResource(['resource_id' => 1]);
        $this->insert('resource_collection', ['resource_collection_id' => 1]);
        $this->insert('resource_type', ['resource_type_id' => 'page']);
        $factory   = new RepositoryFactory();
        $container = $this->container();

        $collection = $factory($container, ResourceCollectionRepository::class)->find(1);
        $type       = $factory($container, ResourceTypeRepository::class)->find('page');

        static::assertSame(
            [ResourceRepository::class, ResourceCollectionEntity::class, ResourceTypeEntity::class],
            [
                $factory($container, ResourceRepository::class)::class,
                null === $collection ? null : $collection::class,
                null === $type ? null : $type::class,
            ],
        );
    }

    #[Test]
    public function buildsTheResourceManagerOverTheRepositories(): void
    {
        $this->insertResource(['resource_id' => 1]);
        $manager = (new ResourceManagerFactory())(new ArrayContainer([
            ResourceRepository::class           => new ResourceRepository($this->em),
            ResourceCollectionRepository::class => new ResourceCollectionRepository($this->em),
        ]));

        static::assertSame([1, []], [$manager->find(1)?->resourceId, $manager->findCollectionByType('x')]);
    }

    #[Test]
    public function buildsTheResourceRepositoryForTheConfiguredEntity(): void
    {
        $this->insertResource(['resource_id' => 1]);
        $repository = (new RepositoryFactory())(
            $this->container(['resource_entity' => SectionResourceEntity::class]),
            ResourceRepository::class,
        );

        static::assertInstanceOf(SectionResourceEntity::class, $repository->find(1));
    }

    #[Test]
    #[DataProvider('entityKeyProvider')]
    public function rejectsAnEntityClassOfTheWrongKind(string $repository, string $key): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Config \"contenir_resource.{$key}\" must name a concrete subclass of");

        (new RepositoryFactory())($this->container([$key => NotAnEntity::class]), $repository);
    }

    #[Test]
    public function rejectsAnEntityManagerOfTheWrongType(): void
    {
        $this->expectException(ConfigurationException::class);

        (new RepositoryFactory())(
            new ArrayContainer([EntityManager::class => new stdClass()]),
            ResourceRepository::class,
        );
    }

    #[Test]
    public function rejectsAnUnknownRepositoryName(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('No repository "Other" is built by this factory');

        (new RepositoryFactory())($this->container(), requestedName: 'Other');
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpDatabase();
    }

    /**
     * @param array<string, mixed> $config
     */
    private function container(array $config = []): ArrayContainer
    {
        return new ArrayContainer([
            'config'             => ['contenir_resource' => $config],
            EntityManager::class => $this->em,
        ]);
    }
}
