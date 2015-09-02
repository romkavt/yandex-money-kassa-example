<?php

namespace shop;

class Log {

    private $settings;

    function __construct($settings) {
        $this->settings = $settings;
    }

    public function info($str) {
        if(is_array($str) || is_object($str)) {
            $str = print_r($str,true);
        }
        $str = $str . "\n";
        file_put_contents($this->settings->LOG_FILE, '[' . date("Y-m-d H:i:s") . '] ' . $str, FILE_APPEND);
    }
}