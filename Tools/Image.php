<?php

namespace Lightning\Tools;

class Image {
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
}