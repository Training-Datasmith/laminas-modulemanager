<?php

declare (strict_types=1);
namespace Laminas\Module_Manager;

use function current;
use function is_array;
use function is_object;
use function is_string;
use function key;
use Laminas\Event_Manager\Event_Manager;
use Laminas\Event_Manager\Event_Manager_Interface;
use Override;
use function sprintf;
use Traversable;
class Module_Manager implements Module_Manager_Interface
{
    /** Reference to Laminas\Mvc\MvcEvent::EVENT_BOOTSTRAP */
    public const EVENT_BOOTSTRAP = 'bootstrap';
    /** @var array An array of Module classes of loaded modules */
    protected $loaded_modules = [];
    /** @var EventManagerInterface */
    protected $events;
    /** @var ModuleEvent */
    protected $event;
    /** @var int */
    protected $load_finished;
    /** @var array|Traversable */
    protected $modules = [];
    /**
     * True if modules have already been loaded
     *
     * @var bool
     */
    protected $modules_are_loaded = false;
    /** @param array|Traversable $modules */
    public function __construct($modules, ?Event_Manager_Interface $event_manager = null)
    {
        $this->set_modules($modules);
        if ($event_manager instanceof Event_Manager_Interface) {
            $this->set_event_manager($event_manager);
        }
    }
    /**
     * Handle the loadModules event
     */
    public function on_load_modules(): void
    {
        if (true === $this->modules_are_loaded) {
            return;
        }
        foreach ($this->get_modules() as $module_name => $module) {
            if (is_object($module)) {
                if (!is_string($module_name)) {
                    throw new Exception\RuntimeException(sprintf('Module (%s) must have a key identifier.', $module::class));
                }
                $module = [$module_name => $module];
            }
            $this->load_module($module);
        }
        $this->modules_are_loaded = true;
    }
    /**
     * Load the provided modules.
     *
     * @triggers loadModules
     * @triggers loadModules.post
     */
    #[Override]
    public function load_modules(): static
    {
        if (true === $this->modules_are_loaded) {
            return $this;
        }
        $events = $this->get_event_manager();
        $event = $this->get_event();
        $event->set_name(Module_Event::EVENT_LOAD_MODULES);
        $events->trigger_event($event);
        /**
         * Having a dedicated .post event abstracts the complexity of priorities from the user.
         * Users can attach to the .post event and be sure that important
         * things like config merging are complete without having to worry if
         * they set a low enough priority.
         */
        $event->set_name(Module_Event::EVENT_LOAD_MODULES_POST);
        $events->trigger_event($event);
        return $this;
    }
    /**
     * Load a specific module by name.
     *
     * @param  string|array               $module
     * @throws Exception\RuntimeException
     * @triggers loadModule.resolve
     * @triggers loadModule
     * @return mixed Module's Module class
     */
    #[Override]
    public function load_module($module)
    {
        $module_name = $module;
        if (is_array($module)) {
            $module_name = key($module);
            $module = current($module);
        }
        if (isset($this->loaded_modules[$module_name])) {
            return $this->loaded_modules[$module_name];
        }
        /*
         * Keep track of nested module loading using the $loadFinished
         * property.
         *
         * Increment the value for each loadModule() call and then decrement
         * once the loading process is complete.
         *
         * To load a module, we clone the event if we are inside a nested
         * loadModule() call, and use the original event otherwise.
         */
        if (!isset($this->load_finished)) {
            $this->load_finished = 0;
        }
        $event = $this->load_finished > 0 ? clone $this->get_event() : $this->get_event();
        $event->set_module_name($module_name);
        $this->load_finished++;
        if (!is_object($module)) {
            $module = $this->load_module_by_name($event);
        }
        $event->set_module($module);
        $event->set_name(Module_Event::EVENT_LOAD_MODULE);
        $this->loaded_modules[$module_name] = $module;
        $this->get_event_manager()->trigger_event($event);
        $this->load_finished--;
        return $module;
    }
    /**
     * Load a module with the name
     *
     * @return mixed                            module instance
     * @throws Exception\RuntimeException
     */
    protected function load_module_by_name(Module_Event $event): object
    {
        $event->set_name(Module_Event::EVENT_LOAD_MODULE_RESOLVE);
        $result = $this->get_event_manager()->trigger_event_until(static fn($r): bool => is_object($r), $event);
        $module = $result->last();
        if (!is_object($module)) {
            throw new Exception\RuntimeException(sprintf('Module (%s) could not be initialized.', $event->get_module_name()));
        }
        return $module;
    }
    /**
     * Get an array of the loaded modules.
     *
     * @param  bool  $loadModules If true, load modules if they're not already
     * @return array An array of Module objects, keyed by module name
     */
    #[Override]
    public function get_loaded_modules($load_modules = false)
    {
        if (true === $load_modules) {
            $this->load_modules();
        }
        return $this->loaded_modules;
    }
    /**
     * Get an instance of a module class by the module name
     *
     * @param  string $moduleName
     * @return mixed
     */
    public function get_module($module_name)
    {
        if (!isset($this->loaded_modules[$module_name])) {
            return;
        }
        return $this->loaded_modules[$module_name];
    }
    /**
     * Get the array of module names that this manager should load.
     *
     * @return array
     */
    #[Override]
    public function get_modules()
    {
        return $this->modules;
    }
    /**
     * Set an array or Traversable of module names that this module manager should load.
     *
     * @param  mixed $modules array or Traversable of module names
     * @throws Exception\InvalidArgumentException
     */
    #[Override]
    public function set_modules($modules): static
    {
        if (is_iterable($modules)) {
            $this->modules = $modules;
        } else {
            throw new Exception\InvalidArgumentException(sprintf('Parameter to %s\'s %s method must be an array or implement the Traversable interface', self::class, __METHOD__));
        }
        return $this;
    }
    /**
     * Get the module event
     *
     * @return ModuleEvent
     */
    public function get_event()
    {
        if (!$this->event instanceof Module_Event) {
            $this->set_event(new Module_Event());
        }
        return $this->event;
    }
    /**
     * Set the module event
     */
    public function set_event(Module_Event $event): static
    {
        $event->set_target($this);
        $this->event = $event;
        return $this;
    }
    /**
     * Set the event manager instance used by this module manager.
     */
    #[Override]
    public function set_event_manager(Event_Manager_Interface $events): static
    {
        $events->set_identifiers([self::class, static::class, 'module_manager']);
        $this->events = $events;
        $this->attach_default_listeners($events);
        return $this;
    }
    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    #[Override]
    public function get_event_manager()
    {
        if (!$this->events instanceof Event_Manager_Interface) {
            $this->set_event_manager(new Event_Manager());
        }
        return $this->events;
    }
    /**
     * Register the default event listeners
     *
     * @param EventManagerInterface $events
     */
    protected function attach_default_listeners($events)
    {
        $events->attach(Module_Event::EVENT_LOAD_MODULES, $this->on_load_modules(...));
    }
}