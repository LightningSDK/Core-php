<?php
/**
 * @file
 * lightningsdk\core\Tools\Configuration
 */

namespace lightningsdk\core\Tools;

/**
 * A helper to load variables from the configuration.
 *
 * @package lightningsdk\core\Tools
 */
class Configuration {
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
        if (empty(self::$configuration)) {
            self::loadConfiguration();
        }

        return Data::getFromPath($variable, self::$configuration, $default);
    }

    /**
     * Set a configuration variable's value.
     *
     * @param string $variable
     *   The name of the variable.
     *
     * @param mixed $value
     *   The new value.
     */
    public static function set($variable, $value) {
        if (empty(self::$configuration)) {
            self::loadConfiguration();
        }

        Data::setInPath($variable, $value, self::$configuration);
    }

    /**
     * Add a new value to an array.
     */
    public static function push($path, $value) {
        if (empty(self::$configuration)) {
            self::loadConfiguration();
        }

        Data::pushInPath($path, $value, self::$configuration);
    }

    /**
     * Merge new data into the configuration.
     *
     * @param array $new_data
     *   An array of new data to merge.
     */
    public static function merge($new_data) {
        self::$configuration = array_replace_recursive(self::$configuration, $new_data);
    }

    /**
     * Merge new data into the configuration without replacing existing values.
     *
     * @param array $new_data
     *   An array of new data to merge.
     */
    public static function softMerge($new_data) {
        self::$configuration = array_replace_recursive($new_data, self::$configuration);
    }

    /**
     * Override the entire configuration with a new one.
     *
     * @param array $new_configuration
     *   An array with the new configuration.
     */
    public static function override($new_configuration) {
        self::$configuration = $new_configuration;
    }

    public static function isLoaded() {
        return self::$loaded;
    }

    /**
     * Load the configuration from the configuration.inc.php file.
     */
    protected static function loadConfiguration() {
        if (!self::$loading) {
            // Flag the state as loading, so that nested calls to load configs
            // do not get stuck in an infinite loop.
            self::$loading = true;
            // Load each configuration file.
            foreach (self::getConfigurations() as $config_file) {
                if (file_exists($config_file)) {
                    self::softMerge(self::getConfigurationData($config_file));
                } else {
                    echo "not found $config_file";
                }
            }

            if (!empty(self::$configuration['modules']['include'])) {
                self::loadModules(self::$configuration['modules']['include']);
            }
            // Load module configurations.
            if (Request::isCLI()) {
                if (!empty(self::$configuration['modules']['include-cli'])) {
                    self::loadModules(self::$configuration['modules']['include-cli']);
                }
            }

            self::$loaded = true;
            self::$loading = false;
        }
    }

    public static function loadModules($includeModules) {
        for ($i = 0; $i < count($includeModules); $i++) {
            $module = $includeModules[$i];
            foreach (['Modules', 'vendor'] as $path) {
                if (file_exists(HOME_PATH . '/' . $path . '/' . $module . '/config.php')) {
                    $moduleConfig = require HOME_PATH . '/' . $path . '/' . $module . '/config.php';
                    self::softMerge($moduleConfig);
                    if (!empty($moduleConfig['modules']['include'])) {
                        foreach ($moduleConfig['modules']['include'] as $include) {
                            if (!in_array($include, $includeModules)) {
                                $includeModules[] = $include;
                            }
                        }
                    }
                }
            }
        }
    }

    public static function reload() {
        self::$configuration = [];
        static::loadConfiguration();
    }

    /**
     * Get the full configuration.
     *
     * @return array
     *   The full configuration.
     */
    public static function getConfiguration() {
        return self::$configuration;
    }

    /**
     * Get a list of configuration files.
     *
     * @return array
     *   A list of files.
     */
    public static function getConfigurations() {
        return [
            'source' => CONFIG_PATH . '/config.inc.php',
            'internal' => HOME_PATH . '/vendor/lightningsdk/core/Config.php'
        ];
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
        include $file;
        return $conf;
    }
}
