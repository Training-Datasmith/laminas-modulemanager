<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use Laminas\Module_Manager\Exception;
use Laminas\Module_Manager\Feature\Dependency_Indicator_Interface;
use Laminas\Module_Manager\Module_Event;
use function method_exists;
use function sprintf;
class Module_Dependency_Checker_Listener
{
    /** @var array of already loaded modules, indexed by module name */
    protected $loaded = [];
    /** @throws Exception\MissingDependencyModuleException */
    public function __invoke(Module_Event $e): void
    {
        $module = $e->get_module();
        if ($module instanceof Dependency_Indicator_Interface || method_exists($module, 'getModuleDependencies')) {
            $dependencies = $module->get_module_dependencies();
            foreach ($dependencies as $dependency_module) {
                if (!isset($this->loaded[$dependency_module])) {
                    throw new Exception\Missing_Dependency_Module_Exception(sprintf('Module "%s" depends on module "%s", which was not initialized before it', $e->get_module_name(), $dependency_module));
                }
            }
        }
        $this->loaded[$e->get_module_name()] = true;
    }
}