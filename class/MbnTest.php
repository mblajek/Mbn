<?php

/**
 * @noinspection PhpRedundantCatchClauseInspection
 * @noinspection PhpUndefinedClassInspection
 * @noinspection PhpUnused
 */
class Mbn0 extends Mbn {
    protected static $MbnP = 0;
}

class Mbn3c extends Mbn {
    protected static $MbnP = 3;
    protected static $MbnS = ',';
}

class Mbn20u extends Mbn {
    protected static $MbnP = 20;
    protected static $MbnS = ',';
    protected static $MbnT = true;
}

class Mbn2nef extends Mbn {
    protected static $MbnE = false;
    protected static $MbnF = true;
}

class Mbn4yec extends Mbn {
    protected static $MbnP = 4;
    protected static $MbnE = true;
    protected static $MbnS = ',';
    protected static $MbnL = 20;
}

class MbnColon extends Mbn {
    protected static $MbnS = ':';
}

MbnErr::translate(function ($key, $value) {
    if ($key === 'mbn.invalid_argument') {
        return str_replace('%a%', $value['v'], 'Niepoprawny argument %a% dla konstruktora %v%');
    }
    return null;
});

class MbnTest {
    const CACHE_TIME = 15;
    private static $testsAllJson = null;
    private static $phpTestResult = null;

    public static function getTestsAllJson() /*:string*/ {
        if (self::$testsAllJson === null) {
            self::$testsAllJson = FileHelper::getFile('mbn_test_set.json');
        }
        return self::$testsAllJson;
    }

    private static function runTestMbn($tests) {
        $ret = [];
        $i = 0;
        $evv = null;
        foreach ($tests as $test) {
            list($raw, $req, $exp) = $test;
            try {
                $o = eval($exp);
                if ($o === null || is_bool($o)) {
                    $o = json_encode($o);
                } elseif (is_array($o)) {
                    $o = implode(',', $o);
                }
                $evv = (string)$o;
            } catch (MbnErr $s) {
                $evv = $s->errorKey . ' ' . $s->getMessage();
            } catch (Exception $s) {
                $evv = $s->getMessage();
            }
            $reql = strlen($req);
            if ($reql !== 0 && $req[$reql - 1] === '*') {
                $cmpn = $reql - 1;
            } else {
                $cmpn = $reql + strlen($evv);
            }
            if (strncmp($evv, $req, $cmpn) !== 0) {
                $ret [] = ['id' => $i, 'raw' => $raw, 'code' => $exp, 'correct' => $req, 'incorrect' => $evv];
            }
            $i++;
        }
        return ['status' => (count($ret) === 0) ? 'OK' : 'ERR', 'count' => $i, 'errors' => $ret];
    }

    private static function testMbn() /*:string*/ {
        $time = time();

        $phpCheckFile = 'var/php_check_' . str_replace('.', '-', PHP_VERSION);
        $cachedResult = FileHelper::getFile($phpCheckFile);
        if ($cachedResult !== null) {
            $cachedResultArr = json_decode($cachedResult, true);
            if (isset($cachedResultArr['cache']) && $time - $cachedResultArr['cache'] <= self::CACHE_TIME
                && FileHelper::getCurrentHash() === FileHelper::getCachedHash()) {
                $cachedResultArr['cache'] = true;
                return json_encode($cachedResultArr);
            }
            FileHelper::deleteFile($phpCheckFile);
        }

        $testsAll = json_decode(self::getTestsAllJson());
        $tests = array_merge($testsAll->both, $testsAll->php);
        foreach ($tests as &$test) {
            $tst = $test[0];
            $jsonA = [];
            $pos = 0;
            while (preg_match('/[^)]({[^}]*})/', substr($tst, $pos), $jsonA) === 1) {
                $json = preg_replace('/(\\w+):/', '"$1":', $jsonA[1]);
                $jsonDecoded = json_decode($json, true);
                if ($jsonDecoded === null) {
                    $pos += strlen($jsonA[1]);
                } else {
                    $jsonArr = str_replace([' ', "\r", "\n"], '', var_export($jsonDecoded, true));
                    $tst = str_replace($jsonA[1], $jsonArr, $tst);
                    $pos += strlen($jsonArr);
                }
            }
            $expArr = explode('; ', $tst);
            $exprArrLast = count($expArr) - 1;
            $expArr[$exprArrLast] = 'return ' . $expArr[$exprArrLast] . ';';
            $test[2] = implode('; ', $expArr);
        }
        unset($test);

        $startTimePHP = microtime(true);
        $testPHP = self::runTestMbn($tests);
        $testPHP['MbnV'] = '?';
        try {
            $testPHP['MbnV'] = Mbn::prop()['MbnV'];
        } catch (MbnErr $e) {
        }
        $testPHP['time'] = round((microtime(true) - $startTimePHP) * 1000);
        $testPHP['env'] = 'PHP_' . PHP_VERSION;

        if ($testPHP['status'] === 'OK') {
            FileHelper::putFile($phpCheckFile, json_encode($testPHP + ['cache' => $time]));
        }
        return json_encode($testPHP);
    }

    public static function testMbnResult($encode) /*:string*/ {
        if (!self::$phpTestResult) {
            self::$phpTestResult = self::testMbn();
        }
        return $encode ? json_encode(self::$phpTestResult) : self::$phpTestResult;
    }

    public static function output($contents, $query) /*:void*/ {
        switch ($query) {
            case 'php':
                echo self::testMbnResult(false);
                break;
            case 'js':
                header('Content-Type: application/javascript');
                echo $contents;
                break;
            case 'docker':
                if (env::docker) {
                    (new SimpleHtml())->addPre(array_merge(array_map(function ($v) {
                        return file_get_contents("http://mbn-php$v/mbn_test?php");
                    }, [
                        /* @formatter:off */
                        '5-4', '5-5', '5-6',
                        '7-0', '7-1', '7-2', '7-3', '7-4',
                        '8-0', '8-1', '8-2', '8-3', '8-4',
                        /* @formatter:on */
                    ]),
                        ["---", self::$phpTestResult]))->render();
                } else {
                    (new SimpleHtml())->addErrorDiv('docker disabled')->render();
                }
                break;
            default:
                (new SimpleHtml())->addPre()->addScript($contents)->render();
        }
    }
}
