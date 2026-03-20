<?php

declare (strict_types=1);
namespace Laminas\Module_Manager;

use function gettype;
use function is_object;
use function is_string;
use Laminas\Event_Manager\Event;
use function sprintf;
/**
 * Custom event for use with module manager
 * Composes Module objects
 */
class Module_Event extends Event
{
    /**
     * Module events triggered by eventmanager
     */
    public const EVENT_MERGE_CONFIG = 'mergeConfig';
    public const EVENT_LOAD_MODULES = 'loadModules';
    public const EVENT_LOAD_MODULE_RESOLVE = 'loadModule.resolve';
    public const EVENT_LOAD_MODULE = 'loadModule';
    public const EVENT_LOAD_MODULES_POST = 'loadModules.post';
    /** @var mixed */
    protected $module;
    /** @var string */
    protected $module_name;
    /** @var Listener\ConfigMergerInterface */
    protected $config_listener;
    /**
     * Get the name of a given module
     *
     * @return string
     */
    public function get_module_name()
    {
        return $this->module_name;
    }
    /**
     * Set the name of a given module
     *
     * @param  string $moduleName
     * @throws Exception\InvalidArgumentException
     * @return ModuleEvent
     */
    public function set_module_name($module_name)
    {
        if (!is_string($module_name)) {
            throw new Exception\InvalidArgumentException(sprintf('%s expects a string as an argument; %s provided', __METHOD__, gettype($module_name)));
        }
        // Performance tweak, don't add it as param.
        $this->module_name = $module_name;
        return $this;
    }
    /**
     * Get module object
     *
     * @return null|object
     */
    public function get_module()
    {
        return $this->module;
    }
    /**
     * Set module object to compose in this event
     *
     * @param  object $module
     * @throws Exception\InvalidArgumentException
     * @return ModuleEvent
     */
    public function set_module($module)
    {
        if (!is_object($module)) {
            throw new Exception\InvalidArgumentException(sprintf('%s expects a module object as an argument; %s provided', __METHOD__, gettype($module)));
        }
        // Performance tweak, don't add it as param.
        $this->module = $module;
        return $this;
    }
    /**
     * Get the config listener
     *
     * @return null|Listener\ConfigMergerInterface
     */
    public function get_config_listener()
    {
        return $this->config_listener;
    }
    /**
     * Set module object to compose in this event
     *
     * @return ModuleEvent
     */
    public function set_config_listener(Listener\Config_Merger_Interface $config_listener)
    {
        $this->set_param('configListener', $config_listener);
        $this->config_listener = $config_listener;
        return $this;
    }
}