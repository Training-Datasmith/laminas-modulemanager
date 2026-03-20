<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener\Exception;

use Laminas\Module_Manager\Exception;
class RuntimeException extends Exception\RuntimeException implements Exception_Interface
{
}