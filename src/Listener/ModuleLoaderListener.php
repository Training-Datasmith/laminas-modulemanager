<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use function file_exists;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Event_Manager\Listener_Aggregate_Interface;
use Laminas\Loader\Module_Autoloader;
use Laminas\Module_Manager\Module_Event;
use Override;
class Module_Loader_Listener extends Abstract_Listener implements Listener_Aggregate_Interface
{
    /** @var ModuleAutoloader */
    protected $module_loader;
    /** @var bool */
    protected $generate_cache;
    /** @var array */
    protected $callbacks = [];
    /**
     * Creates an instance of the ModuleAutoloader and injects the module paths
     * into it.
     */
    public function __construct(?Listener_Options $options = null)
    {
        parent::__construct($options);
        $this->generate_cache = $this->options->get_module_map_cache_enabled();
        $this->module_loader = new Module_Autoloader($this->options->get_module_paths());
        if ($this->has_cached_class_map()) {
            $this->generate_cache = false;
            $this->module_loader->set_module_class_map($this->get_cached_config());
        }
    }
    /** {@inheritDoc} */
    #[Override]
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->callbacks[] = $events->attach(Module_Event::EVENT_LOAD_MODULES, [$this->module_loader, 'register'], 9000);
        if ($this->generate_cache) {
            $this->callbacks[] = $events->attach(Module_Event::EVENT_LOAD_MODULES_POST, $this->on_load_modules_post(...));
        }
    }
    /** {@inheritDoc} */
    #[Override]
    public function detach(Event_Manager_Interface $events): void
    {
        foreach ($this->callbacks as $index => $callback) {
            if ($events->detach($callback)) {
                unset($this->callbacks[$index]);
            }
        }
    }
    protected function has_cached_class_map(): bool
    {
        if ($this->options->get_module_map_cache_enabled() && file_exists($this->options->get_module_map_cache_file())) {
            return true;
        }
        return false;
    }
    /** @return array */
    protected function get_cached_config()
    {
        return include $this->options->get_module_map_cache_file();
    }
    /**
     * Unregisters the ModuleLoader and generates the module class map cache.
     */
    public function on_load_modules_post(Module_Event $event): void
    {
        $this->module_loader->unregister();
        $this->write_array_to_file($this->options->get_module_map_cache_file(), $this->module_loader->get_module_class_map());
    }
}