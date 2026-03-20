<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use function end;
use function explode;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Event_Manager\Listener_Aggregate_Interface;
use Laminas\Event_Manager\Listener_Aggregate_Trait;
use Laminas\Module_Manager\Feature\Locator_Registered_Interface;
use Laminas\Module_Manager\Module_Event;
use Laminas\Module_Manager\Module_Manager;
use Laminas\Mvc\Application;
use Laminas\Mvc\Mvc_Event;
use Laminas\Service_Manager\Service_Manager;
use Override;
class Locator_Registration_Listener extends Abstract_Listener implements Listener_Aggregate_Interface
{
    use Listener_Aggregate_Trait;
    /** @var array */
    protected $modules = [];
    /**
     * Check each loaded module to see if it implements LocatorRegistered. If it
     * does, we add it to an internal array for later.
     */
    public function on_load_module(Module_Event $e): void
    {
        if (!$e->get_module() instanceof Locator_Registered_Interface) {
            return;
        }
        $this->modules[] = $e->get_module();
    }
    /**
     * Once all the modules are loaded, loop
     */
    public function on_load_modules(Module_Event $e): void
    {
        $module_manager = $e->get_target();
        $events = $module_manager->get_event_manager()->get_shared_manager();
        if (!$events) {
            return;
        }
        // Shared instance for module manager
        $events->attach(Application::class, Module_Manager::EVENT_BOOTSTRAP, static function (Mvc_Event $e) use ($module_manager): void {
            $module_class_name = $module_manager::class;
            $module_class_name_array = explode('\\', $module_class_name);
            $module_class_name_alias = end($module_class_name_array);
            $application = $e->get_application();
            /** @var ServiceManager $services */
            $services = $application->get_service_manager();
            if (!$services->has($module_class_name)) {
                $services->set_alias($module_class_name, $module_class_name_alias);
            }
        }, 1000);
        if (!$this->modules) {
            return;
        }
        // Attach to the bootstrap event if there are modules we need to process
        $events->attach(Application::class, Module_Manager::EVENT_BOOTSTRAP, $this->on_bootstrap(...), 1000);
    }
    /**
     * This is ran during the MVC bootstrap event because it requires access to
     * the DI container.
     *
     * @TODO: Check the application / locator / etc a bit better to make sure
     * the env looks how we're expecting it to?
     */
    public function on_bootstrap(Mvc_Event $e): void
    {
        $application = $e->get_application();
        /** @var ServiceManager $services */
        $services = $application->get_service_manager();
        foreach ($this->modules as $module) {
            $module_class_name = $module::class;
            if (!$services->has($module_class_name)) {
                $services->set_service($module_class_name, $module);
            }
        }
    }
    /** {@inheritDoc} */
    #[Override]
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULE, $this->on_load_module(...));
        $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULES, $this->on_load_modules(...), -1000);
    }
}