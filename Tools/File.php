<?php

namespace lightningsdk\core\Tools;

class File {
    public static function recursivePath($base_path, $file_id,$extension) {
        $directory = str_pad($file_id,16,"0", STR_PAD_LEFT);
        $file = substr($directory,12,4);
        $directory = $base_path.substr($directory,0,4)."/".substr($directory,4,4)."/".substr($directory,8,4);
        if (!is_dir($directory))
            mkdir($directory, 0777, true);
        return $directory."/".$file.".".$extension;
    }

    public static function absolute($path) {
        if (!preg_match('|^/|', $path) && !preg_match('|^php://|', $path)) {
            return realpath(HOME_PATH . '/' . $path);
        }
        return $path;
    }
}
