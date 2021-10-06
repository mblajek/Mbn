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

    private static function buildPath($protocol, $host) {
        return "$protocol://" . ($host ?: $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI'];
    }

    public static function run($requireFile) {
        $protocol = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] :
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : null);

        if (!$protocol) {
            header('HTTP/1.1 500 Internal Server Error');
            echo "No protocol header";
        }

        $redirect = null;
        if (env::ssl && $protocol === 'http') {
            $redirect = self::buildPath('https', null);
        }
        if (strpos($_SERVER['HTTP_HOST'], 'www.') === 0) {
            $redirect = self::buildPath($protocol, substr($_SERVER['HTTP_HOST'], 4));
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
