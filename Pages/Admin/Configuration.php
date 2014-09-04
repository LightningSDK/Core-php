<?php

namespace Lightning\Pages\Admin;

use Lightning\Tools\Configuration as Config;
use Lightning\Tools\Template;
use Lightning\View\Page;

class Configuration extends Page {
    public function get() {
        $template = Template::getInstance();

        $config_files = Config::getConfigurations();
        $config_data = array();
        foreach ($config_files as $source => $file) {
            $config_data[$source] = Config::getConfigurationData($file);
        }
//print_r($config_data);
        foreach ($config_data as $source => &$config) {
            array_walk_recursive(
                $config,
                function(&$val) use ($source) {
                    if (!is_array($val)) {
                        $val = array(
                            '#source' => array($source),
                            '#value' => array($val)
                        );
                    }
                }
            );
        }
//        print_r($config_data);

        $config_data = call_user_func_array('array_merge_recursive', $config_data);
//        print_r($config_data);

        $output = '<ul>' . $this->processSettingsForm($config_data) . '</ul>';


        $template->set('rendered_content', $output);
    }

    protected function processSettingsForm($config, $key = 'Config', $path = array()) {
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
