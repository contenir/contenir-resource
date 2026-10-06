<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Container;

use Contenir\Resource\Core\Container\ServiceLocator;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Metadata\MetaTagRenderer;
use Contenir\Resource\Core\Tests\TestAsset\Container\ArrayContainer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Group('unit')]
final class ServiceLocatorTest extends TestCase
{
    #[Test]
    public function rejectsAServiceOfAnotherType(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Service "r" must be a ' . MetaTagRenderer::class . ', got stdClass');

        ServiceLocator::get(new ArrayContainer(['r' => new stdClass()]), 'r', MetaTagRenderer::class);
    }

    #[Test]
    public function returnsAServiceOfTheType(): void
    {
        $renderer = new MetaTagRenderer();

        static::assertSame(
            $renderer,
            ServiceLocator::get(new ArrayContainer(['r' => $renderer]), 'r', MetaTagRenderer::class),
        );
    }
}
