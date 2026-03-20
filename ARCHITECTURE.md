# Architecture: laminas-modulemanager

## Purpose
The laminas-mvc module system. Discovers, loads, and bootstraps PHP modules; merges module configuration; manages module autoloading; and coordinates the module lifecycle via events.

## Directory Structure
```
src/
  Module_Manager.php               # Orchestrates module loading lifecycle
  Module_Manager_Interface.php     # Contract for the manager
  Module_Event.php                 # Event emitted at each lifecycle stage
  Feature/                         # Interfaces modules can implement to provide services
    Config_Provider_Interface.php  # getConfig(): array
    Service_Provider_Interface.php # getServiceConfig(): array
    Autoloader_Provider_Interface.php
    Controller_Provider_Interface.php
    Route_Provider_Interface.php
    View_Helper_Provider_Interface.php
    Dependency_Indicator_Interface.php  # Declare module dependencies
    Init_Provider_Interface.php
    Bootstrap_Listener_Interface.php
    ... (20+ Feature interfaces)
  Listener/
    Default_Listener_Aggregate.php # Attaches all standard listeners as a bundle
    Config_Listener.php            # Loads and merges module configs; handles config caching
    Module_Loader_Listener.php     # Instantiates the module class
    Module_Resolver_Listener.php   # Resolves module names to class names
    Autoloader_Listener.php        # Registers module-provided autoloaders
    Service_Listener.php           # Registers module-provided service configs
    Module_Dependency_Checker_Listener.php  # Validates dependency declarations
    Init_Trigger.php               # Calls Module::init() if supported
    On_Bootstrap_Listener.php      # Calls Module::onBootstrap() if supported
    Listener_Options.php           # Configuration options for listeners
  Exception/                       # Typed exceptions (missing dependency, etc.)
```

## Key Design Decisions
- **Event-driven lifecycle** — each stage of module loading (resolve → load → init → bootstrap) fires a `Module_Event` via `laminas-eventmanager`. Listeners hook into these events rather than being called directly.
- **Feature interfaces** — modules opt into capabilities by implementing feature interfaces. The manager checks `instanceof` to determine what each module provides.
- **Config caching** — `Config_Listener` can cache merged module configuration to a PHP file, eliminating repeated file loading on warm requests.
- **Dependency declarations** — modules can declare their dependencies via `Dependency_Indicator_Interface`. `Module_Dependency_Checker_Listener` validates that all declared dependencies are loaded first.

## Extension Points
- Implement any `Feature/*_Interface` in a module class to provide services, routes, view helpers, etc.
- Register a custom listener in the module manager's event manager for custom lifecycle hooks.
- Provide a custom `Config_Merger_Interface` implementation to change how module configs are merged.

## Dependency Flow
```
Module_Manager::loadModules(['Application', 'Blog', ...])
  └─ For each module:
       ├─ Module_Resolver_Listener → resolve class name
       ├─ Module_Loader_Listener → instantiate module class
       ├─ Config_Listener → merge getConfig() output
       ├─ Service_Listener → register getServiceConfig()
       ├─ Autoloader_Listener → register getAutoloaderConfig()
       ├─ Init_Trigger → Module::init()
       └─ On_Bootstrap_Listener → Module::onBootstrap()
```
