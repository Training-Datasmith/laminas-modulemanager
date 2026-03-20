<?php

declare (strict_types=1);
namespace Laminas\Module_Manager;

use Laminas\Event_Manager\Event_Manager_Aware_Interface;
interface Module_Manager_Interface extends Event_Manager_Aware_Interface
{
    /**
     * Load the provided modules.
     *
     * @return ModuleManagerInterface
     */
    public function load_modules();
    /**
     * Load a specific module by name.
     *
     * @param  string $moduleName
     * @return mixed Module's Module class
     */
    public function load_module($module_name);
    /**
     * Get an array of the loaded modules.
     *
     * @param  bool $loadModules If true, load modules if they're not already
     * @return array An array of Module objects, keyed by module name
     */
    public function get_loaded_modules($load_modules);
    /**
     * Get the array of module names that this manager should load.
     *
     * @return array
     */
    public function get_modules();
    /**
     * Set an array or Traversable of module names that this module manager should load.
     *
     * @param  mixed $modules array or Traversable of module names
     * @return ModuleManagerInterface
     */
    public function set_modules($modules);
}