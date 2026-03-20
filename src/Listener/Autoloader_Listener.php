<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use Laminas\Loader\Autoloader_Factory;
use Laminas\Module_Manager\Feature\Autoloader_Provider_Interface;
use Laminas\Module_Manager\Module_Event;
use function method_exists;
class Autoloader_Listener extends Abstract_Listener
{
    public function __invoke(Module_Event $e): void
    {
        $module = $e->get_module();
        if (!$module instanceof Autoloader_Provider_Interface && !method_exists($module, 'getAutoloaderConfig')) {
            return;
        }
        $autoloader_config = $module->get_autoloader_config();
        Autoloader_Factory::factory($autoloader_config);
    }
}