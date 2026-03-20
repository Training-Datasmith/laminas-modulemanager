<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use function class_exists;
use Generator;
use function in_array;
use Laminas\Module_Manager\Module_Event;
use function sprintf;
class Module_Resolver_Listener extends Abstract_Listener
{
    /**
     * Class names that are invalid as module classes, due to inability to instantiate.
     *
     * @var string[]
     */
    protected $invalid_class_names = [Generator::class];
    /**
     * @return object|false False if module class does not exist
     */
    public function __invoke(Module_Event $e): object|false
    {
        $module_name = $e->get_module_name();
        $class = sprintf('%s\Module', $module_name);
        if (class_exists($class)) {
            return new $class();
        }
        if (class_exists($module_name) && !in_array($module_name, $this->invalid_class_names, true)) {
            return new $module_name();
        }
        return false;
    }
}