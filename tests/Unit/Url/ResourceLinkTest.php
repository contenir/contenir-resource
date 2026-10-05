<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Url;

use Contenir\Resource\Core\Url\ResourceLink;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ResourceLinkTest extends TestCase
{
    #[Test]
    public function bothPartsDefaultToNull(): void
    {
        static::assertSame([null, null], (new ResourceLink())->toArray());
    }

    #[Test]
    public function toArrayIsTheUrlAndTargetPair(): void
    {
        static::assertSame(['/a', '_self'], (new ResourceLink('/a', '_self'))->toArray());
    }
}
