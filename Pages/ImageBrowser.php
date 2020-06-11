<?php

namespace lightningsdk\core\Pages;

use lightningsdk\core\Model\Permissions;
use lightningsdk\core\Tools\Configuration;
use lightningsdk\core\Tools\Image;
use lightningsdk\core\Tools\Output;
use lightningsdk\core\View\JS;
use lightningsdk\core\View\Page;
use lightningsdk\core\Tools\ClientUser;
use lightningsdk\core\Tools\Request;

class ImageBrowser extends Page {

    protected $template = ['template_blank', 'lightningsdk/core'];
    protected $page = 'image_browser';
    protected $containers;
    protected $thumbSize;
    protected $thumbQuality = 80;

    public function __construct() {
        parent::__construct();
        $this->thumbSize = Configuration::get('imageBrowser.thumbSize', 150);
        $this->containers = Configuration::get('imageBrowser.containers', []);

        if (Request::get('action') == 'upload') {
            $this->ignoreToken = true;
        }
    }

    public function hasAccess() {
        ClientUser::requirePermission(Permissions::ALL);
        return true;
    }

    public function get() {
        // Render the main display window.
        // this is actually the name of the container and will be suffixed by the path.
        $container = Request::get('container');
        JS::set('imageBrowser.container', $container);
        JS::set('imageBrowser.url', $this->containers[$container]['url']);
        JS::startup('lightning.imageBrowser.init()');
    }

    public function getFolders() {
        Output::setJson(true);
        // Load the container and path.
        $path = explode(':', Request::get('path'), 2);
        if (empty($this->containers[$path[0]])) {
            Output::error('Invalid Container');
        }
        if (empty($path[1])) {
            $path[1] = '';
        }
        $absolute_path = HOME_PATH . '/' . $this->containers[$path[0]]['storage'] . $path[1];
        $folders = array_filter(glob($absolute_path . '/*'), 'is_dir');
        foreach ($folders as &$f) {
            // Format each path for export.
            $f = str_replace($absolute_path . '/', '', $f);
        }
        Output::json([
            'path' => $path[1],
            'folders' => $folders,
        ]);
    }

    public function getImages() {
        Output::setJson(true);
        // Return a list of images in this path.
        $path = explode(':', Request::get('path'), 2);
        if (empty($this->containers[$path[0]])) {
            Output::error('Invalid Container');
        }
        if (empty($path[1])) {
            $path[1] = '';
        }
        $absolute_path = HOME_PATH . '/' . $this->containers[$path[0]]['storage'] . '/' . $path[1];
        $images = array_filter(glob($absolute_path . '/*'), 'is_file');
        asort($images);
        $output_images = [];
        foreach ($images as $key => $filename) {
            // Make sure a thumbnail exists
            $this->ensureThumbnail($filename);

            // Format each path for export
            $image_data = exif_read_data($filename);
            $output_images[] = [
                'filename' => str_replace($absolute_path . '/', '', $filename),
                'width' => $image_data['COMPUTED']['Width'],
                'height' => $image_data['COMPUTED']['Height'],
                'filesize' => filesize($filename),
            ];
        }
        Output::json([
            'path' => $path[1],
            'web_path' => $this->containers[$path[0]]['url'],
            'images' => $output_images,
        ]);
    }

    public function postResizeImage() {

    }

    public function getResizePreview() {

    }

    public function ensureThumbnail($url) {
        $url_parts = explode('/', $url);
        $file_name = array_pop($url_parts);
        $prefix = implode('/', $url_parts);
        if (!is_dir($prefix . '/.thumbs')) {
            mkdir($prefix . '/.thumbs');
        }
        if (!file_exists($prefix . '/.thumbs/' . $file_name)) {
            $image = Image::createFromString(file_get_contents($url));
            $image->process([
                'max_size' => $this->thumbSize,
                'background' => [255, 255, 255],
            ]);
            $image_data = $image->getJPGData($this->thumbQuality ?: null);
            file_put_contents($prefix . '/.thumbs/' . $file_name, $image_data);
        }
    }

    public function postUpload() {
        if (isset($_GET['CKEditorFuncNum'])) {
            $ckfinder_compatibility = true;
            $funcNum = Request::get('CKEditorFuncNum', 'int');
        } else {
            Output::setJson(true);
        }

        // Validate the container.
        $container = Request::get('container');
        if (empty($this->containers[$container])) {
            if ($ckfinder_compatibility) {
                // Backwards Compatibility with CKEditor
                echo '<script>alert("Invalid Container")</script>';
                exit;
            } else {
                Output::error('Invalid Container');
            }
        }

        // Get the new location.
        $suffix_num = 1;
        $prefix = HOME_PATH . '/' . $this->containers[$container]['storage'] . '/';
        $filename = $_FILES['upload']['name'];
        $strpos = strrpos($filename, '.');
        if ($strpos === false) {
            $prefix .= $filename;
            $suffix = '';
        } else {
            $prefix .= substr($filename, 0, $strpos);
            $suffix = substr($filename, $strpos);
        }

        if (!file_exists($prefix . $suffix)) {
            $absolute_file = $prefix . $suffix;
        }
        else {
            while (file_exists($prefix . '_' . $suffix_num . $suffix)) {
                $suffix_num ++;
            }
            $absolute_file = $prefix . '_' . $suffix_num . $suffix;
        }

        // Prepare the image.
        move_uploaded_file($_FILES['upload']['tmp_name'], $absolute_file);
        $this->ensureThumbnail($absolute_file);

        $url = str_replace(
            HOME_PATH . '/' . $this->containers[$container]['storage'] . '/',
            $this->containers[$container]['url'],
            $absolute_file);

        if ($ckfinder_compatibility) {
            // Backwards Compatibility with CKEditor
            echo '<script type="text/javascript">window.parent.CKEDITOR.tools.callFunction(' . $funcNum . ',  "' . $url . '", "");</script>';
            exit;
        } else {
            Output::json(['url' => $url]);
        }
    }

    public function getUpload() {
        $x = 1;
    }
}
