<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use function file_exists;
use function gettype;
use function is_array;
use function is_callable;
use function is_string;
use Laminas\Config\Config;
use Laminas\Config\Factory as ConfigFactory;
use Laminas\Event_Manager\Event_Manager_Interface;
use Laminas\Event_Manager\Listener_Aggregate_Interface;
use Laminas\Event_Manager\Listener_Aggregate_Trait;
use Laminas\Module_Manager\Feature\Config_Provider_Interface;
use Laminas\Module_Manager\Module_Event;
use Laminas\Stdlib\Array_Utils;
use Laminas\Stdlib\Glob;
use Override;
use function sprintf;
use Traversable;
class Config_Listener extends Abstract_Listener implements Config_Merger_Interface, Listener_Aggregate_Interface
{
    use Listener_Aggregate_Trait;
    public const STATIC_PATH = 'static_path';
    public const GLOB_PATH = 'glob_path';
    /** @var array */
    protected $configs = [];
    /** @var array */
    protected $merged_config = [];
    /** @var Config|null */
    protected $merged_config_object;
    /** @var bool */
    protected $skip_config = false;
    /** @var array */
    protected $paths = [];
    public function __construct(?Listener_Options $options = null)
    {
        parent::__construct($options);
        if ($this->has_cached_config()) {
            $this->skip_config = true;
            $this->set_merged_config($this->get_cached_config());
        } else {
            $this->add_config_glob_paths($this->get_options()->get_config_glob_paths());
            $this->add_config_static_paths($this->get_options()->get_config_static_paths());
        }
    }
    /** {@inheritDoc} */
    #[Override]
    public function attach(Event_Manager_Interface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULES, $this->onload_modules_pre(...), 1000);
        if ($this->skip_config) {
            // We already have the config from cache, no need to collect or merge.
            return;
        }
        $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULE, $this->on_load_module(...));
        $this->listeners[] = $events->attach(Module_Event::EVENT_LOAD_MODULES, $this->on_load_modules(...), -1000);
        $this->listeners[] = $events->attach(Module_Event::EVENT_MERGE_CONFIG, $this->on_merge_config(...), 1000);
    }
    /**
     * Pass self to the ModuleEvent object early so everyone has access.
     */
    public function onload_modules_pre(Module_Event $e): static
    {
        $e->set_config_listener($this);
        return $this;
    }
    /**
     * Merge the config for each module
     */
    public function on_load_module(Module_Event $e): static
    {
        $module = $e->get_module();
        if (!$module instanceof Config_Provider_Interface && !is_callable([$module, 'getConfig'])) {
            return $this;
        }
        $config = $module->get_config();
        $this->add_config($e->get_module_name(), $config);
        return $this;
    }
    /**
     * Merge all config files matched by the given glob()s
     *
     * This is only attached if config is not cached.
     */
    public function on_merge_config(Module_Event $e): static
    {
        // Load the config files
        foreach ($this->paths as $path) {
            $this->add_config_by_path($path['path'], $path['type']);
        }
        // Merge all of the collected configs
        $this->merged_config = $this->get_options()->get_extra_config() ?: [];
        foreach ($this->configs as $config) {
            $this->merged_config = Array_Utils::merge($this->merged_config, $config);
        }
        return $this;
    }
    /**
     * Optionally cache merged config
     *
     * This is only attached if config is not cached.
     */
    public function on_load_modules(Module_Event $e): static
    {
        // Trigger MERGE_CONFIG event. This is a hook to allow the merged application config to be
        // modified before it is cached (In particular, allows the removal of config keys)
        $original_event_name = $e->get_name();
        $e->set_name(Module_Event::EVENT_MERGE_CONFIG);
        $e->get_target()->get_event_manager()->trigger_event($e);
        // Reset event name
        $e->set_name($original_event_name);
        // If enabled, update the config cache
        if ($this->get_options()->get_config_cache_enabled() && false === $this->skip_config) {
            $config_file = $this->get_options()->get_config_cache_file();
            $this->write_array_to_file($config_file, $this->get_merged_config(false));
        }
        return $this;
    }
    /**
     * @param  bool $returnConfigAsObject
     * @return mixed
     */
    #[Override]
    public function get_merged_config($return_config_as_object = true)
    {
        if ($return_config_as_object === true) {
            if ($this->merged_config_object === null) {
                $this->merged_config_object = new Config($this->merged_config);
            }
            return $this->merged_config_object;
        }
        return $this->merged_config;
    }
    #[Override]
    public function set_merged_config(array $config): static
    {
        $this->merged_config = $config;
        $this->merged_config_object = null;
        return $this;
    }
    /**
     * Add an array of glob paths of config files to merge after loading modules
     *
     * @param  array|Traversable $globPaths
     */
    public function add_config_glob_paths($glob_paths): static
    {
        $this->add_config_paths($glob_paths, self::GLOB_PATH);
        return $this;
    }
    /**
     * Add a glob path of config files to merge after loading modules
     *
     * @param  string $globPath
     */
    public function add_config_glob_path($glob_path): static
    {
        $this->add_config_path($glob_path, self::GLOB_PATH);
        return $this;
    }
    /**
     * Add an array of static paths of config files to merge after loading modules
     *
     * @param  array|Traversable $staticPaths
     */
    public function add_config_static_paths($static_paths): static
    {
        $this->add_config_paths($static_paths, self::STATIC_PATH);
        return $this;
    }
    /**
     * Add a static path of config files to merge after loading modules
     *
     * @param  string $staticPath
     */
    public function add_config_static_path($static_path): static
    {
        $this->add_config_path($static_path, self::STATIC_PATH);
        return $this;
    }
    /**
     * Add an array of paths of config files to merge after loading modules
     *
     * @param  Traversable|array $paths
     * @param string $type
     * @throws Exception\InvalidArgumentException
     */
    protected function add_config_paths($paths, $type)
    {
        if ($paths instanceof Traversable) {
            $paths = Array_Utils::iterator_to_array($paths);
        }
        if (!is_array($paths)) {
            throw new Exception\InvalidArgumentException(sprintf('Argument passed to %s::%s() must be an array, ' . 'implement the Traversable interface, or be an ' . 'instance of Laminas\Config\Config. %s given.', self::class, __METHOD__, gettype($paths)));
        }
        foreach ($paths as $path) {
            $this->add_config_path($path, $type);
        }
    }
    /**
     * Add a path of config files to load and merge after loading modules
     *
     * @param  string $path
     * @param  string $type
     * @throws Exception\InvalidArgumentException
     */
    protected function add_config_path($path, $type): static
    {
        if (!is_string($path)) {
            throw new Exception\InvalidArgumentException(sprintf('Parameter to %s::%s() must be a string; %s given.', self::class, __METHOD__, gettype($path)));
        }
        $this->paths[] = ['type' => $type, 'path' => $path];
        return $this;
    }
    /**
     * @param string $key
     * @param array|Traversable $config
     * @throws Exception\InvalidArgumentException
     */
    protected function add_config($key, $config): static
    {
        if ($config instanceof Traversable) {
            $config = Array_Utils::iterator_to_array($config);
        }
        if (!is_array($config)) {
            throw new Exception\InvalidArgumentException(sprintf('Config being merged must be an array, ' . 'implement the Traversable interface, or be an ' . 'instance of Laminas\Config\Config. %s given.', gettype($config)));
        }
        $this->configs[$key] = $config;
        return $this;
    }
    /**
     * Given a path (glob or static), fetch the config and add it to the array
     * of configs to merge.
     *
     * @param string $path
     * @param string $type
     */
    protected function add_config_by_path($path, $type): static
    {
        switch ($type) {
            case self::STATIC_PATH:
                $this->add_config($path, Config_Factory::from_file($path));
                break;
            case self::GLOB_PATH:
                // We want to keep track of where each value came from so we don't
                // use ConfigFactory::fromFiles() since it does merging internally.
                foreach (Glob::glob($path, Glob::GLOB_BRACE, true) as $file) {
                    $this->add_config($file, Config_Factory::from_file($file));
                }
                break;
        }
        return $this;
    }
    protected function has_cached_config(): bool
    {
        if ($this->get_options()->get_config_cache_enabled() && file_exists($this->get_options()->get_config_cache_file())) {
            return true;
        }
        return false;
    }
    /** @return mixed */
    protected function get_cached_config()
    {
        return include $this->get_options()->get_config_cache_file();
    }
}