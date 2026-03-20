<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Feature;

use Traversable;
interface Config_Provider_Interface
{
    /**
     * Returns configuration to merge with application configuration
     *
     * @return array|Traversable
     */
    public function get_config();
}