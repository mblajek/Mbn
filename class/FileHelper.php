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
        return '../release/' . ($file && ctype_upper($file[0]) ? '_' : '') . $file;
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
            (new SimpleHtml(404))->addErrorDiv("File not found: $url")->render();
        }
        die;
    }

    public static function getFile($file, $release = false) /*:?string*/ {
        $path = $release ? self::getReleaseFilePath($file) : self::getFilePath($file);
        if (file_exists($path)) {
            return file_get_contents($path);
        }
        return null;
    }

    public static function deleteFile($file, $release = false) /*:?bool*/ {
        $path = $release ? self::getReleaseFilePath($file) : self::getFilePath($file);
        return (unlink($path) !== false);
    }

    public static function putFile($file, $contents, $release = false, $binary = false) /*:?bool*/ {
        $path = $release ? self::getReleaseFilePath($file) : self::getFilePath($file);
        $addNewline = ($binary || ($contents && $contents[strlen($contents) - 1] === PHP_EOL)) ? '' : PHP_EOL;
        return (file_put_contents($path, $contents . $addNewline) !== false);
    }

    public static function clearRelease() {
        $releaseDirPath = self::getReleaseFilePath('');
        foreach (scandir($releaseDirPath) as $file) {
            if ($file && $file[0] !== '.') {
                unlink($releaseDirPath . $file);
            }
        }
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

    public static function getZipFile($file, $release = false) /*:?ZipArchive*/ {
        $path = $release ? self::getReleaseFilePath($file) : self::getFilePath($file);
        if (file_exists($path)) {
            $zip = new ZipArchive();
            if ($zip->open($path)) {
                return $zip;
            }
        }
        return null;
    }

    public static function getAllFiles($dirPath = '', $depth = 2) {
        $ret = [];
        $dirFullPath = self::getFilePath($dirPath);
        foreach (scandir($dirFullPath) as $file) {
            $filePath = $dirPath ? "$dirPath/$file" : $file;
            if ($file === '.' || $file === '..' || $filePath === 'release' || $filePath === 'env.php'
               || $filePath === '.git' || $filePath === '.idea') {
                continue;
            }
            $fileFullPath = self::getFilePath($filePath);
            if (!is_dir($fileFullPath)) {
                $fileContents = self::getFile($filePath);
                $ret[$filePath] = strlen($fileContents) . '-' . hash('sha256', $fileContents);
            } elseif ($depth) {
                $ret += self::getAllFiles($filePath, $depth - 1);
            }
        }
        return $ret;
    }
}
