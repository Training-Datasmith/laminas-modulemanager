<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use Laminas\Module_Manager\Feature\Init_Provider_Interface;
use Laminas\Module_Manager\Module_Event;
use function method_exists;
class Init_Trigger extends Abstract_Listener
{
    public function __invoke(Module_Event $e): void
    {
        $module = $e->get_module();
        if (!$module instanceof Init_Provider_Interface && !method_exists($module, 'init')) {
            return;
        }
        $module->init($e->get_target());
    }
}