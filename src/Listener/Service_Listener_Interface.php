<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use Laminas\Event_Manager\Listener_Aggregate_Interface;
interface Service_Listener_Interface extends Listener_Aggregate_Interface
{
    /**
     * Provide metadata describing how to aggregate service/plugin manager configuration.
     *
     * - $serviceManager is the service name for the service/plugin manager.
     * - $key is the configuration key containing configuration for it.
     * - $moduleInterface is the interface indicating a configuration provider for it.
     * - $method is used for duck-typing configuration providers.
     *
     * @param  string $serviceManager  Service name for service/plugin manager
     * @param  string $key             Configuration key
     * @param  string $moduleInterface FQCN as string
     * @param  string $method          Method name
     * @return ServiceListenerInterface
     */
    public function add_service_manager($service_manager, $key, $module_interface, $method);
    /**
     * @param  array $configuration
     * @return ServiceListenerInterface
     */
    public function set_default_service_config($configuration);
}