<?php
/**
 * @file
 * lightningsdk\core\Tools\Configuration
 */

namespace lightningsdk\core\Tools;

use lightningsdk\core\Tools\Cache\Cache;

/**
 * A helper to load variables from the configuration.
 *
 * @package lightningsdk\core\Tools
 */
class ConfigurationCore {
    /**
     * The cached configuration data.
     *
     * @var array
     */
    protected static $configuration = [];

    /**
     * Flag to tell if the configuration is loading. The configuration is considered loading if loadConfiguration() has
     * been called but not complete.
     *
     * @var boolean
     */
    protected static $loading = false;

    /**
     * @var boolean
     */
    protected static $loaded = false;

    public static function bootstrap($bootstrapConfig) {
        static::$configuration = $bootstrapConfig;
        self::loadConfiguration();
    }

    /**
     * Get a config variable's value.
     *
     * @param string $variable
     *   The path to the variable within the config.
     *
     * @return mixed
     *   The value of the variable.
     */
    public static function get($variable, $default = null) {
        return Data::getFromPath($variable, static::$configuration, $default);
    }

    /**
     * Set a configuration variable's value.
     *
     * @deprecated
     *
     * @param string $variable
     *   The name of the variable.
     *
     * @param mixed $value
     *   The new value.
     */
    public static function set($variable, $value) {
        if (empty(static::$configuration)) {
            static::loadConfiguration();
        }

        Data::setInPath($variable, $value, static::$configuration);
    }

    public static function unset($variable) {
        Data::removeFromPath($variable, static::$configuration);
    }

    /**
     * Add a new value to an array.
     */
    public static function push($path, $value) {
        if (empty(static::$configuration)) {
            static::loadConfiguration();
        }

        Data::pushInPath($path, $value, static::$configuration);
    }

    /**
     * Merge new data into the configuration.
     *
     * @param array $new_data
     *   An array of new data to merge.
     */
    public static function merge($new_data) {
        static::$configuration = array_replace_recursive(static::$configuration, $new_data);
    }

    /**
     * Merge new data into the configuration without replacing existing values.
     *
     * @param array $new_data
     *   An array of new data to merge.
     */
    public static function softMerge($new_data) {
        static::$configuration = array_replace_recursive($new_data, static::$configuration);
    }

    /**
     * Override the entire configuration with a new one.
     *
     * @param array $new_configuration
     *   An array with the new configuration.
     */
    public static function override($new_configuration) {
        static::$configuration = $new_configuration;
    }

    public static function isLoaded() {
        return static::$loaded;
    }

    /**
     * Load the configuration from the configuration.inc.php file.
     */
    protected static function loadConfiguration() {
        if (!static::$loading) {
            // Flag the state as loading, so that nested calls to load configs
            // do not get stuck in an infinite loop.
            static::$loading = true;

            // Load site configuration file
            static::softMerge(static::getConfigurationData(CONFIG_PATH . '/config.inc.php'));

            // At this point we have enough info to load from cache
            if (!static::loadCachedConfig()) {
                static::generateConfiguration();
                static::writeCachedConfiguration();
            }

            Logger::debugf('Loaded configuration: %s', json_encode(static::$configuration));

            static::$loaded = true;
            static::$loading = false;
        }
    }

    protected static function loadCachedConfig() {
        if (!Configuration::get('debug')) {
            $cache = Cache::get(Cache::PHP_FILE);
            if ($cached_config = $cache->get('config')) {
                // override the entire config
                Configuration::override($cached_config);
                return true;
            }
        }
        return false;
    }

    protected static function writeCachedConfiguration() {
        if (!Configuration::get('debug')) {
            // Not debug mode, save the cache.
            $cache = Cache::get(Cache::PHP_FILE);
            $cache->set('config', static::$configuration);
        }
    }

    protected static function generateConfiguration() {
        if (!empty(static::$configuration['modules']['include'])) {
            static::softMerge(Modules::load(static::$configuration['modules']['include']));
        }
        // Load module configurations.
        if (Request::isCLI()) {
            if (!empty(static::$configuration['modules']['include-cli'])) {
                static::softMerge(Modules::load(static::$configuration['modules']['include-cli']));
            }
        }
    }

    public static function reload() {
        static::$configuration = [];
        static::loadConfiguration();
    }

    /**
     * Get the full configuration.
     *
     * @return array
     *   The full configuration.
     */
    public static function getConfiguration() {
        return static::$configuration;
    }

    /**
     * Load a configuration form a file.
     *
     * @param string $file
     *   The absolute file path.
     *
     * @return array
     *   The config data.
     */
    public static function getConfigurationData($file) {
        if (file_exists($file)) {
            include $file;
            return $conf;
        } else {
            throw new \Exception('Missing configuration file: ' . $file);
        }
    }
}
