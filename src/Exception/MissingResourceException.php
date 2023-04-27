<?php

namespace Contenir\Resource\Exception;

use Laminas\Mvc\Exception\ExceptionInterface;
use RuntimeException;

class MissingResourceException extends RuntimeException implements ExceptionInterface
{
}
