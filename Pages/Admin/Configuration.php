<?php

namespace lightningsdk\core\Pages\Admin;

use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Configuration as Config;
use lightningsdk\core\Tools\Template;
use lightningsdk\core\View\Page;

class Configuration extends Page {

    public function get() {
        ClientUser::requireAdmin();
        $template = Template::getInstance();

        $config_files = Config::getConfigurations();
        $config_data = [];
        foreach ($config_files as $source => $file) {
            $config_data[$source] = Config::getConfigurationData($file);
        }

        foreach ($config_data as $source => &$config) {
            array_walk_recursive(
                $config,
                function(&$val) use ($source) {
                    if (!is_array($val)) {
                        $val = [
                            '#source' => [$source],
                            '#value' => [$val]
                        ];
                    }
                }
            );
        }

        $config_data = call_user_func_array('array_merge_recursive', $config_data);

        $output = '<ul>' . $this->processSettingsForm($config_data) . '</ul>';

        $template->set('rendered_content', $output);
    }

    protected function processSettingsForm($config, $key = 'Config', $path = []) {
        ClientUser::requireAdmin();
        $output = '<li><input name="" value="' . $key . '" /></li>';

        foreach ($config as $subkey => $value) {
            $subpath = $path;
            $subpath[] = $subkey;
            $output .= '<ul>';
            if (isset($value['#value'])) {
                if (count($value['#value']) > 1) {
                    $output .= '<li><input name="" value="' . $subkey . '" /></li><ul>';
                    foreach ($value['#value'] as $i => $val) {
                        $disabled = $value['#source'][$i] == 'internal' ? 'disabled="true"' : '';
                        $output .= '<li><input name="' . $value['#source'][$i] . '.' . implode('.', $subpath) . '" value="' . $val . '" ' . $disabled . ' /> (' . $value['#source'][$i] . ')</li>';
                    }
                    $output .= '</ul>';
                } else {
                    $disabled = $value['#source'][0] == 'internal' ? 'disabled="true"' : '';
                    $output .= '<li><input name="' . $value['#source'][0] . '.' . implode('.', $subpath) . '" value="' . $subkey . '" ' . $disabled . ' /> : <input name="' . $value['#source'][0] . '.' . implode('.', $subpath) . '" value="' . $value['#value'][0] . '" ' . $disabled . ' /></li>';
                }
            } else {
                $output .= $this->processSettingsForm($value, $subkey, $subpath);
            }
            $output .= '</ul>';
        }

        return $output;
    }
}
