<?php
$relFiles = [
   'mbn.js' => ['desc' => 'Library in JS'],
   'mbn.php' => ['desc' => 'Library in PHP'],
   'mbn.min.js' => ['desc' => 'Minified library in JS'],
   'mbn.min.php' => ['desc' => 'Minified library in PHP'],
   'mbn.d.ts' => ['desc' => 'TypeScript declaration file'],
   'Mbn.php' => ['desc' => 'Mbn class in PHP (with namespace, without MbnErr class)'],
   'MbnErr.php' => ['desc' => 'MbnErr class in PHP (with namespace)'],
];
foreach ($relFiles as $n => &$relFile) {
    if ($n === ucfirst($n)) {
        $n = '_' . $n;
    }
    $relFile['path'] = 'release/' . $n;
}
unset($relFile);

$vString = null;
if (file_exists('release/v')) {
    $vString = file_get_contents('release/v');
}
$getFile = filter_input(INPUT_GET, 'gf');
if (!empty($getFile)) {
    if ($getFile === 'v') {
        header('Content-Type: text/json');
        echo $vString;
    } else {
        $getFileLower = strtolower($getFile);
        $isUcFirstOrLower = ($getFile === $getFileLower) || ($getFile === ucfirst($getFile));
        if ($isUcFirstOrLower && (isset($relFiles[$getFile]) || isset($relFiles[$getFileLower]))) {
            $relFile = isset($relFiles[$getFile]) ? $relFiles[$getFile] : $relFiles[$getFileLower];
            $relFilePath = $relFile['path'];
            $disposition = null;
            $extension = pathinfo($relFilePath, PATHINFO_EXTENSION);
            if (filter_input(INPUT_GET, 'show') === null) {
                switch ($extension) {
                    case 'js':
                    case 'ts':
                        header('Content-Type: text/javascript');
                        break;
                    case 'php':
                        header('Content-Type: application/php');
                        break;
                    default:
                        header('Content-Type: text/plain');
                }
                $disposition = 'attachment';
            } else {
                header('Content-Type: text/plain');
                $disposition = 'inline';
            }
            header('Content-Disposition: ' . $disposition . '; filename="' . $getFile . '"');
            readfile($relFilePath);
        } else {
            header('HTTP/1.0 404 Not Found');
        }
    }
    die;
}
require 'mbn.php';
$hashChanged = true;
if ($vString !== null) {
    $oldHash = json_decode($vString)->hash;
    $newHash = hash('sha256', file_get_contents('mbn.js') . file_get_contents('mbn.php') . file_get_contents('mbn.d.ts'));
    if ($oldHash === $newHash) {
        $hashChanged = false;
    } elseif (file_exists('release/php_check')) {
        unlink('release/php_check');
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <title>Mbn Library</title>
    <meta charset="UTF-8">
    <meta name="description"
          content="Library for PHP and JS to do calculations with any precision and correct (half-up) rounding.">
    <link rel="icon" href="page/favicon.ico" type="image/png"/>
    <link rel="stylesheet" href="page/style.css"/>
</head>
<body>
<script>
    //avoid any caching
    <?php readfile('mbn.js') ?>
    var releaseStatus = {
        hashChanged: <?= json_encode($hashChanged) ?>,
        passedPHP: false,
        passedJS: false
    };
</script>

<script src="page/script.js"></script>
<script src="mbn_test.js"></script>

<div id="topBar">
    <span>
    <a href="#about">about</a> |
    <a href="#downloads">downloads</a> |
    <a href="#reference">reference</a> |
    <a href="#class_declarations">class declarations</a> |
    <a href="#object_declarations">object declarations</a> |
    <a href="#exceptions">exceptions</a> |
    <a href="#changelog">changelog</a> |
    <a href="javascript:void 0;" id="darkMode">&#x263D;</a>
    </span>
</div>
<div id="scroller">
    <div class="title1">Mbn (Multi-byte number) Library</div>
    <div>Library for PHP and JS to do calculations with any precision and correct (half-up) rounding.</div>
    <div class="title2" id="about">About</div>
    <div>Main job of the library is to regain control on numbers.</div>
    <div>Most of computer maths bases on float/double numbers which are fast and precise, but cause some problems in
        fixed-precision (e.g. financial) calculations.
    </div>
    <div>Also it's easy to get unexpected some NaN and Infinity values. Usually results should be formatted in concrete
        way,
        what is more or less available in languages.
    </div>
    <div>In Mbn library:
        <ul>
            <li>parsing invalid strings, division by zero and many more problems are thrown as exceptions</li>
            <li>all calculations have predictable results, 1.4 - 0.4 gives always 1, not 0.9999999999999999</li>
            <li>almost identical syntax between JS and PHP, all operations supported by a single class</li>
            <li>fixed precision with any size of fractional part: from zero to thousands and more</li>
            <li>built in <a href="#other_methods_calc">expression parser</a>, by default =2+2*2 is parsed as 6, =2PI as
                6.28, see <a href='calc'>calc example</a></li>
            <li>built in <a href="#other_methods_split">split</a> and <a href="#other_methods_reduce">reduce</a>
                functions
                for some useful array operations
            </li>
            <li>custom formatting: dot/coma separator, grouping thousands, truncating trailing zeros</li>
            <li>exception messages can be easily translated</li>
            <li>compatibility: PHP 5.4+, JS ES3+ (IE6+)</li>
        </ul>
    </div>
    <div>Mbn is distributed under the <a href='https://github.com/mblajek/Mbn/blob/master/LICENSE.txt'>MIT License</a>,
        see
        <a href='https://github.com/mblajek/Mbn'>Github page</a>.
    </div>
    <div>Available for PHP Composer via <a href='https://packagist.org/packages/mblajek/mbn'>Packagist</a>.</div>

    <div class="title2" id="tests_and_benchmark">Tests and benchmark<span id="releaseBtn"
                                                                          style="cursor:pointer; visibility:hidden;"> &#8635;</span>
    </div>
    <pre><span class="lb"></span><strong id="resultJS">..</strong></pre>
    <pre><span class="lb"></span><strong id="resultPHP">..</strong></pre>

    <div class="title2" id="downloads">Downloads</div>
    <div>Minified JS is created with <a href='https://closure-compiler.appspot.com'>Google Closure api</a></div>
    <div>Minified PHP is created with custom text replacements, intended to be used in online PHP sandboxes like <a
           href="https://3v4l.org/">3v4l.org</a>
    </div>
    <div>Generally code is optimized for speed and size; not for readability</div>
    <?php try {
        foreach ($relFiles as $n => &$relFile) { ?>
            <pre><span class="lb"></span><strong><?= $n ?></strong> [ <a href="lib/<?= $n ?>&amp;show">show</a> | <a
                   href="lib/<?= $n ?>">download</a> ] (<?= (new Mbn(filesize($relFile['path'])))->div(1024) ?> kB)<!--
--><br/><span class="lb"></span><?= $relFile['desc'] ?></pre>
        <?php }
    } catch (MbnErr $e) {
    }
    unset($relFile); ?>

    <div class="title2" id="reference">Reference</div>
    <div>JS and Mbn code equivalents.</div>
    <div>In most cases Mbn code in PHP and JS is identical - <span class="monoInline">a.f()</span> in JS is <span
           class="monoInline">$a-&gt;f()</span> in PHP
    </div>
    <div>
        <table>
            <tbody>
            <tr>
                <th>operation</th>
                <th>JS (Number)</th>
                <th>Mbn</th>
                <th>JS (Number)</th>
                <th>Mbn</th>
                <th>return type</th>
            </tr>
            <tr>
                <th>declaration</th>
                <td>a = b</td>
                <td>a = new Mbn(b)</td>
                <td>a = 0;<br>a = b;</td>
                <td>a = new Mbn();<br>a.set(b);</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>add</th>
                <td>a + b</td>
                <td>a.add(b)</td>
                <td>a += b</td>
                <td>a.add(b, true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>subtract</th>
                <td>a - b</td>
                <td>a.sub(b)</td>
                <td>a -= b</td>
                <td>a.sub(b, true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>multiply</th>
                <td>a * b</td>
                <td>a.mul(b)</td>
                <td>a *= b</td>
                <td>a.mul(b, true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>divide</th>
                <td>a / b</td>
                <td>a.div(b)</td>
                <td>a /= b</td>
                <td>a.div(b, true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>modulo</th>
                <td>a % b</td>
                <td>a.mod(b)</td>
                <td>a %= b</td>
                <td>a.mod(b, true)</td>
                <th>Mbn</th>
            </tr>
            <tr class="hidden"></tr>
            <tr>
                <td colspan="6">result has same sign as the original number</td>
            </tr>
            <tr>
                <th>minimum</th>
                <td>Math.min(a, b)</td>
                <td>a.min(b)</td>
                <td>a = Math.min(a, b)</td>
                <td>a.min(b, true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>maximum</th>
                <td>Math.max(a, b)</td>
                <td>a.max(b)</td>
                <td>a = Math.max(a, b)</td>
                <td>a.max(b, true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>power</th>
                <td>Math.pow(a, b)</td>
                <td>a.pow(b)</td>
                <td>a = Math.pow(a, b)</td>
                <td>a.pow(b, true)</td>
                <th>Mbn</th>
            </tr>
            <tr class="hidden"></tr>
            <tr>
                <td colspan="6">integer exponent only</td>
            </tr>
            <tr>
                <th>factorial</th>
                <td></td>
                <td>a.fact()</td>
                <td></td>
                <td>a.fact(true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>round</th>
                <td>Math.round(a)</td>
                <td>a.round()</td>
                <td>a = Math.round(a)</td>
                <td>a.round(true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>floor</th>
                <td>Math.floor(a)</td>
                <td>a.floor()</td>
                <td>a = Math.floor(a)</td>
                <td>a.floor(true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>ceiling</th>
                <td>Math.ceil(a)</td>
                <td>a.ceil()</td>
                <td>a = Math.ceil(a)</td>
                <td>a.ceil(true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>integer part of value</th>
                <td>Math.trunc(a)</td>
                <td>a.intp()</td>
                <td>a = Math.trunc(a)</td>
                <td>a.intp(true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>absolute value</th>
                <td>Math.abs(a)</td>
                <td>a.abs()</td>
                <td>a = Math.abs(a)</td>
                <td>a.abs(true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>additional inverse</th>
                <td>-a</td>
                <td>a.inva()</td>
                <td>a = -a</td>
                <td>a.inva(true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>multiplicative inverse</th>
                <td>1 / a</td>
                <td>a.invm()</td>
                <td>a = 1 / a</td>
                <td>a.invm(true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>square root</th>
                <td>Math.sqrt(a)</td>
                <td>a.sqrt()</td>
                <td>a = Math.sqrt(a)</td>
                <td>a.sqrt(true)</td>
                <th>Mbn</th>
            </tr>
            <tr>
                <th>sign</th>
                <td>Math.sign(a)</td>
                <td>a.sgn()</td>
                <td>a = Math.sign(a)</td>
                <td>a.sgn(true)</td>
                <th>Mbn</th>
            </tr>
            <tr class="hidden"></tr>
            <tr>
                <td colspan="6">negative &rarr; -1, positive &rarr; 1, 0 &rarr; 0</td>
            </tr>
            <tr>
                <th>clone</th>
                <td>b = null<br/>b = a</td>
                <td>b = null<br/>b = a.add(0)</td>
                <td>b = 0;<br/>b = a;</td>
                <td>b = new Mbn()<br/>b.set(a)</td>
                <th>Mbn</th>
            </tr>
            <tr class="hidden"></tr>
            <tr>
                <td colspan="6">'b = a' when 'a' is object, only passes reference to existing instance of object<br/>a.add(0)
                    is easiest way to create new instance the same Mbn class with the same value<br/>Mbn object has
                    method
                    'set', so it can be modified to have specified value
                </td>
            </tr>
            <tr>
                <th>equals</th>
                <td>a === b</td>
                <td>a.eq(b)</td>
                <td></td>
                <td></td>
                <th>boolean</th>
            </tr>
            <tr class="hidden"></tr>
            <tr>
                <td colspan="6">'b === a' when 'a' and 'b' are objects, only checks, if both vars are references to the
                    same instance of object
                </td>
            </tr>
            <tr>
                <th>equals<br>with max diff</th>
                <td>Math.abs(a - b) &lt;= 0.1</td>
                <td>a.eq(b, 0.1)</td>
                <td></td>
                <td></td>
                <th>boolean</th>
            </tr>
            <tr>
                <th>compare</th>
                <td>Math.sign(a - b)</td>
                <td>a.cmp(b)</td>
                <td></td>
                <td></td>
                <th>number [js]<br/>int [php]</th>
            </tr>
            <tr class="hidden"></tr>
            <tr>
                <td colspan="6">a &lt; b &rarr; -1, a &gt; b &rarr; 1, a === b &rarr; 0</td>
            </tr>
            <tr>
                <th>comparing</th>
                <td>a &lt; b<br>a &lt;= b<br>a &gt;= b<br>a &gt; b</td>
                <td>a.cmp(b) &lt; 0<br>a.cmp(b) &lt;= 0<br>a.cmp(b) &gt;= 0<br>a.cmp(b) &gt; 0</td>
                <td></td>
                <td></td>
                <th>boolean</th>
            </tr>
            <tr>
                <th>compare<br>with max diff</th>
                <td>Math.abs(a - b) &lt;= 0.1</td>
                <td>a.cmp(b, 0.1)</td>
                <td></td>
                <td></td>
                <th>number [js]<br/>int [php]</th>
            </tr>
            <tr>
                <th>is integer</th>
                <td>Math.round(a) === a</td>
                <td>a.isInt()</td>
                <td></td>
                <td></td>
                <th>boolean</th>
            </tr>
            <tr>
                <th>to number</th>
                <td>Number(a)</td>
                <td>a.toNumber()</td>
                <td></td>
                <td></td>
                <th>number [js]<br/>int/float [php]</th>
            </tr>
            <tr class="hidden"></tr>
            <tr>
                <td colspan="6">Number(a) when 'a' is Mbn may cause errors for Mbn with comma separator, thousand
                    formatting
                    etc.<br>when precision is 0, toNumber in PHP returns int
                </td>
            </tr>
            <tr>
                <th>to string</th>
                <td>a.toString()</td>
                <td>a.toString() [js,php]<br/>$a->__toString() [php]</td>
                <td></td>
                <td></td>
                <th>string</th>
            </tr>
            <tr class="hidden"></tr>
            <tr>
                <td colspan="6">gets default string representation of Mbn, based on Mbn* class params<br>
                    JS toString() and PHP __toString() are used by these languages by default while casting<br>
                    (string)$a, ' '.$a [php], String(a), " "+a [js]
                </td>
            </tr>
            <tr>
                <th>format</th>
                <td></td>
                <td>a.format()</td>
                <td></td>
                <td></td>
                <th>string</th>
            </tr>
            <tr class="hidden"></tr>
            <tr>
                <td colspan="6">gets string representation with changed Mbn* class params<br>params: boolean - trigger
                    thousand formatting (grouping), default true<br/>object - Mbn* params, truncation, formatting,
                    precision, separator; missing - inherit from class
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="title2" id="class_declarations">Class declarations</div>
    <div>Each Mbn class has few parameters defining it's precision, default format and behavior
        <br/>Library in JS and PHP delivers single class named Mbn with default parameters. This class can be extended.
        <br/>Available parameters:
    </div>
    <ul>
        <li><strong>MbnP</strong> - precision - number of digits in fractional part, defines how many digits will be
            stored
            <br>by default also defines string representation, MbnP=0 &rarr; "0", MbnP=2 &rarr; "0.00"
            <ul>
                <li>Default: 2</li>
            </ul>
        </li>
        <li>
            <strong>MbnS</strong> - separator - dot or comma, decimal separator in string representation
            <ul>
                <li>Default: . (dot)</li>
            </ul>
        </li>
        <li>
            <strong>MbnT</strong> - truncation - true or false, truncation of trailing zeros in string representation
            <br>for MbnP=2 and MbnT=true: 1.12 &rarr; "1.12", 1.10 &rarr; "1.1", 1.00 &rarr; "1"
            <ul>
                <li>Default: false (no truncation)</li>
            </ul>
        </li>
        <li>
            <strong id="class_declarations_mbne">MbnE</strong> - evaluating - true, false or null, triggers usage of
            expression parser
            <ul>
                <li>true: all expressions are evaluated</li>
                <li>null: expressions starting with "=", like "=2+3" ale evaluated</li>
                <li>false: no expressions ale evaluated, "=2+3" causes invalid format exception</li>
                <li><span class="monoInline">new Mbn("=2+3", true)</span> is parsed always regardless of MbnE</li>
                <li><span class="monoInline">new Mbn("=2+3", false)</span> is never parsed regardless of MbnE</li>
                <li>When object [js] or array [php] is passed as second argument, also expression is parsed:
                    <br><span class="monoInline">new Mbn("=2a", {a: 1})</span> [js] / <span class="monoInline">new Mbn('=2a', ['a' => 1])</span>
                    [php]
                </li>
                <li>MbnE doesn't affect <span class="monoInline">Mbn.calc("2+3")</span> [js] / <span class="monoInline">Mbn::calc("2+3")</span>
                    [php]
                </li>
                <li>Default: null</li>
            </ul>
        </li>
        <li>
            <strong>MbnF</strong> - formatting - true or false, grouping thousands (with space) in string representation
            <br>for MbnP=5 and MbnF=true 12345.12345 &rarr; "12 345.12345"
            <ul>
                <li>Default: false (no formatting)</li>
            </ul>
        </li>
        <li>
            <strong>MbnL</strong> - limit - number of digits, that will cause limit_exceeded exception
            <br>some short expressions like "=9!!" or "=9^9^9" can have really big results and take much time
            <br>MbnL can avoid interface freeze or server overload
            <br>hint: some operations like power, may exceed limit when result shouldn't, because of storing exact
            result
            during calculations
            <ul>
                <li>Default: 1000</li>
            </ul>
        </li>
    </ul>

    <div class="title2" id="class_declarations_js">Class declarations in JS</div>
    <div>Default Mbn class can be extended with <span class="monoInline">Mbn.extend()</span> method
        <br/>Single precision as number, or object with Mbn* parameters can be passed.
        <br/>hint: Mbn in JS is not exactly class, it's a class, function and object
        <br/>Derived classes cannot be extended
    </div>
    <script>
        w(["//default: precision 2, dot separator, ...", "//class already defined in library", "//var Mbn = Mbn.extend();"], "mono");
        we('new Mbn("12.7");');

        w();
        var Mbn0 = Mbn.extend(0);
        w(["//precision 0", "var Mbn0 = Mbn.extend(0);"], "mono");
        we('new Mbn0("12.7");');

        w();
        var Mbn3 = Mbn.extend(3);
        w(['//precision 3', 'var Mbn3 = Mbn.extend(3);'], "mono");
        we('new Mbn3("12.7");');

        w();
        var Mbn4c = Mbn.extend({MbnP: 4, MbnS: ","});
        w(['//precision 4, coma output separator', 'var Mbn4c = Mbn.extend({MbnP: 4, MbnS: ","});'], "mono");
        we('new Mbn4c("12.7");');

        w();
        var Mbn5t = Mbn.extend({MbnP: 5, MbnT: true});
        w(['//precision 5, truncate zeros', 'var Mbn5t = Mbn.extend({MbnP: 5, MbnT: true});'], "mono");
        we('new Mbn5t("12.7");');
    </script>
    <div class="title2" id="class_declarations_php">Class declarations in PHP</div>
    <div>Default Mbn class can be extended standard inheritance by overriding protected static fields
        <br/>Mbn* fields which are not overridden, have default value
        <br/>Derived classes shouldn't be extended
    </div>
    <pre><span class="lb"></span>class Mbn0 extends Mbn {<!--
--><br/><span class="lb"></span>  protected static $MbnP = 0;<!--
--><br/><span class="lb"></span>}</pre>
    <div></div>
    <pre><span class="lb"></span>class Mbn4c extends Mbn {<!--
--><br/><span class="lb"></span>  protected static $MbnP = 4;<!--
--><br/><span class="lb"></span>  protected static $MbnS = ',';<!--
--><br/><span class="lb"></span>}</pre>
    <div></div>
    <pre><span class="lb"></span>class Mbn5t extends Mbn {<!--
--><br/><span class="lb"></span>  protected static $MbnP = 5;<!--
--><br/><span class="lb"></span>  protected static $MbnT = true;<!--
--><br/><span class="lb"></span>}</pre>

    <div class="title2" id="object_declarations">Object declarations</div>
    <div>There are several types of values that can be passed as first constructor argument - a value</div>
    <ul>
        <li>none - <span class="monoInline">new Mbn()</span> &rarr; 0</li>
        <li>boolean - <span class="monoInline">new Mbn(true / false)</span> &rarr; 1 / 0</li>
        <li>string - value from string, examples of valid arguments for default Mbn
            <ul>
                <li>dot/coma decimal separator: "12.123", "12,123"</li>
                <li>missing fractional or integer part: ".123" &rarr; "0.12", "12." &rarr; "12.00"</li>
                <li>number with formatted integer part: "12 345,123" &rarr; "12345.12"</li>
                <li>expression, like mentioned in <a href="#class_declarations_mbne">class declarations</a> and <a
                       href="#other_methods_calc">expression parser</a> sections
                </li>
            </ul>
        </li>
        <li>object - if can be casted to valid string, it's parsed<br>e.g. instance of another Mbn class</li>
        <li>Mbn object - if object is instance of the same Mbn class, operation is faster</li>
        <li>cannot be array - array <span class="monoInline">[1, 2]</span> has valid string representation "1,2", but
            shouldn't be parsed
        </li>
    </ul>
    <div>Second argument to constructor may be true / false or object [js] / array [php], what affects expression
        evaluation
        <br>as mentioned in <a href="#class_declarations_mbne">class declarations</a> and <a
           href="#other_methods_calc">expression parser</a> sections
    </div>
    <div>In JS Mbn called as a function, also returns new instance of Mbn (<span class="monoInline">Mbn()</span> instead
        of
        <span class="monoInline">new Mbn()</span>)
    </div>
    <div class="title2">Dealing with Mbn objects</div>
    <ul>
        <li>Mbn objects have "magic" <span class="monoInline">.toString()</span> [js] / <span class="monoInline">->__toString()</span>
            [php] methods
            <br/><span class="monoInline">(new Mbn(2)) + "x"</span> [js] / <span
               class="monoInline">(new Mbn(2)) . 'x'</span> [php] gives "2.00x"
        </li>
        <li>value passed as first argument, to two-argument function is firstly converted to Mbn class of object
            <ul>
                <li>two-argument functions: add, sub, mul, div, mod, min, max, pow</li>
                <li>invalid value passed as this argument causes exception
                    <br><span class="monoInline">(new Mbn(2)).add("x")</span></li>
                <li>value with bigger precision is firstly truncated to precision
                    <br><span class="monoInline">(new Mbn(2)).add("1.999")</span>
                    &rarr; <span class="monoInline">(new Mbn(2)).add(new Mbn("1.999"))</span> &rarr; "4.00"
                </li>
            </ul>
        </li>
        <li>value passed as last a argument, to all standard functions triggers modification of original object (===
            true)
            <ul>
                <li>standard functions: two-argument functions and round, floor ceil, intp, abs, inva, invm, sqrt, sgn
                </li>
                <li>for <span class="monoInline">a = new Mbn(2); b = a.add(1);</span> "a" stays unchanged and "b" is set
                    to
                    result
                </li>
                <li>for <span class="monoInline">a = new Mbn(2); b = a.add(1, true);</span> "a" is changed, but "b" gets
                    simply
                    reference to "a"
                </li>
            </ul>
        </li>
        <li>because results are Mbn objects, it's possible to use method chaining
            <ul>
                <li>sum of 3 numbers: <span class="monoInline">a = b.add(c).add(d)</span>
                    <br/>ad 2 numbers to "a": <span class="monoInline">a.add(b, true).add(c, true)</span></li>
                <li>sum of 2 numbers, but lot less than zero: <span class="monoInline">b = a.add(x).max(0)</span>
                    <br/>limit "a" to be between two and three: <span
                       class="monoInline">a.max(2, true).min(3, true)</span>
                </li>
            </ul>
        </li>
        <li>loosing / keeping precision
            <ul>
                <li>results of operations are predictable, but in some cases may lead to loose of precision, what is not
                    implementation problem, result of 0.01*0.01 may be 0.00001, but this number doesn't exist in Mbn
                    class with 2 digits of fractional part
                </li>
                <li>some Mbn functions never lead to loss of precision: add, sub, mod, min, max, fact, inva</li>
                <li>for mul (and precision = 2) it depends on number of size of arguments fractional parts, 1.23*5 or
                    1.2*3.4 never looses precision, 1.23*4.5 always looses
                </li>
                <li>for div (and precision = 2) it depends, e.g. division of integer by 100 or by it's own divisor never
                    looses precision
                </li>
                <li>pow and sqrt should be used with care</li>
                <li>when loss of precision is necessary, operations should be done in right order, only the last
                    operation should loose precision, sometimes multiplication by 100 may be needed
                    <ul>
                        <li>exact result of 4.11*0.23/2 is 0.47265, and this will be result such calculations in in Mbn
                            with
                            precision=5, with precision = 2 result should be 0.47, but 4.11*0.23/2 = 0.48, because
                            precision is lost in multiplication and division, 411*0.23/200 is
                            right, precision is lost only in division
                        </li>
                        <li>0.01/2*100 = 1, because precision is lost in division, 0.01*100/2 is right, no precision is
                            lost
                        </li>
                    </ul>
                </li>
            </ul>
        </li>
    </ul>

    <div class="title2" id="exceptions">Exceptions</div>
    <div>All exceptions are instances of MbnErr class</div>
    <div>JS: MbnErr has field "message", and method "toString" returns that message <span
           class="monoInline">ex.message</span>, <span class="monoInline">String(ex)</span></div>
    <div>PHP: MbnErr extends Exception, message available with <span class="monoInline">$ex->getMessage()</span></div>
    <div>Moreover MbnErr has fields "errorKey" and "errorValues" which represent concrete situation.</div>
    <div>Field errorValues contains string representations of values to message or is empty, when there is no value to
        pass
    </div>
    <div>Possible values of errorKey:</div>
    <ul>
        <li><span class="monoInline">mbn.invalid_argument</span> - value passed to Mbn constructor is in wrong type,
            e.g.
            function, array, ..
            <ul>
                <li>errorValues.v is string representation of value</li>
                <li><span class="monoInline">new Mbn(function(){})</span>, <span
                       class="monoInline">new Mbn([1,2])</span>,
                    <span
                       class="monoInline">new Mbn(NaN)</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.invalid_format</span> - string value passed to Mbn constructor is invalid
            <ul>
                <li>errorValues.v is passed string value or string value of passed object</li>
                <li><span class="monoInline">new Mbn("x")</span>, <span class="monoInline">new Mbn("1..2")</span></li>
                <li><span class="monoInline">Mbn({toString:function(){return "x"}})</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.limit_exceeded</span> - value reaches limit of digits from MbnL
            <ul>
                <li>errorValues.v is MbnL, exact value which caused exception is unknown</li>
                <li><span class="monoInline">new Mbn("x")</span>, <span class="monoInline">new Mbn("1..2")</span></li>
                <li><span class="monoInline">Mbn({toString:function(){return "x"}})</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.div.zero_divisor</span> - division by zero
            <ul>
                <li>errorValues is empty</li>
                <li><span class="monoInline">a.div(0)</span>, <span class="monoInline">a.mod(0)</span>, <span
                       class="monoInline">(new Mbn(0)).invm()</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.pow.unsupported_exponent</span> - only integer exponents are supported
            <ul>
                <li>errorValues.v is given exponent</li>
                <li><span class="monoInline">a.pow(0.5)</span>, <span class="monoInline">a.pow(1.5)</span>, <span
                       class="monoInline">Mbn.calc("2^.5")</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.fact.invalid_value</span> - factorial can be calculated only for non-negative
            integers
            <ul>
                <li>errorValues.v is current value</li>
                <li><span class="monoInline">(new Mbn(-2)).fact()</span>, <span
                       class="monoInline">Mbn.calc("0.5!")</span>
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.sqrt.negative_value</span> - square root can be calculated only for
            non-negative
            numbers
            <ul>
                <li>errorValues.v is current value</li>
                <li><span class="monoInline">(new Mbn(-2)).sqrt()</span>, <span
                       class="monoInline">Mbn.calc("sqrt(-2)")</span>,
                    <span class="monoInline">Mbn.reduce("sqrt", [2, -2])</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.cmp.negative_diff</span> - maximal difference cannot be negative
            <ul>
                <li>errorValues.v is current value</li>
                <li><span class="monoInline">(new Mbn(2)).cmp(3, -1)</span>, <span
                       class="monoInline">(new Mbn(2)).eq(3, -1)</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.extend.invalid_precision</span> - invalid value for precision (MbnP)
            <ul>
                <li>errorValues.v is given precision</li>
                <li><span class="monoInline">Mbn.extend(-2)</span>, <span
                       class="monoInline">Mbn.extend({MbnP: 0.5})</span>
                </li>
                <li>PHP: derived classes are not checked in runtime, but method <span
                       class="monoInline">Mbn::prop()</span>
                    checks it
                </li>
                <li><span
                       class="monoInline">class Mbn_5 extends Mbn {protected static $MbnP = 0.5;} Mbn_5::prop();</span>
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.format.invalid_precision</span> - invalid value for precision (MbnP)
            <ul>
                <li>errorValues.v is given precision</li>
                <li><span class="monoInline">a.format({MbnP: 0.5})</span> [js], <span class="monoInline">$a->format(['MbnP' => 0.5])</span>
                    [php]
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.extend.invalid_separator</span> - invalid value for decimal separator (MbnS)
            <ul>
                <li>errorValues.v is given separator</li>
                <li><span class="monoInline">Mbn.extend({MbnS: 1})</span>, <span
                       class="monoInline">Mbn.extend({MbnS: ':'})</span></li>
                <li><span
                       class="monoInline">class MbnCol extends Mbn {protected static $MbnS = ':';} MbnCol::prop();</span>
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.format.invalid_separator</span> - invalid value for decimal separator (MbnS)
            <ul>
                <li>errorValues.v is given separator</li>
                <li><span class="monoInline">a.format({MbnS: 1})</span> [js], <span
                       class="monoInline">$a->format(['MbnS' => 1])</span> [php]
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.extend.invalid_truncation</span> - invalid value for truncation of trailing
            zeros
            (MbnT)
            <ul>
                <li>errorValues.v is given truncation</li>
                <li><span class="monoInline">Mbn.extend({MbnT: 1})</span></li>
                <li><span class="monoInline">class MbnT1 extends Mbn {protected static $MbnT = 1;} MbnT1::prop();</span>
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.format.invalid_truncation</span> - invalid value for truncation of trailing
            zeros
            (MbnT)
            <ul>
                <li>errorValues.v is given truncation</li>
                <li><span class="monoInline">a.format({MbnT: 1})</span> [js], <span
                       class="monoInline">$a->format(['MbnT' => 1])</span> [php]
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.extend.invalid_evaluating</span> - invalid value for evaluating trigger (MbnE)
            <ul>
                <li>errorValues.v is given evaluating trigger</li>
                <li><span class="monoInline">Mbn.extend({MbnE: 1})</span></li>
                <li><span class="monoInline">class MbnE1 extends Mbn {protected static $MbnE = 1;} MbnE1::prop();</span>
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.format.invalid_evaluating</span> - invalid value for evaluating trigger (MbnE)
            <ul>
                <li>hint: MbnE doesn't affect format(), but is validated; this behavior may be changed</li>
                <li>errorValues.v is given evaluating trigger</li>
                <li><span class="monoInline">a.format({MbnE: 1})</span> [js], <span
                       class="monoInline">$a->format(['MbnE' => 1])</span> [php]
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.extend.invalid_formatting</span> - invalid value for formatting (MbnF)
            <ul>
                <li>errorValues.v is given formatting</li>
                <li><span class="monoInline">Mbn.extend({MbnF: 1})</span></li>
                <li><span class="monoInline">class MbnF1 extends Mbn {protected static $MbnF = 1;} MbnF1::prop();</span>
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.format.invalid_formatting</span> - invalid value for formatting (MbnF)
            <ul>
                <li>errorValues.v is given formatting</li>
                <li><span class="monoInline">a.format({MbnF: 1})</span> [js], <span
                       class="monoInline">$a->format(['MbnF' => 1])</span> [php]
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.extend.invalid_limit</span> - invalid value digit limit (MbnL)
            <ul>
                <li>errorValues.v is given limit</li>
                <li><span class="monoInline">Mbn.extend({MbnE: Infinity})</span>, <span class="monoInline">Mbn.extend({MbnE: -1})</span>
                </li>
                <li><span
                       class="monoInline">class MbnLm1 extends Mbn {protected static $MbnL = -1;} MbnLm1::prop();</span>
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.format.invalid_limit</span> - invalid value digit limit (MbnL)
            <ul>
                <li>hint: MbnL doesn't affect format(), but is validated; this behavior may be changed</li>
                <li>errorValues.v is given limit</li>
                <li><span class="monoInline">a.format({MbnL: -1})</span> [js], <span class="monoInline">$a->format(['MbnL' => -1])</span>
                    [php]
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.calc.undefined</span> - undefined variable in expression
            <ul>
                <li>errorValues.v is name of undefined variable</li>
                <li><span class="monoInline">Mbn.calc("a*b", {a: 5})</span> [js], <span class="monoInline">Mbn::calc("a*b", ['a' => 5])</span>
                    [php]
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.calc.unexpected</span> - unexpected token in expression
            <ul>
                <li>errorValues.v is unexpected token or rest of expression starting with that token</li>
                <li><span class="monoInline">Mbn.calc("/ 2")</span>, <span class="monoInline">Mbn.calc("(2 * 3")</span>
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.def.undefined</span> - constant is not defined
            <ul>
                <li>errorValues.v is name of undefined constant</li>
                <li><span class="monoInline">Mbn.def("A")</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.def.already_set</span> - constant already has a value
            <ul>
                <li>errorValues.v is name of constant</li>
                <li>errorValues.w is current value of constant</li>
                <li><span class="monoInline">Mbn.def("PI", 2)</span>, <span
                       class="monoInline">Mbn.def("A", 2); Mbn.def("A", 2)</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.def.invalid_name</span> - invalid name for constant
            <ul>
                <li>errorValues.v is name of constant</li>
                <li><span class="monoInline">Mbn.def("2", 2)</span>, <span class="monoInline">Mbn.def("2")</span>, <span
                       class="monoInline">Mbn.def(null, "2")</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.split.invalid_part_count</span> - invalid number of parts, should be positive
            integer
            <ul>
                <li>errorValues.v is number of parts</li>
                <li><span class="monoInline">a.split(0)</span>, <span class="monoInline">a.split(-0.5)</span>, <span
                       class="monoInline">a.split([])</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.split.zero_part_sum</span> - sum of parts is zero, value cannot be split
            <ul>
                <li>errorValues is empty</li>
                <li><span class="monoInline">a.split([-1, 1])</span>, <span
                       class="monoInline">a.split([1, -2, 1])</span>
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.reduce.invalid_function</span> - invalid function name passed to "reduce"
            <ul>
                <li>errorValues.v is given function name</li>
                <li><span class="monoInline">a.reduce("x", [1])</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.reduce.no_array</span> - no array given
            <ul>
                <li>errorValues is empty</li>
                <li><span class="monoInline">a.reduce("sqrt", 1)</span>, <span
                       class="monoInline">a.reduce("add", 1, 2)</span>
                </li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.reduce.invalid_argument_count</span> - two arguments passed to single-argument
            function
            <ul>
                <li>errorValues is empty</li>
                <li><span class="monoInline">a.reduce("sqrt", [1, 2], [3, 4])</span>, <span class="monoInline">a.reduce("inva", [1, 2], 3)</span>,
                    <span class="monoInline">a.reduce("abs", 1, [2, 3])</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.reduce.different_lengths</span> - given arrays have different lengths
            <ul>
                <li>errorValues.v is length of first array</li>
                <li>errorValues.w is length of second array</li>
                <li><span class="monoInline">a.reduce("add", [1, 2], [3])</span></li>
            </ul>
        </li>
        <li><span class="monoInline">mbn.reduce.different_keys</span> - given arrays have different keys
            <ul>
                <li>hint: only may be thrown in PHP</li>
                <li>errorValues.v is keys of first array, e.g. "0,a"</li>
                <li>errorValues.w is keys of second array"</li>
                <li><span class="monoInline">a::reduce("add", [1, 'a' => 2], [3, 4])</span></li>
            </ul>
        </li>
    </ul>
    <div class="title2" id="changelog">Changelog</div>
    <ul>
        <li>31.03.2020 - added {comments} to expression parser <strong>(1.50)</strong></li>
        <li>31.03.2020 - fixed multiline expressions, "2\n+3" is 5, not 2</li>
        <li>31.03.2020 - invalid grouping like "1 .0" or "1(multiple spaces)0" no longer parsed</li>
        <li>03.01.2020 - added factorial to reduce</li>
        <li>13.12.2019 - minor PHP code changes</li>
        <li>11.12.2019 - changed MbnErr.errorValue (string|null) to errorValues (array[php], object[js])
            <strong>(1.49)</strong></li>
        <li>20.11.2019 - fixed PHP 5.4 compatibility <strong>(1.48)</strong></li>
        <li>20.11.2019 - fixed PHP bug when creating basic Mbn object from object of derived class (since 28.09.2017)
        </li>
        <li>21.10.2019 - fixed PHP wrong errorValue for reduce.different_keys</li>
        <li>21.10.2019 - validating constant name also for checking of existence e.g. Mbn::def(null, "2")</li>
        <li>20.10.2019 - NaN as argument throws mbn.invalid_argument exception instead of mbn.limit_exceeded</li>
        <li>20.10.2019 - fixed MbnL validation</li>
        <li>18.10.2019 - format(5) worked as format(false), now throws mbn.format.invalid_formatting exception</li>
        <li>18.10.2019 - PHP: Mbn::prop() throws mbn.extend, not Mbn.prop exceptions, also mbn.prop exceptions were
            broken
        </li>
        <li>17.10.2019 - better representation of passed invalid values <strong>(1.47)</strong></li>
        <li>15.10.2019 - all code reformatted to 4-space indents</li>
        <li>14.10.2019 - fixed wrong message for limit_exceeded (since 10.10.2019)</li>
        <li>14.10.2019 - fixed JS formatting bug - undeclared variable (since 08.01.2018)</li>
        <li>11.10.2019 - added omitConsts param to Mbn.check()</li>
        <li>11.10.2019 - added MbnErr.translate() - error translation function</li>
        <li>10.10.2019 - added errorKey / errorValue fields to MbnErr <strong>(1.46)</strong></li>
        <li>10.10.2019 - JS: MbnErr accessible as Mbn.MbnErr</li>
        <li>10.10.2019 - fixed JS bug for constant named "hasOwnProperty"</li>
        <li>23.09.2019 - PHP: Mbn and MbnErr published separately with namespace</li>
        <li>19.09.2019 - minor changes and optimisations in Mbn.calc()</li>
        <li>18.09.2019 - added Mbn.check() - check and get list of used variables <strong>(1.45)</strong></li>
        <li>18.09.2019 - fixed JS bug for variable named "hasOwnProperty" passed to Mbn.calc()</li>
        <li>24.05.2019 - fixed PHP split bug for mixed positive/negative parts (since 26.02.2019)
            <strong>(1.44)</strong>
            [php]
        </li>
        <li>23.05.2019 - fixed JS split bug for mixed positive/negative parts (since 26.02.2019) <strong>(1.44)</strong>
            [js]
        </li>
        <li>26.02.2019 - allow split with positive and negative parts <strong>(1.43)</strong></li>
        <li>05.02.2019 - added MbnL - digit limit <strong>(1.42)</strong></li>
        <li>05.02.2019 - fixed Mbn.calc() (%!) vs unary operator operator order (since 09.01.2019)</li>
        <li>22.01.2019 - PHP: added formatting with Mbn* params <strong>(1.41)</strong> [php]</li>
        <li>22.01.2019 - fixed PHP factorial - Mbn instead of static (since 09.01.2019)</li>
        <li>21.01.2019 - fixed (%!) operation order (since 09.01.2019)</li>
        <li>18.01.2019 - PHP: Mbn.prop() checks Mbn* params</li>
        <li>09.01.2019 - PHP: factorial</li>
        <li>08.01.2019 - JS: added formatting with Mbn* params <strong>(1.41)</strong> [js]</li>
        <li>08.01.2019 - JS: factorial</li>
        <li>08.08.2018 - minor changes <strong>(1.40)</strong></li>
        <li>03.04.2018 - added @return and @throws annotations<strong>(1.39)</strong></li>
        <li>03.04.2018 - allow constants starting with lower case</li>
        <li>22.03.2018 - fixed PHP that toString() wasn't public (__toString() was public)</li>
        <li>11.03.2018 - PHP: tri-state MbnE, Mbn.calc("==5") is simply parsed as string <strong>(1.38)</strong> [php]
        </li>
        <li>09.03.2018 - JS: tri-state MbnE, Mbn.calc("==5") is simply parsed as string <strong>(1.38)</strong> [js]
        </li>
        <li>07.03.2018 - allow Mbn.calc("=4") <strong>(1.37)</strong></li>
        <li>07.03.2018 - fixed errors for MbnE=false <strong>(1.36)</strong></li>
    </ul>

    <div class="title2">Other methods</div>

    <div class="title2" id="other_methods_split">Array methods - split - split value into an array</div>
    <div>Value can be split into array preserving sum of values, result is array of Mbn objects</div>
    <ul>
        <li>splitting into given number of parts
            <ul>
                <li>
                    <span class="monoInline">(new Mbn(3)).split()</span> gives array <span class="monoInline">[1.50, 1.50]</span>
                    <span class="monoInline">(new Mbn(4)).split(3)</span> gives array <span class="monoInline">[1.33, 1.34, 1.33]</span>
                    (or similar)
                </li>
            </ul>
        </li>
        <li>splitting into given proportions
            <ul>
                <li>
                    <span class="monoInline">(new Mbn(4.5)).split([1, 2])</span> gives array <span class="monoInline">[1.50, 3.00]</span>
                </li>
                <li>
                    <span class="monoInline">(new Mbn(4.5)).split([-1, -2])</span> gives array <span class="monoInline">[1.50, 3.00]</span>
                </li>
                <li>
                    proportion can have mixed signs, so <span class="monoInline">(new Mbn(4.5)).split([-1, 2])</span>
                    gives array <span class="monoInline">[-4.50, 9.00]</span><br>
                    hint: sum of proportions cannot be zero
                </li>
                <li>PHP: array of proportions can be associative, so <span class="monoInline">(new Mbn(4.5)).split(['a' => 1, 'b' => 2])</span>
                    gives array <span class="monoInline">['a' => 1.50, 'b' => 3.00]</span></li>
            </ul>
        </li>
    </ul>

    <div class="title2" id="other_methods_reduce">Array methods - reduce - map or reduce array</div>
    <div>Array can be mapped or reduced with one of Mbn methods<br>PHP: array can be associative</div>
    <ul>
        <li>one-argument functions: abs, inva, invm, ceil, floor, sqrt, round, sgn, intp, fact
            <ul>
                <li>result is mapped array, so <span class="monoInline">Mbn.reduce("abs", [1, -2])</span> gives array
                    <span class="monoInline">[1.00, 2.00]</span></li>
                <li>PHP: <span class="monoInline">Mbn::reduce("abs", ['a' => 1, 'b' => -2])</span> gives array
                    <span class="monoInline">['a' => 1.00, 'b' => 2.00]</span></li>
                <li>"set" may be used as a function, to create array of Mbn objects without any other actions</li>
            </ul>
        </li>
        <li>two-argument functions: add, sub, mul, div, mod, min, max, pow
            <ul>
                <li>two-argument function and single value gives mapped array
                    <ul>
                        <li><span class="monoInline">Mbn.reduce("pow", [3, 4, 5], 2)</span> gives array <span
                               class="monoInline">[3^2, 4^2, 5^2]</span> &rarr; <span class="monoInline">[9.00, 16.00, 25.00]</span>
                        </li>
                        <li><span class="monoInline">Mbn.reduce("pow", 2, [3, 4, 5])</span> gives array <span
                               class="monoInline">[2^3, 2^4, 2^5]</span> &rarr; <span class="monoInline">[8.00, 16.00, 32.00]</span>
                        </li>
                    </ul>
                </li>
                <li>two-argument function and two arrays give mapped array
                    <ul>
                        <li><span class="monoInline">Mbn.reduce("mul", [3, 4, 5], [1, 2, 3])</span> gives array <span
                               class="monoInline">[3*1, 4*2, 5*3]</span> &rarr; <span class="monoInline">[3.00, 8.00, 15.00]</span>
                            <br>
                            hint: arrays have to have the same length
                            PHP: hint: associative arrays have to have identical keys
                        </li>
                    </ul>
                </li>
                <li>two-argument function and one array reduces array
                    <ul>
                        <li><span class="monoInline">Mbn.reduce("mul", [3, 4, 5])</span> gives 3*4*5 = 60.00<br>
                        <li>asymmetric functions work identical, not really useful: <span class="monoInline">Mbn.reduce("sub", [3, 4, 5])</span>
                            gives 3-4-5 = -6.00
                        <li>for empty array returns 0.00</li>
                    </ul>
                </li>
            </ul>
        </li>
    </ul>


    <script>
        w("Other methods - calc", "title2");

        we(['//string value can be evaluated with library', 'Mbn.calc("2 + 2 * 2");']);

        w();
        we(['//standard operators work typically, also with power evaluated right-to-left', 'Mbn.calc("3 ^ 3 ^ 3") + " " + Mbn.calc("(3 ^ 3) ^ 3");']);

        w();
        we(['//it is posible, to use percentage values', 'Mbn.calc("200 * 123%");']);

        w();
        we(['//modulo uses # operator', 'Mbn.calc("245 # 100");']);

        w();
        we(['//min and max use & and | symbols, and therefore work like logical operators or/and on 0/1 values', 'Mbn.calc("(1 | 0) & 0");']);

        w();
        w(['//operator priorities high to low (in partenthesis with the same priority): ^, (*, /, #), (+, -), &amp;, |'], "mono");

        w();
        we(['//single argument functions abs, ceil, floor, round, sqrt, sgn, int (=intp) are accesible', 'Mbn.calc("((sqrt(5) + 1) / 2)^2");']);

        w();
        we(['//there are 3 standard constants: PI, E (with 40 digits precission) and eps (epsilon, distance to next number)', 'Mbn5t.calc("PI");']);
        we(['Mbn5t.calc("eps");']);

        w();
        we(["//variables can be passed as second argument", 'Mbn.calc("a / b", {a: 7, b: 3});']);
        w(["//php", "Mbn::calc('a / b', ['a' => 7, 'b' => 3]);"], "mono");

        w();
        we(["//calc() is called when constructor is called with string begnning with =", 'new Mbn("=x*x", {x: 2});']);

        w("Defining constants", "title2");

        we(['//constants can be get by name', 'Mbn.def("PI");']);

        w();
        we(['//constants can be defined, have to start from letter or _', 'Mbn.def("Q", "2");', 'Mbn.def("Q");']);

        w();
        we(['//accessing to undefined constants and redefinition of defined throws exception', 'Mbn.def("Q", "2");']);

        w();
        we(['//constant can be checked if is defined', 'Mbn.def(null, "Q");']);

        w("Other methods - check", "title2");
        we(['//incorrect expressions return false', 'Mbn.check("a * b *");']);

        w();
        we(['//correct expressions return list of used vars, also already defined - not needed', 'Mbn.check("a * b * PI");']);

        w("Examples of calculations, that give wrong results, and can be easily corrected with Mbn", "title2");

        we("(1.4 - 0.4) === 1;");

        we("(new Mbn(1.4)).sub(0.4).eq(1);");

        w();
        we(["//correct in IE", "(315.5 * 1.23).toFixed(2);"]);

        we('(new Mbn(315.5)).mul(1.23);');

        w();
        we(["//correct in IE", "(13492105 / 1000).toFixed(2);"]);

        we('(new Mbn(13492105)).div(1000);');

    </script>
</div>
</body>
</html>
