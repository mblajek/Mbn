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

    private static function buildPath($scheme, $host) {
        return ($scheme ?: $_SERVER['REQUEST_SCHEME']) . "://"
           . ($host ?: $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI'];
    }

    public static function run($requireFile) {
        $redirect = null;
        if (env::ssl && $_SERVER['REQUEST_SCHEME'] === 'http') {
            $redirect = self::buildPath('https', null);
        }
        if (strpos($_SERVER['HTTP_HOST'], 'www.') === 0) {
            $redirect = self::buildPath(null, substr($_SERVER['HTTP_HOST'], strlen('www.')));
        }
        if ($redirect) {
            header('Location: ' . $redirect);
            die;
        }

        $url = isset($_SERVER['REDIRECT_URL']) ? ltrim($_SERVER['REDIRECT_URL'], '/') : '';
        $query = isset($_SERVER['REDIRECT_QUERY_STRING']) ? trim($_SERVER['REDIRECT_QUERY_STRING'], '/') : '';

        self::runPath($requireFile, $url, $query);
    }
}
/*echo '<pre>';
var_dump(get_defined_vars());
die;*/