<?php
/**
 * @file
 * Lightning\Tools\Configuration
 */

namespace Lightning\Tools;

/**
 * A helper to load variables from the configuration.
 *
 * @package Lightning\Tools
 */
class Configuration {
    /**
     * The cached configuration data.
     *
     * @var array
     */
    protected static $configuration = array();

    /**
     * Get a config variable's value.
     *
     * @param string $variable
     *   The path to the variable within the config.
     *
     * @return mixed
     *   The value of the variable.
     */
    public static function get($variable) {
        if (empty(self::$configuration)) {
            self::loadConfiguration();
        }

        $path = explode('.', $variable);
        return self::getSub($path, self::$configuration);
    }

    /**
     * Get a child element of a variable's value.
     *
     * @param array $path
     *   The path to the value.
     * @param array $content
     *   The value of the current variable.
     *
     * @return mixed
     *   The value of the variable.
     */
    protected static function getSub($path, $content) {
        $next = array_shift($path);
        if (!empty($content[$next])) {
            $content = $content[$next];
        } else {
            return null;
        }

        if (!empty($path)) {
            $content = self::getSub($path, $content);
        }

        return $content;
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
        self::$configuration[$variable] = $value;
    }

    /**
     * Load the configuration from the configuration.inc.php file.
     */
    protected static function loadConfiguration() {
        if (empty(self::$configuration)) {
            foreach (self::getConfigurations() as $config_file)
            if (file_exists($config_file)) {
                self::$configuration = array_replace_recursive(
                    self::getConfigurationData($config_file),
                    self::$configuration
                );
            } else {
                echo "not found $config_file";
            }
        }
    }

    /**
     * Get a list of configuration files.
     *
     * @return array
     *   A list of files.
     */
    public static function getConfigurations() {
        return array(
            'source' => CONFIG_PATH . '/config.inc.php',
            'internal' => HOME_PATH . '/Lightning/Config.php'
        );
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
