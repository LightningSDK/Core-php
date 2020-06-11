<?php

namespace lightningsdk\core\Tools\IO;

class PseudoStream {

    /**
     * @var FileHandlerInterface
     */
    protected $fileHandler;

    /**
     * @var string
     */
    protected $file;

    /**
     * PseudoStream constructor.
     * @param FileHandlerInterface $file_handler
     */
    public function __construct($file_handler, $file) {
        $this->fileHandler = $file_handler;
        $this->file = $file;
    }

    public function outputSegment() {
        $size   = $this->fileHandler->getSize($this->file);
        $length = $size;           // Content length
        $start  = 0;               // Start byte
        $end    = $size - 1;       // End byte
        header("Accept-Ranges: bytes");
        if (isset($_SERVER['HTTP_RANGE'])) {
            $c_end   = $end;
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            if (strpos($range, ',') !== false) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            if ($range == '-') {
                $c_start = $size - substr($range, 1);
            }else{
                $range  = explode('-', $range);
                $c_start = $range[0];
                $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $size;
            }
            $c_end = ($c_end > $end) ? $end : $c_end;
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header("Content-Range: bytes $start-$end/$size");
                exit;
            }
            $start  = $c_start;
            $end    = $c_end;
            $length = $end - $start + 1;
            header('HTTP/1.1 206 Partial Content');
        }
        header("Content-Range: bytes $start-$end/$size");
        header("Content-Length: " . $length);
        $buffer = 1024 * 8;
        $range_start = $start;
        while($content = $this->fileHandler->readRange($this->file, $range_start, $range_start + $buffer)) {
            $range_start += $buffer + 1;
            echo $content;
        }
    }
}
