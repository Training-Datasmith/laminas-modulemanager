<?php

declare(strict_types=1);

/**
 * Example: demonstrating the laminas-modulemanager module lifecycle interfaces.
 *
 * Shows how a module class implements feature interfaces that are called during
 * the load and merge-config phases of a Laminas MVC application bootstrap.
 *
 * Run from the laminas-modulemanager project root:
 *   php examples/module_lifecycle.php
 */

require __DIR__ . '/../vendor/autoload.php';

use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\ModuleManager\Feature\AutoloaderProviderInterface;
use Laminas\ModuleManager\Feature\ServiceProviderInterface;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\EventManager\EventManager;

/**
 * Example application module implementing multiple feature interfaces.
 */
final class ApplicationModule implements
    ConfigProviderInterface,
    AutoloaderProviderInterface
{
    public function getConfig(): array
    {
        return [
            'router' => [
                'routes' => [
                    'home' => [
                        'type'    => 'Literal',
                        'options' => ['route' => '/', 'defaults' => ['controller' => 'Application\Controller\Index']],
                    ],
                ],
            ],
            'view_manager' => ['template_path_stack' => [__DIR__ . '/view']],
        ];
    }

    public function getAutoloaderConfig(): array
    {
        return [
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => ['Application' => __DIR__ . '/src/Application'],
            ],
        ];
    }
}

// --- Demonstrate that the module provides config ---
$module = new ApplicationModule();
$config = $module->getConfig();

echo "Module config keys: " . implode(', ', array_keys($config)) . "\n";
echo "Router routes:      " . implode(', ', array_keys($config['router']['routes'])) . "\n\n";

// --- Show ModuleManager feature interfaces ---
$interfaces = [
    ConfigProviderInterface::class     => 'getConfig()',
    AutoloaderProviderInterface::class => 'getAutoloaderConfig()',
    ServiceProviderInterface::class    => 'getServiceConfig()',
];

echo "Laminas module feature interfaces:\n";
foreach ($interfaces as $interface => $method) {
    $implements = is_a(ApplicationModule::class, $interface, true);
    printf("  %-50s %s => %s\n", $interface, $method, $implements ? 'implemented' : 'not implemented');
}
