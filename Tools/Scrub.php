<?php

namespace Lightning\Tools;

class Scrub{

    const SCRUB_BASIC_HTML = 'p,b,a[href|name|target|title],i,strong,em,img[src|width|height],table[cellpadding|cellspacing|border],tr,td,tbody,hr,h1,h2,h3,h4,h5,h6,*[id|name|align|style|alt|class],sup,sub,ul,ol,li,span,font[color|size],div,br,blockquote';
    const SCRUB_ADVANCED_HTML = 'input[type|value|checked|src],select,option[value],form[target|action|method],textarea,iframe[frameborder|src|height|width|align|allowfullscreen]';
    const SCRUB_BASIC_CSS = 'height,width,color,background-color,vertical-align,text-align,margin,margin-left,margin-right,margin-top,margin-bottom,padding,padding-left,margin-right,margin-top,margin-bottom,border,border-left,border-right,border-top,border-bottom,float,font-size';

    public static function un_magic_quotify($process_arr=NULL) {
        if($process_arr == NULL)
            $process_arr = array(&$_POST);
        if (get_magic_quotes_gpc()) {
            //$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
            while (list($key, $val) = each($process_arr)) {
                foreach ($val as $k => $v) {
                    unset($process_arr[$key][$k]);
                    if (is_array($v)) {
                        $process_arr[$key][stripslashes($k)] = $v;
                        $process_arr[] = &$process_arr[$key][stripslashes($k)];
                    } else {
                        $process_arr[$key][stripslashes($k)] = stripslashes($v);
                    }
                }
            }
            unset($process_arr);
        }
    }

    public static function toHTML($code){
        return htmlspecialchars($code, ENT_QUOTES);
    }

    public static function url($value){
        $url = preg_replace("/(&[#a-z0-9]+;)/i", "_", $value);
        $url = preg_replace("/[^a-z0-9]/i", "_", $url);
        return $url;
    }

    public static function email($email){
        $email = str_replace(" ", '', strtolower($email));
        if(!preg_match('/^[_a-zA-Z0-9-]+([_\.\-+][a-zA-Z0-9]+)*@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\-\.]+$/',$email))
            return false;
        else
            return $email;
    }

    public static function variable($var){
        preg_match("/[a-z0-9_]+/i",$var,$r);
        return $r[0];
    }

    public static function password($pass){
        if(preg_match("/ /",$pass))
            return false;
        else
            return $pass;
    }

    public static function hex($code){
        $code = str_replace(" ", '', $code);
        if(!preg_match("/^[a-z0-9\.]+/i",$code))
            return false;
        else
            return $code;
    }

    public static function string($string){
        global $db;
        return $db->escape($string);
    }

    public static function name($name){
        return addslashes(preg_replace('/[^a-z0-9\' ]/i','',$name));
    }

    public static function text($text){
        $purifier = HTMLPurifierWrapper::getInstance();
        $config = HTMLPurifierConfig::createDefault();

        $config->set('HTML.Allowed', '');
        $config->set('CSS.AllowedProperties','');
        $config->set('Core.EscapeNonASCIICharacters',true);

        return $purifier->purify($text, $config);
    }

    public static function html($html, $allowed_tags="", $allowed_css="", $trusted=false){
        $purifier = HTMLPurifierWrapper::getInstance();
        $config = HTMLPurifierConfig::createDefault();

        if (empty($allowed_tags) || $allowed_tags[0] == '.') {
            $allowed_tags = self::SCRUB_BASIC_HTML . ',' . substr($allowed_tags, 1);
        }
        elseif ($allowed_tags == '') {
            if($trusted) {
                $allowed_tags = self::SCRUB_BASIC_HTML . ',' . self::SCRUB_ADVANCED_HTML;
            } else {
                $allowed_tags = self::SCRUB_BASIC_HTML;
            }
        }

        if (empty($allowed_css) || $allowed_css[0] == '.') {
            $allowed_css = self::SCRUB_BASIC_CSS . ',' . substr($allowed_css, 1);
        }
        elseif ($allowed_css == '') {
            $allowed_css = self::SCRUB_BASIC_CSS;
        }

        if ($trusted) {
            $config->set('CSS.Trusted', true);
            $config->set('HTML.Trusted', true);
            $config->set('Attr.EnableID', true);
        } else {
            $config->set('CSS.Trusted', false);
            $config->set('HTML.Trusted', false);
            $config->set('Attr.EnableID', false);
        }

        $config->set('HTML.Allowed', $allowed_tags);
        $config->set('CSS.AllowedProperties', $allowed_css);
        $config->set('Core.EscapeNonASCIICharacters', true);

        return $purifier->purify( $html, $config );
    }

    public static function bool($val){
        if(intval($val) > 0 || $val === true)
            return true;
        else
            return false;
    }
}
