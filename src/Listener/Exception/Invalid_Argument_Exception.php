<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener\Exception;

use Laminas\Module_Manager\Exception;
class InvalidArgumentException extends Exception\InvalidArgumentException implements Exception_Interface
{
}