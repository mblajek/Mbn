<?php

class FileHelper {
    private static $relFiles = [
       'mbn.js' => ['desc' => 'Library in JS'],
       'mbn.php' => ['desc' => 'Library in PHP'],
       'mbn.min.js' => ['desc' => 'Minified library in JS'],
       'mbn.min.php' => ['desc' => 'Minified library in PHP'],
       'mbn.d.ts' => ['desc' => 'TypeScript declaration file'],
       'Mbn.php' => ['desc' => 'Mbn class in PHP (with namespace, without MbnErr class)'],
       'MbnErr.php' => ['desc' => 'MbnErr class in PHP (with namespace)'],
       'v' => [],
    ];

    private static $contentTypes = [
       '' => 'application/json',
       'json' => 'application/json',
       'js' => 'text/javascript',
       'ts' => 'text/javascript',
       'php' => 'application/php',
       'txt' => 'text/plain',
    ];

    private static function getReleaseFilePath($file) /*:string*/ {
        return '../release/' . (ctype_upper($file[0]) ? '_' : '') . $file;
    }

    private static function getFilePath($file) /*:string*/ {
        return "../$file";
    }

    public static function getFileList() /*:array*/ {
        $files = [];
        foreach (self::$relFiles as $name => $file) {
            if (isset($file['desc'])) {
                $path = self::getReleaseFilePath($name);
                $files[$name] = $file + ['path' => $path, 'size' => (file_exists($path) ? filesize($path) : 0)];
            }
        }
        return $files;
    }

    public static function downloadFileAndDie($url, $show) /*:never*/ {
        $foundFile = null;
        if (isset(self::$relFiles[$url])) {
            $foundFile = $url;
        } else {
            $urlFirstLower = strtolower(substr($url, 0, 1)) . substr($url, 1);
            if (isset(self::$relFiles[$urlFirstLower])) {
                $foundFile = $urlFirstLower;
            }
        }
        if ($foundFile && file_exists($foundFilePath = self::getReleaseFilePath($foundFile))) {
            header('Content-Type: ' . static::$contentTypes[$show ? 'txt' : pathinfo($url, PATHINFO_EXTENSION)]);
            header('Content-Disposition: ' . ($show ? 'inline' : 'attachment') . '; filename="' . $url . '"');
            readfile($foundFilePath);
        } else {
            header('HTTP/1.1 404 Not Found');
            echo "File not found: $url";
        }
        die;
    }

    public static function getFile($file) /*:?string*/ {
        $path = self::getFilePath($file);
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        return null;
    }

    public static function getCachedHash() /*:?string*/ {
        $vFilePath = self::getReleaseFilePath('v');
        if (file_exists($vFilePath)) {
            return json_decode(file_get_contents($vFilePath))->hash;
        }
        return null;
    }

    public static function getCurrentHash() /*:?string*/ {
        $files = array_map('FileHelper::getFilePath', ['mbn.js', 'mbn.php', 'mbn.d.ts']);
        if (count(array_filter($files, 'file_exists')) === count($files)) {
            return hash('sha256', implode('', array_map('file_get_contents', $files)));
        }
        return null;
    }
}
