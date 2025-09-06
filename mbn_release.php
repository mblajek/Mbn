<?php

function releaseMbn() {
    $newHash = FileHelper::getCurrentHash();

    $mbn_js = FileHelper::getFile('mbn.js');
    $mbn_php = FileHelper::getFile('mbn.php');
    $mbn_d_ts = FileHelper::getFile('mbn.d.ts');
    $filesToSave = [];

    if (FileHelper::getCachedHash() === $newHash) {
        return 'already up-to-date';
    }

    function getVersion($code) {
        preg_match('/MbnV = [\'"]([\d.]+)[\'"];/', $code, $varr);
        return 'v' . (isset($varr[1]) ? $varr[1] : '?');
    }

    function removePhpTag($code, $replace = '') {
        return trim(preg_replace('/^<\?php\s*/i', $replace, $code));
    }

    function arrayGetStr($array, $key) {
        if (array_key_exists($key, $array)) {
            return $array[$key] ?: '';
        }
        return '';
    }

    function checkMinifyJS(&$errors) {
        if (!env::docker) {
            throw new Exception('Release possible only in docker environment');
        }
        system(implode(' ', ['../ext/qjs --std',
            '../ext/uglifym.js',
            '../mbn.js',
            '1>../var/qg_std',
            '2>../var/qg_err']));

        $qgStd = trim(FileHelper::getFile('var/qg_std')) ?: null;
        $qgErr = trim(FileHelper::getFile('var/qg_err')) ?: null;
        FileHelper::deleteFile('var/qg_std');
        FileHelper::deleteFile('var/qg_err');
        if ($qgErr) {
            throw new Exception('Uglify error: ' . $qgErr);
        }
        if (!$qgStd) {
            throw new Exception('Uglify empty response');
        }
        //todo: run tests with qjs
        return $qgStd;
    }

    function minifyCheckPHP(&$errors) {
        $mbn_min_php = php_strip_whitespace('mbn.php');
        $mbn_min_php_out = '';
        $len = strlen($mbn_min_php);
        $stString = false;
        $stBack = false;
        $stSpace = false;
        $c0 = '';
        for ($i = 0; $i < $len; $i++) {
            $c = $mbn_min_php[$i];
            if ($stString && ($c === '\\') && !$stBack) {
                $stBack = true;
            } else {
                if ($c === '\'') {
                    $stString = !$stString || $stBack;
                }
                $stBack = false;
            }
            if ($c === ' ') {
                if ($stString) {
                    $mbn_min_php_out .= $c;
                } else {
                    $stSpace = true;
                }
                continue;
            }
            if ($stSpace && preg_match('/\\w\\w/', $c0 . $c)) {
                $mbn_min_php_out .= ' ';
            }
            $mbn_min_php_out .= $c;
            $c0 = $c;
            $stSpace = false;
        }
        $mbn_min_php_out = str_replace('extends\\Exception', 'extends \\Exception', $mbn_min_php_out);
        eval(removePhpTag($mbn_min_php_out));
        $minPhpObj = json_decode(MbnTest::testMbnResult(false));
        if ($minPhpObj->status !== 'OK') {
            $errors[] = ['PHP' => 'Status !== OK'];
            foreach ($minPhpObj->errors as $error) {
                $errors[] = [$error->id . ') ' => $error->raw,
                   '!) ' => $error->correct,
                   '=) ' => $error->incorrect];
            }
        }
        return $mbn_min_php_out;
    }

    $errors = [];

    try {
        $mbn_min_js = checkMinifyJS($errors);
        $mbn_min_php = minifyCheckPHP($errors);
    } catch (Exception $e) {
        return $e->getMessage();
    }

    if (!empty($errors)) {
        $errStr = '';
        foreach ($errors as $err) {
            foreach ($err as $errc => $errl) {
                $errStr .= $errc . ' => ' . trim($errl) . PHP_EOL;
            }
            $errStr .= PHP_EOL;
        }
        return $errStr;
    }


    $license = '/* Mbn {V} / ' . date('d.m.Y') . ' | https://mbn.li | Copyright (c) 2016-' . date('Y')
       . ' Mikołaj Błajek | https://mbn.li/LICENSE */' . PHP_EOL;

    $versionJs = getVersion($mbn_js);
    $versionPhp = getVersion($mbn_php);

    $licenseJs = str_replace('{V}', $versionJs, $license);
    $licensePhp = '<?php ' . str_replace('{V}', $versionPhp, $license);

    $filesToSave['mbn.php'] = removePhpTag($mbn_php, $licensePhp);
    $filesToSave['mbn.min.php'] = removePhpTag($mbn_min_php, $licensePhp);
    $filesToSave['mbn.js'] = preg_replace('/^\s*/', $licenseJs, $mbn_js);
    $filesToSave['mbn.min.js'] = preg_replace('/^\s*/', $licenseJs, $mbn_min_js);
    $filesToSave['mbn.d.ts'] = preg_replace('/^\s*/', $licenseJs, $mbn_d_ts);

    //split into class files
    $mbnClasses = [];
    $mbnClassName = null;
    foreach (explode(PHP_EOL, removePhpTag($mbn_php)) as $line) {
        $line = trim($line, "\n\r");
        if (preg_match('/^class\\s+(\\w+)(?:\\s+extends\\s+\\w+)?/', trim($line), $match)) {
            $mbnClassName = $match[1];
            $mbnClasses[$mbnClassName] = 'namespace Mbn;' . PHP_EOL;
        }
        if ($mbnClassName !== null) {
            $mbnClasses[$mbnClassName] .= $line . PHP_EOL;
        }
    }
    foreach ($mbnClasses as $mbnClassName => $mbnClassLines) {
        $filesToSave[$mbnClassName . '.php'] = $licensePhp . $mbnClassLines;
    }

    $filesToSave['v'] = json_encode(['mbn_js' => $versionJs, 'mbn_php' => $versionPhp, 'hash' => $newHash]);

    FileHelper::clearRelease();
    foreach ($filesToSave as $fileName => $fileContents) {
        FileHelper::putFile($fileName, $fileContents, true);
    }

    return 'update finished: JS ' . $versionJs . ', PHP ' . $versionPhp;
}

echo releaseMbn();
