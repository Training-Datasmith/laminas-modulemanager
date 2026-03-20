<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Feature;

interface Dependency_Indicator_Interface
{
    /**
     * Expected to return an array of modules on which the current one depends on
     *
     * @return array
     */
    public function get_module_dependencies();
}