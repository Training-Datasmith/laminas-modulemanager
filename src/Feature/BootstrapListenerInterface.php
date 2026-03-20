<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Feature;

use Laminas\Event_Manager\Event_Interface;
interface Bootstrap_Listener_Interface
{
    /**
     * Listen to the bootstrap event
     *
     * @return void
     */
    public function on_bootstrap(Event_Interface $e);
}