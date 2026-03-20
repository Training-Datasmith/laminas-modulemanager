<?php

declare (strict_types=1);
namespace Laminas\Module_Manager\Listener;

use function func_get_args;
use function gettype;
use function is_array;
use Laminas\Stdlib\Abstract_Options;
use function rtrim;
use function sprintf;
use Traversable;
class Listener_Options extends Abstract_Options
{
    /** @var array */
    protected $module_paths = [];
    /** @var array */
    protected $config_glob_paths = [];
    /** @var array */
    protected $config_static_paths = [];
    /** @var array */
    protected $extra_config = [];
    /** @var bool */
    protected $config_cache_enabled = false;
    /** @var string */
    protected $config_cache_key;
    /** @var string|null */
    protected $cache_dir;
    /** @var bool */
    protected $check_dependencies = true;
    /** @var bool */
    protected $module_map_cache_enabled = false;
    /** @var string */
    protected $module_map_cache_key;
    /** @var bool */
    protected $use_laminas_loader = true;
    /**
     * Get an array of paths where modules reside
     *
     * @return array
     */
    public function get_module_paths()
    {
        return $this->module_paths;
    }
    /**
     * Set an array of paths where modules reside
     *
     * @param  array|Traversable $modulePaths
     * @throws Exception\InvalidArgumentException
     * @return ListenerOptions Provides fluent interface
     */
    public function set_module_paths($module_paths)
    {
        if (!is_array($module_paths) && !$module_paths instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf('Argument passed to %s::%s() must be an array, ' . 'implement the Traversable interface, or be an ' . 'instance of Laminas\Config\Config. %s given.', self::class, __METHOD__, gettype($module_paths)));
        }
        $this->module_paths = $module_paths;
        return $this;
    }
    /**
     * Get the glob patterns to load additional config files
     *
     * @return array
     */
    public function get_config_glob_paths()
    {
        return $this->config_glob_paths;
    }
    /**
     * Get the static paths to load additional config files
     *
     * @return array
     */
    public function get_config_static_paths()
    {
        return $this->config_static_paths;
    }
    /**
     * Set the glob patterns to use for loading additional config files
     *
     * @param array|Traversable $configGlobPaths
     * @throws Exception\InvalidArgumentException
     * @return ListenerOptions Provides fluent interface
     */
    public function set_config_glob_paths($config_glob_paths)
    {
        if (!is_array($config_glob_paths) && !$config_glob_paths instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf('Argument passed to %s::%s() must be an array, ' . 'implement the Traversable interface, or be an ' . 'instance of Laminas\Config\Config. %s given.', self::class, __METHOD__, gettype($config_glob_paths)));
        }
        $this->config_glob_paths = $config_glob_paths;
        return $this;
    }
    /**
     * Set the static paths to use for loading additional config files
     *
     * @param array|Traversable $configStaticPaths
     * @throws Exception\InvalidArgumentException
     * @return ListenerOptions Provides fluent interface
     */
    public function set_config_static_paths($config_static_paths)
    {
        if (!is_array($config_static_paths) && !$config_static_paths instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf('Argument passed to %s::%s() must be an array, ' . 'implement the Traversable interface, or be an ' . 'instance of Laminas\Config\Config. %s given.', self::class, __METHOD__, gettype($config_static_paths)));
        }
        $this->config_static_paths = $config_static_paths;
        return $this;
    }
    /**
     * Get any extra config to merge in.
     *
     * @return array|Traversable
     */
    public function get_extra_config()
    {
        return $this->extra_config;
    }
    /**
     * Add some extra config array to the main config. This is mainly useful
     * for unit testing purposes.
     *
     * @param array|Traversable $extraConfig
     * @throws Exception\InvalidArgumentException
     * @return ListenerOptions Provides fluent interface
     */
    public function set_extra_config($extra_config)
    {
        if (!is_array($extra_config) && !$extra_config instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf('Argument passed to %s::%s() must be an array, ' . 'implement the Traversable interface, or be an ' . 'instance of Laminas\Config\Config. %s given.', self::class, __METHOD__, gettype($extra_config)));
        }
        $this->extra_config = $extra_config;
        return $this;
    }
    /**
     * Check if the config cache is enabled
     *
     * @return bool
     */
    public function get_config_cache_enabled()
    {
        return $this->config_cache_enabled;
    }
    /**
     * Set if the config cache should be enabled or not
     *
     * @param  bool $enabled
     * @return ListenerOptions
     */
    public function set_config_cache_enabled($enabled)
    {
        $this->config_cache_enabled = (bool) $enabled;
        return $this;
    }
    /**
     * Get key used to create the cache file name
     *
     * @return string
     */
    public function get_config_cache_key()
    {
        return (string) $this->config_cache_key;
    }
    /**
     * Set key used to create the cache file name
     *
     * @param  string $configCacheKey the value to be set
     * @return ListenerOptions
     */
    public function set_config_cache_key($config_cache_key)
    {
        $this->config_cache_key = $config_cache_key;
        return $this;
    }
    /**
     * Get the path to the config cache
     *
     * Should this be an option, or should the dir option include the
     * filename, or should it simply remain hard-coded? Thoughts?
     *
     * @return string
     */
    public function get_config_cache_file()
    {
        if ($this->get_config_cache_key()) {
            return $this->get_cache_dir() . '/module-config-cache.' . $this->get_config_cache_key() . '.php';
        }
        return $this->get_cache_dir() . '/module-config-cache.php';
    }
    /**
     * Get the path where cache file(s) are stored
     *
     * @return string|null
     */
    public function get_cache_dir()
    {
        return $this->cache_dir;
    }
    /**
     * Set the path where cache files can be stored
     *
     * @param  string|null $cacheDir the value to be set
     * @return ListenerOptions
     */
    public function set_cache_dir($cache_dir)
    {
        $this->cache_dir = $cache_dir ? static::normalize_path($cache_dir) : null;
        return $this;
    }
    /**
     * Check if the module class map cache is enabled
     *
     * @return bool
     */
    public function get_module_map_cache_enabled()
    {
        return $this->module_map_cache_enabled;
    }
    /**
     * Set if the module class map cache should be enabled or not
     *
     * @param  bool $enabled
     * @return ListenerOptions
     */
    public function set_module_map_cache_enabled($enabled)
    {
        $this->module_map_cache_enabled = (bool) $enabled;
        return $this;
    }
    /**
     * Get key used to create the cache file name
     *
     * @return string
     */
    public function get_module_map_cache_key()
    {
        return (string) $this->module_map_cache_key;
    }
    /**
     * Set key used to create the cache file name
     *
     * @param  string $moduleMapCacheKey the value to be set
     * @return ListenerOptions
     */
    public function set_module_map_cache_key($module_map_cache_key)
    {
        $this->module_map_cache_key = $module_map_cache_key;
        return $this;
    }
    /**
     * Get the path to the module class map cache
     *
     * @return string
     */
    public function get_module_map_cache_file()
    {
        if ($this->get_module_map_cache_key()) {
            return $this->get_cache_dir() . '/module-classmap-cache.' . $this->get_module_map_cache_key() . '.php';
        }
        return $this->get_cache_dir() . '/module-classmap-cache.php';
    }
    /**
     * Set whether to check dependencies during module loading or not
     *
     * @return bool
     */
    public function get_check_dependencies()
    {
        return $this->check_dependencies;
    }
    /**
     * Set whether to check dependencies during module loading or not
     *
     * @param  bool $checkDependencies the value to be set
     * @return ListenerOptions
     */
    public function set_check_dependencies($check_dependencies)
    {
        $this->check_dependencies = (bool) $check_dependencies;
        return $this;
    }
    /**
     * Whether or not to use laminas-loader to autoload modules.
     *
     * @return bool
     */
    public function use_laminas_loader()
    {
        return $this->use_laminas_loader;
    }
    /**
     * Set a flag indicating if the module manager should use laminas-loader
     *
     * Setting this option to false will disable ModuleAutoloader, requiring
     * other means of autoloading to be used (e.g., Composer).
     *
     * If disabled, the AutoloaderProvider feature will be disabled as well
     *
     * @param  bool $flag
     * @return ListenerOptions
     */
    public function set_use_laminas_loader($flag)
    {
        $this->use_laminas_loader = (bool) $flag;
        return $this;
    }
    /**
     * Normalize a path for insertion in the stack
     *
     * @param  string $path
     * @return string
     */
    public static function normalize_path($path)
    {
        $path = rtrim($path, '/');
        return rtrim($path, '\\');
    }
    /** @deprecated Use self::useLaminasLoader instead */
    public function use_zend_loader(): bool
    {
        return $this->use_laminas_loader();
    }
    /** @deprecated Use self::setUseLaminasLoader instead */
    public function set_use_zend_loader(bool $flag): Listener_Options
    {
        return $this->set_use_laminas_loader(...func_get_args());
    }
}