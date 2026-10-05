<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Container;

use Contenir\Resource\Core\Container\ResourceSummaryFactory;
use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Resource\Core\Tests\TestAsset\Content\RecordingSectionRenderer;
use Contenir\Resource\Core\Tests\TestAsset\Entity\ResourceFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

use function str_repeat;

#[Group('unit')]
final class ResourceSummaryFactoryTest extends TestCase
{
    #[Test]
    public function rejectsARendererOfTheWrongType(): void
    {
        $this->expectException(ConfigurationException::class);

        (new ResourceSummaryFactory())(new ArrayContainer([
            'config'   => ['contenir_resource' => ['section_renderer' => 'sections']],
            'sections' => new stdClass(),
        ]));
    }

    #[Test]
    public function rendersNoSectionsAndCutsAtTwoHundredAndFortyNineByDefault(): void
    {
        $summary = (new ResourceSummaryFactory())(new ArrayContainer());

        static::assertSame(
            ['', str_repeat('a', times: 249) . '…'],
            [
                $summary->summarise(ResourceFactory::section(section: '{"a":1}')),
                $summary->summarise(str_repeat('a', times: 250)),
            ],
        );
    }

    #[Test]
    public function usesTheConfiguredRendererAndLength(): void
    {
        $summary = (new ResourceSummaryFactory())(new ArrayContainer([
            'config'   => ['contenir_resource' => ['section_renderer' => 'sections', 'summary_length' => 5]],
            'sections' => new RecordingSectionRenderer(),
        ]));

        static::assertSame('Rende…', $summary->summarise(ResourceFactory::section(section: '{"a":1}')));
    }
}
