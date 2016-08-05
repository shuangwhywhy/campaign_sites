<?php

spl_autoload_register('AutoLoader');

function AutoLoader ($className) {
    $fileName = str_replace('\\', DS, $className).'.php';
    $file = ROOT_DIR.DS.$fileName;
    if (!file_exists($file)) {
        $file = ROOT_DIR.DS.'libraries'.DS.$fileName;
    }
    if (file_exists($file)) {
        include_once $file;
    } else {
        throw New \Exception('Class could not be loaded ['.$className.'], file location: ['.$file.']');
    }
}
