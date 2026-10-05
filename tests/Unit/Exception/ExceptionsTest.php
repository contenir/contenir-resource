<?php

declare(strict_types=1);

namespace Contenir\Resource\Core\Tests\Unit\Exception;

use Contenir\Resource\Core\Exception\ConfigurationException;
use Contenir\Resource\Core\Exception\ExceptionInterface;
use Contenir\Resource\Core\Exception\MissingResourceException;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

#[Group('unit')]
final class ExceptionsTest extends TestCase
{
    #[Test]
    public function configurationIsAnInvalidArgumentException(): void
    {
        $exception = ConfigurationException::invalidSection('contenir_resource', 'x');

        static::assertSame(
            [true, true, 'Config "contenir_resource" must be an array, got string'],
            [
                $exception instanceof InvalidArgumentException,
                $exception instanceof ExceptionInterface,
                $exception->getMessage(),
            ],
        );
    }

    #[Test]
    public function messagesNameTheProblem(): void
    {
        static::assertSame(
            [
                'Service "svc" must be a Foo, got stdClass',
                'Config "s.k" must be a number, got int',
                'Config "s.k" must name a concrete subclass of Base, got "Other"',
                'No repository "Nope" is built by this factory',
            ],
            [
                ConfigurationException::invalidService('svc', 'Foo', new stdClass())->getMessage(),
                ConfigurationException::invalidValue('s', 'k', 'a number', 5)->getMessage(),
                ConfigurationException::invalidEntityClass('s.k', 'Other', 'Base')->getMessage(),
                ConfigurationException::unknownRepository('Nope')->getMessage(),
            ],
        );
    }

    #[Test]
    public function missingResourceIsARuntimeException(): void
    {
        $exception = MissingResourceException::notResolved('pipe the middleware');

        static::assertSame(
            [true, true, 'No resource was resolved for this request; pipe the middleware'],
            [
                $exception instanceof RuntimeException,
                $exception instanceof ExceptionInterface,
                $exception->getMessage(),
            ],
        );
    }
}
