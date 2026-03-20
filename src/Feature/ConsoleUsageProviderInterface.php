<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Feature;

use Laminas\Console\Adapter\Adapter_Interface;
interface Console_Usage_Provider_Interface
{
    /**
     * Returns an array or a string containing usage information for this module's Console commands.
     * The method is called with active Laminas\Console\Adapter\AdapterInterface that can be used to directly access
     * Console and send output.
     *
     * If the result is a string it will be shown directly in the console window.
     * If the result is an array, its contents will be formatted to console window width. The array must
     * have the following format:
     *
     *     return array(
     *                'Usage information line that should be shown as-is',
     *                'Another line of usage info',
     *
     *                '--parameter'        =>   'A short description of that parameter',
     *                '-another-parameter' =>   'A short description of another parameter',
     *                ...
     *            )
     *
     * @return array|string|null
     */
    public function get_console_usage(Adapter_Interface $console);
}