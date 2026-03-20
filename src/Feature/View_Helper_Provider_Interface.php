<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Feature;

use Laminas\Service_Manager\Config;
interface View_Helper_Provider_Interface
{
    /**
     * Expected to return \Laminas\ServiceManager\Config object or array to
     * seed such an object.
     *
     * @return array|Config
     */
    public function get_view_helper_config();
}