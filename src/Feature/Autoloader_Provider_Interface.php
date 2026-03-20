<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Feature;

interface Autoloader_Provider_Interface
{
    /**
     * Return an array for passing to Laminas\Loader\AutoloaderFactory.
     *
     * @return array
     */
    public function get_autoloader_config();
}