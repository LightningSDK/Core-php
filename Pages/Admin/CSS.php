<?php

namespace Lightning\Pages\Admin;

use Lightning\Tools\ClientUser;
use Lightning\Tools\Configuration;
use Lightning\Tools\Output;
use Lightning\Tools\Request;
use Lightning\Tools\Template;
use Lightning\View\JS;
use Source\View\Page;

class CSS extends Page {

    protected $page = ['admin/css', 'Lightning'];
    protected $template = ['template_blank', 'Lightning'];

    public function hasAccess() {
        return Configuration::get('css.editable') && ClientUser::requireAdmin();
    }

    /**
     * Load the page.
     */
    public function get() {
        $input_file = Configuration::get('css.input');
        $template = Template::getInstance();
        if (HOME_PATH . '/' . $input_file) {
            $template->set('scss', file_get_contents(HOME_PATH . '/' . $input_file));
        } else {
            $template->set('scss', '');
        }
        JS::addSessionToken();
        JS::startup('lightning.admin.css.init();');
    }

    /**
     * Save modified SCSS for retrieving a preview version.
     */
    public function postPreview() {
        Output::setJson(true);

        // Save the new input to a temp file.
        $id = Request::get('id', Request::TYPE_INT);
        $input_temp = Configuration::get('css.input') . '.' . $id . '.tmp';
        $posted_scss = Request::post('scss', Request::TYPE_STRING);
        file_put_contents(HOME_PATH . '/' . $input_temp, $posted_scss);

        Output::json(Output::SUCCESS);
    }

    /**
     * Get a preview version of the CSS.
     *
     * @throws \Exception
     *   If the version of the file requested could not be found.
     */
    public function getPreview() {
        // Load temp file
        $id = Request::get('id', Request::TYPE_INT);
        $input_temp = Configuration::get('css.input') . '.' . $id . '.tmp';
        $input_temp = HOME_PATH . '/' . $input_temp;
        if (!file_exists($input_temp)) {
            throw new \Exception('Invalid File ID');
        }

        $css_contents = $this->generateCSS($input_temp);
        Output::setContentType('text/css');
        echo $css_contents;
        exit;
    }

    /**
     * Save the SCSS, optionally publish it.
     */
    public function postSave() {
        Output::setJson(true);

        $settings = Configuration::get('css');

        // Save the new input to a the actual file.
        $posted_scss = Request::post('scss', Request::TYPE_STRING);
        $scss_file = HOME_PATH . '/' . $settings['input'];
        file_put_contents($scss_file, $posted_scss);

        if (!empty($settings['publish'])) {
            // Generate the output css files.
            $css_contents = $this->generateCSS($scss_file);
            $css_file = HOME_PATH . '/' .  $settings['output'];
            file_put_contents($css_file, $css_contents);

            // gzip if required.
            if (!empty($settings['gzip'])) {
                file_put_contents($css_file . '.gz', gzencode($css_contents, 9));
            }
        }

        Output::json(['url' => '/' .  $settings['output']]);
    }

    /**
     * Build the compressed CSS file.
     *
     * @param string $source_file
     *   The absolute location of the scss file.
     *
     * @return string
     *   The condensed CSS contents
     */
    protected function generateCSS($source_file) {
        $sass_command = 'scss -t compressed --compass';
        foreach (Configuration::get('css.include_paths', []) as $import) {
            $sass_command .= ' -I ' . escapeshellarg($import);
        }

        $sass_command .= ' ' . escapeshellarg($source_file);

        exec('cd ' . escapeshellarg(HOME_PATH . '/Source/Resources') . ' && ' . $sass_command, $output, $return);
        return implode(' ', $output);
    }
}
