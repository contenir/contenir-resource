<?php

declare(strict_types=1);

namespace Contenir\Resource\Exception;

use Laminas\Mvc\Exception\ExceptionInterface;
use RuntimeException;

class MissingResourceException extends RuntimeException implements ExceptionInterface
{
}
