<?php

$requireFile = function ($name, $query = null) {
    $requirePath = '../' . (ctype_upper($name[0]) ? 'class/' : '') . (($name === 'Mbn') ? '../mbn' : $name) . '.php';
    if (file_exists($requirePath)) {
        unset ($name);
        require_once $requirePath;
        return true;
    }
    header('HTTP/1.1 500 Internal Server Error');
    echo "Class not found: $name";
    die;
};

header('Cache-Control: no-store, max-age=0');

spl_autoload_register($requireFile);
Router::run($requireFile);
