<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use Laminas\Module_Manager\Feature\Bootstrap_Listener_Interface;
use Laminas\Module_Manager\Module_Event;
use Laminas\Module_Manager\Module_Manager;
use Laminas\Mvc\Application;
use function method_exists;
class On_Bootstrap_Listener extends Abstract_Listener
{
    public function __invoke(Module_Event $e): void
    {
        $module = $e->get_module();
        if (!$module instanceof Bootstrap_Listener_Interface && !method_exists($module, 'onBootstrap')) {
            return;
        }
        $module_manager = $e->get_target();
        $events = $module_manager->get_event_manager();
        $shared_events = $events->get_shared_manager();
        $shared_events->attach(Application::class, Module_Manager::EVENT_BOOTSTRAP, [$module, 'onBootstrap']);
    }
}