<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use function class_exists;
use function gettype;
use function is_array;
use function is_object;
use function is_scalar;
use function is_string;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Module_Manager\Module_Event;
use Laminas\Service_Manager\Config as ServiceConfig;
use Laminas\Service_Manager\Config_Interface as ServiceConfigInterface;
use Laminas\Service_Manager\Service_Manager;
use Laminas\Stdlib\Array_Utils;
use function method_exists;
use Override;
use function spl_object_hash;
use function sprintf;
use Traversable;
class Service_Listener implements Service_Listener_Interface
{
    /**
     * Service manager post-configuration.
     *
     * @var ServiceManager
     */
    protected $configured_service_manager;
    /** @var callable[] */
    protected $listeners = [];
    /**
     * Default service configuration for the application service manager.
     *
     * @var array
     */
    protected $default_service_config;
    /** @var array */
    protected $service_managers = [];
    /** @param null|array $configuration */
    public function __construct(
        /**
         * Default service manager used to fulfill other SMs that need to be lazy loaded
         */
        protected Service_Manager $default_service_manager,
        $configuration = null
    )
    {
        if ($configuration !== null) {
            $this->set_default_service_config($configuration);
        }
    }
    /**
     * @param  array $configuration
     */
    #[Override]
    public function set_default_service_config($configuration): static
    {
        $this->default_service_config = $configuration;
        return $this;
    }
    /** {@inheritDoc} */
    #[Override]
    public function add_service_manager($service_manager, $key, $module_interface, $method): static
    {
        if (is_string($service_manager)) {
            $sm_key = $service_manager;
        } elseif ($service_manager instanceof Service_Manager) {
            $sm_key = spl_object_hash($service_manager);
        } else {
            throw new Exception\RuntimeException(sprintf('Invalid service manager provided, expected ServiceManager or string, %s provided', get_debug_type($service_manager)));
        }
        $this->service_managers[$sm_key] = ['service_manager' => $service_manager, 'config_key' => $key, 'module_class_interface' => $module_interface, 'module_class_method' => $method, 'configuration' => []];
        if ($key === 'service_manager' && $this->default_service_config) {
            $this->service_managers[$sm_key]['configuration']['default_config'] = $this->default_service_config;
        }
        return $this;
    }
    /**
     * @param  int $priority
     */
    #[Override]
    public function attach(Event_Manager_Interface $events, $priority = 1): static
    {
        $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULE, $this->on_load_module(...));
        $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULES_POST, $this->on_load_modules_post(...));
        return $this;
    }
    #[Override]
    public function detach(Event_Manager_Interface $events): void
    {
        foreach ($this->listeners as $key => $listener) {
            if ($events->detach($listener)) {
                unset($this->listeners[$key]);
            }
        }
    }
    /**
     * Retrieve service manager configuration from module, and
     * configure the service manager.
     *
     * If the module does not implement a specific interface and does not
     * implement a specific method, does nothing. Also, if the return value
     * of that method is not a ServiceConfig object, or not an array or
     * Traversable that can seed one, does nothing.
     *
     * The interface and method name can be set by adding a new service manager
     * via the addServiceManager() method.
     */
    public function on_load_module(Module_Event $e): void
    {
        $module = $e->get_module();
        foreach ($this->service_managers as $key => $sm) {
            if (!$module instanceof $sm['module_class_interface'] && !method_exists($module, $sm['module_class_method'])) {
                continue;
            }
            $config = $module->{$sm['module_class_method']}();
            if ($config instanceof Service_Config_Interface) {
                $config = $this->service_config_to_array($config);
            }
            if ($config instanceof Traversable) {
                $config = Array_Utils::iterator_to_array($config);
            }
            if (!is_array($config)) {
                // If we do not have an array by this point, nothing left to do.
                continue;
            }
            // We are keeping track of which modules provided which configuration to which service managers.
            // The actual merging takes place later. Doing it this way will enable us to provide more powerful
            // debugging tools for showing which modules overrode what.
            $fullname = $e->get_module_name() . '::' . $sm['module_class_method'] . '()';
            /** @codingStandardsIgnoreLine */
            $this->service_managers[$key]['configuration'][$fullname] = $config;
        }
    }
    /**
     * Use merged configuration to configure service manager
     *
     * If the merged configuration has a non-empty, array 'service_manager'
     * key, it will be passed to a ServiceManager Config object, and
     * used to configure the service manager.
     *
     * @throws Exception\RuntimeException
     */
    public function on_load_modules_post(Module_Event $e): void
    {
        $config_listener = $e->get_config_listener();
        $config = $config_listener->get_merged_config(false);
        foreach ($this->service_managers as $key => $sm) {
            $sm_config = $this->merge_service_configuration($key, $sm, $config);
            if (!$sm['service_manager'] instanceof Service_Manager) {
                if (!$this->default_service_manager->has($sm['service_manager'])) {
                    // No plugin manager registered by that name; nothing to configure.
                    continue;
                }
                $instance = $this->default_service_manager->get($sm['service_manager']);
                if (!$instance instanceof Service_Manager) {
                    throw new Exception\RuntimeException(sprintf('Could not find a valid ServiceManager for %s', $sm['service_manager']));
                }
                $sm['service_manager'] = $instance;
            }
            $service_config = new Service_Config($sm_config);
            // The service listener is meant to operate during bootstrap, and, as such,
            // needs to be able to override existing configuration.
            $allow_override = $sm['service_manager']->get_allow_override();
            $sm['service_manager']->set_allow_override(true);
            $service_config->configure_service_manager($sm['service_manager']);
            $sm['service_manager']->set_allow_override($allow_override);
        }
    }
    /**
     * Merge a service configuration container
     *
     * Extracts the various service configuration arrays.
     *
     * @param ServiceConfigInterface|string $config ServiceConfigInterface or
     *     class name resolving to one.
     * @return array
     * @throws Exception\RuntimeException If resolved class name is not a
     *     ServiceConfigInterface implementation.
     * @throws Exception\RuntimeException Under laminas-servicemanager v2 if the
     *     configuration instance is not specifically a ServiceConfig, as there
     *     is no way to extract service configuration in that case.
     */
    protected function service_config_to_array($config)
    {
        if (is_string($config) && class_exists($config)) {
            $class = $config;
            $config = new $class();
        }
        if (!$config instanceof Service_Config_Interface) {
            throw new Exception\RuntimeException(sprintf('Invalid service manager configuration class provided; received "%s", expected an instance of %s', is_object($config) ? $config::class : (is_scalar($config) ? $config : gettype($config)), Service_Config_Interface::class));
        }
        if (method_exists($config, 'toArray')) {
            // laminas-servicemanager v3 interface
            return $config->to_array();
        }
        // For laminas-servicemanager v2, we need a Laminas\ServiceManager\Config
        // instance specifically.
        if (!$config instanceof Service_Config) {
            throw new Exception\RuntimeException(sprintf('Invalid service manager configuration class provided; received "%s", expected an instance of %s', is_object($config) ? $config::class : (is_scalar($config) ? $config : gettype($config)), Service_Config::class));
        }
        // Pull service configuration from discrete methods.
        return ['abstract_factories' => $config->get_abstract_factories(), 'aliases' => $config->get_aliases(), 'delegators' => $config->get_delegators(), 'factories' => $config->get_factories(), 'initializers' => $config->get_initializers(), 'invokables' => $config->get_invokables(), 'services' => $config->get_services(), 'shared' => $config->get_shared()];
    }
    /**
     * Merge all configuration for a given service manager to a single array.
     *
     * @param string $key Named service manager
     * @param array $metadata Service manager metadata
     * @param array $config Merged configuration
     * @return array Service manager-specific configuration
     */
    private function merge_service_configuration($key, array $metadata, array $config)
    {
        if (isset($config[$metadata['config_key']]) && is_array($config[$metadata['config_key']]) && !empty($config[$metadata['config_key']])) {
            $this->service_managers[$key]['configuration']['merged_config'] = $config[$metadata['config_key']];
        }
        // Merge all of the things!
        $service_config = [];
        foreach ($this->service_managers[$key]['configuration'] as $configs) {
            if (isset($configs['configuration_classes'])) {
                foreach ($configs['configuration_classes'] as $class) {
                    $configs = Array_Utils::merge($configs, $this->service_config_to_array($class));
                }
            }
            $service_config = Array_Utils::merge($service_config, $configs);
        }
        return $service_config;
    }
}