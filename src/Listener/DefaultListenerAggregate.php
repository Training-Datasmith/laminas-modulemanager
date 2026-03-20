<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Event_Manager\Listener_Aggregate_Interface;
use Laminas\Module_Manager\Module_Event;
use Override;
class Default_Listener_Aggregate extends Abstract_Listener implements Listener_Aggregate_Interface
{
    /** @var array */
    protected $listeners = [];
    /** @var ConfigMergerInterface */
    protected $config_listener;
    /**
     * Attach one or more listeners
     *
     * @param  int $priority
     */
    #[Override]
    public function attach(Event_Manager_Interface $events, $priority = 1): static
    {
        $options = $this->get_options();
        $config_listener = $this->get_config_listener();
        $locator_registration_listener = new Locator_Registration_Listener($options);
        // High priority, we assume module autoloading (for FooNamespace\Module
        // classes) should be available before anything else.
        // Register it only if use_laminas_loader config is true, however.
        if ($options->use_laminas_loader()) {
            $module_loader_listener = new Module_Loader_Listener($options);
            $module_loader_listener->attach($events);
            $this->listeners[] = $module_loader_listener;
        }
        $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULE_RESOLVE, new Module_Resolver_Listener());
        if ($options->use_laminas_loader()) {
            // High priority, because most other loadModule listeners will assume
            // the module's classes are available via autoloading
            // Register it only if use_laminas_loader config is true, however.
            $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULE, new Autoloader_Listener($options), 9000);
        }
        if ($options->get_check_dependencies()) {
            $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULE, new Module_Dependency_Checker_Listener(), 8000);
        }
        $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULE, new Init_Trigger($options));
        $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULE, new On_Bootstrap_Listener($options));
        $locator_registration_listener->attach($events);
        $config_listener->attach($events);
        $this->listeners[] = $locator_registration_listener;
        $this->listeners[] = $config_listener;
        return $this;
    }
    /**
     * Detach all previously attached listeners
     */
    #[Override]
    public function detach(Event_Manager_Interface $events): void
    {
        foreach ($this->listeners as $key => $listener) {
            if ($listener instanceof Listener_Aggregate_Interface) {
                $listener->detach($events);
                unset($this->listeners[$key]);
                continue;
            }
            $events->detach($listener);
            unset($this->listeners[$key]);
        }
    }
    /**
     * Get the config merger.
     *
     * @return ConfigMergerInterface
     */
    public function get_config_listener()
    {
        if (!$this->config_listener instanceof Config_Merger_Interface) {
            $this->set_config_listener(new Config_Listener($this->get_options()));
        }
        return $this->config_listener;
    }
    /**
     * Set the config merger to use.
     */
    public function set_config_listener(Config_Merger_Interface $config_listener): static
    {
        $this->config_listener = $config_listener;
        return $this;
    }
}