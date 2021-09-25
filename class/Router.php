<?php

class Router {
    private static $pages = [
       'calc' => [],
       '' => ['path' => 'lib'],
       'LICENSE' => ['redirect' => 'https://github.com/mblajek/Mbn/blob/master/LICENSE.txt'],
       'mbn_test' => ['path' => 'mbn_test.js']
    ];

    private static function runPath($requireFile, $url, $query) /*:void*/ {
        $page = isset(self::$pages[$url]) ? self::$pages[$url] : ['getFile' => true];
        if (!empty($page['redirect'])) {
            header('Location: ' . $page['redirect']);
            die;
        }
        if (!empty($page['getFile'])) {
            FileHelper::downloadFileAndDie($url, $query === 'show');
        }
        $requireFile(!empty($page['path']) ? $page['path'] : $url, $query);
    }

    public static function run($requireFile) {
        $path = $_SERVER['REQUEST_SCHEME'] . '://' .

           $url = isset($_SERVER['REDIRECT_URL']) ? ltrim($_SERVER['REDIRECT_URL'], '/') : '';
        $query = isset($_SERVER['REDIRECT_QUERY_STRING']) ? trim($_SERVER['REDIRECT_QUERY_STRING'], '/') : '';
/*

        $redirect = false;
        if (preg_match('/www\\.(.+)/', $_SERVER['HTTP_HOST'], $match)) {
            //header('Location: ');
            echo $match[1];
            var_dump(env::ssl);
        }*/


        self::runPath($requireFile, $url, $query);
    }
}
/*echo '<pre>';
var_dump(get_defined_vars());
die;*/