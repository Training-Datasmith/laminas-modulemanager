<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Feature;

use Laminas\Module_Manager\Module_Manager_Interface;
interface Init_Provider_Interface
{
    /**
     * Initialize workflow
     *
     * @return void
     */
    public function init(Module_Manager_Interface $manager);
}