<?php

class Router {
    private static $pages = [
       'calc' => [],
       '' => ['path' => 'lib'],
       'LICENSE' => ['redirect' => 'https://github.com/mblajek/Mbn/blob/master/LICENSE.txt'],
       'mbn_current' => ['getFile' => '../mbn.js'],
       'mbn_test' => ['path' => 'mbn_test.js'],
       'mbn_release' => ['path' => 'mbn_release', 'type' => 'text/plain'],
       'mbn_update' => ['path' => 'mbn_update', 'type' => 'text/plain'],
    ];

    private static function runPath($requireFile, $url, $query) /*:void*/ {
        $page = isset(self::$pages[$url]) ? self::$pages[$url] : ['getFile' => $url];
        if (!empty($page['redirect'])) {
            header('Location: ' . $page['redirect']);
            die;
        }
        if (!empty($page['getFile'])) {
            FileHelper::downloadFileAndDie($page['getFile'], $query === 'show');
        }
        if (!empty($page['type'])) {
            header('Content-Type: ' . $page['type']);
        }
        $requireFile(!empty($page['path']) ? $page['path'] : $url, $query);
    }

    private static function getProtocol() /*:string*/ {
        $protocol = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] :
           (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : null);
        if (!$protocol) {
            (new SimpleHtml(500))->addErrorDiv('No protocol header')->render();
            die;
        }
        return $protocol;
    }

    private static function buildPath($protocol, $host) /*:void*/ {
        return "$protocol://" . ($host ?: $_SERVER['HTTP_HOST']) . $_SERVER['REQUEST_URI'];
    }

    public static function run($requireFile) /*:void*/ {
        $protocol = self::getProtocol();
        $redirect = null;
        if (strpos($_SERVER['HTTP_HOST'], 'www.') === 0) {
            $redirect = self::buildPath($protocol, substr($_SERVER['HTTP_HOST'], 4));
        }
        if (env::ssl && $protocol === 'http') {
            // www. - access without https
            $redirect = $redirect ? null : self::buildPath('https', null);
        }
        if ($redirect) {
            header('Location: ' . $redirect);
            die;
        }

        $contextLen = isset($_SERVER['CONTEXT_PREFIX']) ? strlen($_SERVER['CONTEXT_PREFIX']) : 0;
        $url = substr(isset($_SERVER['REDIRECT_URL']) ? ltrim($_SERVER['REDIRECT_URL'], '/') : '', $contextLen);
        $query = isset($_SERVER['REDIRECT_QUERY_STRING']) ? trim($_SERVER['REDIRECT_QUERY_STRING'], '/') : '';

        self::runPath($requireFile, $url, $query);
    }
}
