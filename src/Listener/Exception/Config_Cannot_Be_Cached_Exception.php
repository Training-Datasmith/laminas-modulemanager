<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener\Exception;

use Brick\Var_Exporter\Export_Exception;
use RuntimeException;
use function sprintf;
use Throwable;
final class Config_Cannot_Be_Cached_Exception extends RuntimeException
{
    private function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    /** @internal */
    public static function from_exporter_exception(Export_Exception $export_exception): self
    {
        return new self(sprintf('Cannot export config into a cache file. Config contains uncacheable entries: %s', $export_exception->get_message()), $export_exception->get_code(), $export_exception);
    }
}