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
    const CACHE_TIME = 10;
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
        $phpVersion = phpversion();
        $time = time();

        $phpCheckFile = 'release/php_check_' . str_replace('.', '-', $phpVersion);
        if (($cachedResult = FileHelper::getFile($phpCheckFile)) !== null) {
            $cachedResultArr = json_decode($cachedResult, true);
            if (isset($cachedResultArr['cache']) && $time - $cachedResultArr['cache'] <= self::CACHE_TIME) {
                $cachedResultArr['cache'] = true;
                return json_encode($cachedResultArr);
            }
        }

        $testsAll = json_decode(self::getTestsAllJson());
        $tests = array_merge($testsAll->both, $testsAll->php);
        foreach ($tests as &$test) {
            $tst = $test[0];
            $jsonA = [];
            $pos = 0;
            while (preg_match('/[^)]({[^}]*})/', substr($tst, $pos), $jsonA) === 1) {
                $json = preg_replace('/([a-z]+):/i', '"$1":', $jsonA[1]);
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
        $testPHP['env'] = 'PHP_' . $phpVersion;

        FileHelper::putFile($phpCheckFile, json_encode($testPHP + ['cache' => $time]));
        return json_encode($testPHP);
    }

    public static function testMbnResult($encode) {
        if (!self::$phpTestResult) {
            self::$phpTestResult = self::testMbn();
        }
        return $encode ? json_encode(self::$phpTestResult) : self::$phpTestResult;
    }

    public static function output($contents, $query) /*:string*/{
        list ($htmlStart, $htmlEnd) = explode('|', '<html lang="en"><body>|</body></html>');
        switch ($query) {
            case 'php':
                $contents = self::testMbnResult(false);
                break;
            case 'docker':

                $contents = "$htmlStart<pre>" . implode('<br>', array_map(function ($v) {
                       return env::docker ? file_get_contents("http://mbn-php$v/mbn_test?php") : "skipped $v";
                   }, ['5-4', '5-5', '5-6', '7-0', '7-1', '7-2', '7-3', '7-4', '8-0', '8-1']))
                   . "<br>---<br>" . self::$phpTestResult . "</pre>$htmlEnd";
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            default:
                $contents = "$htmlStart<pre></pre><script>$contents</script>$htmlEnd";
        }
        return $contents;
    }
}

ob_start();
?>
function displayResult(displayTestStatusOpt) {
    var displayTestStatus = displayTestStatusOpt || (function (lang, result) {
        document.getElementsByTagName('pre')[0].innerHTML += ((result + " [" + lang + "]<br>"))
    });

    <?=FileHelper::getFile('mbn.js');?>

    var Mbn0 = Mbn.extend(0);
    var Mbn3c = Mbn.extend({MbnP: 3, MbnS: ','});
    var Mbn20u = Mbn.extend({MbnP: 20, MbnS: ',', MbnT: true});
    var Mbn2nef = Mbn.extend({MbnE: false, MbnF: true});
    var Mbn4yec = Mbn.extend({MbnP: 4, MbnE: true, MbnS: ",", MbnL: 20});

    Mbn.MbnErr.translate(function (key, value) {
        if (key === "mbn.invalid_argument") {
            return "Niepoprawny argument %a% dla konstruktora %v%".replace("%a%", value.v);
        }
    });

    //partial JSON support for environment without JSON
    var hasOwnProperty = {}.hasOwnProperty;

    function jsonEncode(o) {
        switch (typeof o) {
            case "number":
                return String(o);
            case "string":
                return '"' + o.replace(/"/g, '\\"') + '"';
            case "object":
                var a = [], r = (o instanceof Array), i;
                for (i in o) {
                    if (hasOwnProperty.call(o, i)) {
                        a.push((r ? "" : jsonEncode(i) + ":") + jsonEncode(o[i]));
                    }
                }
                return (r ? "[" : "{") + a.join(",") + (r ? "]" : "}");
            default:
                throw "invalid type " + (typeof o);
        }
    }

    var runTestMbn = function (tests) {
        var ret = [];
        var tl = tests.length;
        for (var i = 0; i < tests.length; i++) {
            var test = tests[i];
            var raw = test[0];
            var req = test[1];
            var exp = test[2];

            var evv;
            try {
                evv = String(eval(exp));
            } catch (ex) {
                if (ex instanceof Mbn.MbnErr) {
                    evv = String(ex.errorKey) + " " + String(ex);
                } else {
                    evv += String(ex);
                }
            }

            var cmpn;
            var reql = req.length;
            if (reql !== 0 && req.charAt(reql - 1) === '*') {
                cmpn = reql - 1;
            } else {
                cmpn = reql + evv.length;
            }

            if (req.slice(0, cmpn) !== evv.slice(0, cmpn)) {
                ret.push({id: i, raw: raw, code: exp, correct: req, incorrect: evv});
            }
        }
        return {status: (ret.length === 0) ? 'OK' : 'ERR', count: tl, errors: ret};
    };

    function testMbn() {
        /** @type {{both:array, js:array, php:array}} */
        var testsAll = (<?= MbnTest::getTestsAllJson(); ?>);

        var tests = testsAll.both.concat(testsAll.js);
        for (var i = 0; i < tests.length; i++) {
            var test = tests[i];
            test[2] = test[0].replace(/->|::/g, ".").replace(/^\$/, "var $")
               .replace(/\n/g, "\\n").replace(/\r/g, "\\r").replace(/\t/g, "\\t");
        }
        var startTimeJS = new Date();
        var ret = runTestMbn(tests);
        ret.MbnV = Mbn.prop().MbnV;
        ret.time = (new Date()) - startTimeJS;
        ret.env = 'JS';

        displayTestStatus("JS", jsonEncode(ret));
    }

    displayTestStatus("PHP", <?=MbnTest::testMbnResult(true);?>);
    setTimeout(testMbn, 100);
}
displayResult(((typeof displayTestStatus) !== "undefined") ? displayTestStatus : undefined);

<?php
echo MbnTest::output(ob_get_clean(), isset($query) ? $query : null);
