<?php

namespace lightningsdk\core\Tools;

class Image {

    const FORMAT_JPG = 'JPG';
    const FORMAT_PNG = 'PNG';
    const FORMAT_EPS = 'EPS';

    /**
     * The source image contents.
     *
     * @var resource
     *
     * TODO: Table class needs to be updated for this to be protected.
     */
    public $source;

    /**
     * The processed image data.
     *
     * @var string
     *
     * TODO: Same as above
     */
    public $processed;

    /**
     * Wrap text for image drawing.
     *
     * @param $fontSize
     * @param $angle
     * @param $fontFace
     * @param $string
     * @param $width
     *
     * @return string
     */
    public static function wrapText($fontSize, $angle, $fontFace, $string, $width){
        $ret = '';
        $arr = explode(' ', $string);
        foreach ( $arr as $word ){
            $teststring = $ret.' '.$word;
            $testbox = imagettfbbox($fontSize, $angle, $fontFace, $teststring);
            if ( $testbox[2] > $width ){
                $ret .= ($ret == '' ? '' : "\n") . $word;
            } else {
                $ret .= ($ret == '' ? '' : ' ') . $word;
            }
        }
        return $ret;
    }

    /**
     * Creates an image object from a mime encoded web form.
     * @param $name
     * @return bool|Image
     */
    public static function loadFromPost($name) {
        $file = $_FILES[$name]['tmp_name'];
        if (!file_exists($file) || !is_uploaded_file($file)) {
            return false;
        }

        $image = new self();

        $image->source = imagecreatefromstring(file_get_contents($file));
        return $image;
    }

    /**
     * @param $string
     * @return Image
     */
    public static function createFromString($string) {
        $image = new self();
        $image->source = imagecreatefromstring($string);
        return $image;
    }

    /**
     * Creates an image object from a posted base64, form encoded image.
     *
     * @param $field
     * @return bool|Image
     */
    public static function loadFromPostField($field) {
        $image = new self();

        $image->source = imagecreatefromstring(base64_decode(Request::post($field, 'base64')));
        return $image->source ? $image : false;
    }

    /**
     * Process an image for scale and cropping.
     *
     * @param array $settings
     *   The image transformation settings.
     *   - max_size int - the maximum size in either axis
     *   - max_width int - the maximum width
     *   - max_height int - the maximum height
     *   - height int - an absolute height
     *   - width int - an absolute width
     *   - crop array|string - @todo: needs documentation
     *   - alpha boolean - whether to set an alpha if the image does not fill the new size
     *   - background array - [r, g, b] values for a background color if not using alpha
     *
     * @return boolean
     *   Whether the image size was changed.
     */
    public function process($settings) {
        // Initialized some parameters.
        // The coordinates of the top left in the dest image where the src image will start.
        $dest_x = 0;
        $dest_y = 0;
        // The coordinates of the source image where the copy will start.
        $src_x = 0;
        $src_y = 0;
        // Src frame = The original image width/height
        // Dest frame = The destination image width/height
        // Dest w/h = The destination scaled image content size
        // Src w/h = The source image copy size
        $src_frame_w = $dest_frame_w = $dest_w = $src_w = imagesx($this->source);
        $src_frame_h = $dest_frame_h = $dest_h = $src_h = imagesy($this->source);

        if (!empty($settings['max_size']) && empty($settings['max_height'])) {
            $settings['max_height'] = $settings['max_size'];
        }
        if (!empty($settings['max_size']) && empty($settings['max_width'])) {
            $settings['max_width'] = $settings['max_size'];
        }

        // Set max sizes
        if (!empty($settings['max_width']) && $dest_frame_w > $settings['max_width']) {
            $dest_frame_w = $dest_w = $settings['max_width'];
            // Scale down the height.
            $dest_frame_h = $dest_h = ($dest_w * $src_h/$src_w);
        }
        if (!empty($settings['max_height']) && $dest_frame_w > $settings['max_height']) {
            $dest_frame_h = $dest_h = $settings['max_height'];
            // Scale down the width.
            $dest_frame_w = $dest_w = ($dest_h * $src_w/$src_h);
        }

        // Set absolute width/height
        if (!empty($settings['width'])) {
            $dest_frame_w = $dest_w = $settings['width'];
        }
        if (!empty($settings['height'])) {
            $dest_frame_h = $dest_h = $settings['height'];
        }

        // If the image can be cropped.
        if (!empty($settings['crop'])) {
            if (is_string($settings['crop'])) {
                switch ($settings['crop']) {
                    case 'left':
                    case 'right':
                        $settings['crop'] = ['x' => $settings['crop']];
                        break;
                    case 'x':
                        $settings['crop'] = ['x' => true];
                        break;
                    case 'bottom':
                    case 'top':
                        $settings['crop'] = ['y' => $settings['crop']];
                        break;
                    case 'y':
                        $settings['crop'] = ['y' => true];
                        break;
                }
            }
            if (!empty($settings['crop']['x']) && $settings['crop']['x'] === true) {
                $scale = $dest_frame_h / $src_frame_h;
                // Get the width of the destination image if it were scaled.
                $dest_w = $scale * $src_frame_w;
                if ($dest_w > $dest_frame_w) {
                    $dest_crop = $dest_w - $dest_frame_w;
                    $dest_w = $dest_frame_w;
                    $src_x = $dest_crop / $scale / 2;
                    $src_w = $src_frame_w - ($src_x * 2);
                } else {
                    $dest_border = $dest_frame_w - $dest_w;
                    $dest_x = $dest_border / 2;
                }
            }
            if (!empty($settings['crop']['y'])) {
                // TODO: This can be simplified.
                $scale = $src_frame_w / $src_frame_h;
                // Get the height of the destination image if it were scaled.
                $dest_h = ( int ) ($dest_frame_w / $scale);
                if ($settings['crop']['y'] == 'bottom') {
                    if ($dest_h < $dest_frame_h) {
                        $dest_border = $dest_frame_h - $dest_h;
                        $dest_y = $dest_border / 2;
                    }
                } elseif ($settings['crop']['y'] === true) {
                    if ($dest_h > $dest_frame_h) {
                        $dest_crop = $dest_h - $dest_frame_h;
                        $dest_h = $dest_frame_h;
                        $src_y = $dest_crop / $scale / 2;
                        $src_h = $src_frame_h - ($src_y * 2);
                    } else {
                        $dest_border = $dest_frame_h - $dest_h;
                        $dest_y = $dest_border / 2;
                    }
                }
            }
        }

        $this->processed = imagecreatetruecolor($dest_frame_w, $dest_frame_h);
        if (!empty($settings['alpha'])) {
            $color = imagecolorallocatealpha($this->processed, 0, 0, 0, 127);
            imagefill($this->processed, 0, 0, $color);
            imagealphablending($this->processed, false);
            imagesavealpha($this->processed, true);
        }
        elseif (!empty($settings['background'])) {
            $color = imagecolorallocate($this->processed, $settings['background'][0], $settings['background'][1], $settings['background'][2]);
            imagefill($this->processed, 0, 0, $color);
        }

        imagecopyresampled(
            $this->processed, $this->source,
            $dest_x, $dest_y, $src_x, $src_y,
            $dest_w, $dest_h, $src_w, $src_h
        );

        // Return whether the dimensions have changed.
        return $dest_x != $src_x || $dest_y != $src_y || $dest_w != $src_w || $dest_h != $src_h;
    }

    /**
     * Get the image reference to output. This will be the the processed image
     * if there is one, otherwise it will be the original image.
     *
     * @return resource
     */
    protected function &getOutputImage() {
        if (!empty($this->processed)) {
            $ref =& $this->processed;
        } else {
            $ref =& $this->source;
        }
        return $ref;
    }

    /**
     * Get the data as a PNG binary string.
     *
     * @return string
     *   binary string.
     */
    public function getPNGData() {
        ob_start();
        imagepng($this->getOutputImage());
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    /**
     * Get the data as a JPG binary string.
     *
     * @param integer $quality
     *   The image compression quality. (0 to 100)
     *
     * @return string
     *   binary string.
     */
    public function getJPGData($quality = 80) {
        ob_start();
        imagejpeg($this->getOutputImage(), null, $quality);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    /**
     * @param $file
     * @param array $format
     */
    public function save($file, $format = ['format' => self::FORMAT_JPG, 'quality' => 80]) {
        if (is_array($format)) {
            $quality = $format['quality'];
            $format = $format['format'];
        }

        switch ($format) {
            case self::FORMAT_JPG:
                file_put_contents($file, $this->getJPGData($quality));
                break;
            case self::FORMAT_PNG:
                file_put_contents($file, $this->getPNGData());
                break;
        }
    }

    public static function setHeader($format) {
        switch ($format) {
            case self::FORMAT_PNG:
                Output::setContentType('image/png');
                break;
            case self::FORMAT_EPS:
                Output::setContentType('application/eps');
                break;
            case self::FORMAT_JPG:
                Output::setContentType('image/jpeg');
                break;
        }
    }
}
