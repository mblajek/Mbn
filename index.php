<?php
$relFiles = array(
    array(
        'mbn.js',
        'Library in JS'
    ),
    array(
        'mbn.php',
        'Library in PHP'
    ),
    array(
        'mbn.min.js',
        'Minified library in JS'
    ),
    array(
        'mbn.min.php',
        'Minified library in PHP'
    ),
    array(
        'mbn.slim.js',
        'Slim library version in JS - without pow(), calc(), reduce() and constants'
    ),
    array(
        'mbn.slim.php',
        'Slim library version in PHP - without pow(), calc(), reduce() and constants'
    ),
    array(
        'mbn.slim.min.js',
        'Minified slim library version in JS'
    ),
    array(
        'mbn.slim.min.php',
        'Minified slim library version in PHP'
    ),
);
foreach ($relFiles as &$relFile) {
   $relFile[] = filesize('release/' . $relFile[0]);
}
unset($relFile);

$getFile = filter_input(INPUT_GET, 'gf');
if ($getFile != null && isset($relFiles[$getFile])) {
   $fn = $relFiles[$getFile][0];
   $ext = pathinfo($fn, PATHINFO_EXTENSION);
   header('Content-Type: text/plain');
   header('Content-Disposition: inline; filename="' . $fn . '"');
   readfile('release/' . $fn);
   die;
} elseif ($getFile === 'icon') {
   header('Content-Type: image/bmp');
   echo gzinflate(base64_decode('c/KtY4AAOyDWAGIBKGYEQhBwAOLDfBCMDP7//w/EDAwNQGX//0Lw/rcMDPPPMjCsX8vAsD2XgWEdkD8XiFe9hfBB4iB5kDqQXgA='));
}

?><!DOCTYPE html>
<head>
   <title>Mbn Librabry</title>
   <meta charset="UTF-8">
   <link rel="icon" href="index.php?gf=icon" type="image/bmp" />
</head><body>
   <script src="mbn.js"></script>

   <style>
      div{
         font-family: "Arial";
         margin: 0px 4px 2px 4px;
         border-radius: 2px;
         padding: 2px 6px 2px 6px
      }
      .title1{
         font-size: 2em;
         font-weight: bold;
      }
      .title2{
         margin-top: 20px;
         font-size: 1.5em;
         font-weight: bold;
         border-left: 2px solid gray;
      }
      .mono{
         font-family: "Consolas", monospace;
         background-color: lightgray;
         border: 1px solid gray;
         white-space: pre-wrap;
      }
      .mono>span:after{
         color: gray;
         content: ">| ";
      }
      .result{
         font-family: "Consolas", monospace;
         border: 1px solid gray;
         display: inline-block;
         font-weight: bold;
         margin-right: 0px;
         border-radius: 2px 0px 0px 2px;
      }
      .label{
         font-family: "Consolas", monospace;
         border: 1px solid gray;
         display: inline-block;
         margin-left: 0px;
         border-radius: 0px 2px 2px 0px;
         border-left: 0px;
      }
      .title3{
         font-size: 1.2em;
         border-left: 20px solid gray;
         border-radius: 0px;
      }
   </style>
   <script>
      function w(a, c) {
         if (a === undefined) {
            a = "";
         } else if (a instanceof Array) {
            a = a.slice();
            var al = a.length;
            if(c === 'mono'){
               for (var i = 0; i < al; i++) {
                  a[i] = "<span></span>" + a[i];
               }
            }
            a = a.join("<br>");
         } else if(c === 'mono'){
            a = "<span></span>" + a;
         }
         document.write("<div" + (c ? (" class=\"" + c + "\"") : "") + ">" + a + "</div>");
      }

      function we(a) {
         w(a, "mono");
         try {
            var acode = (a instanceof Array) ? a.join("\n") : a;
            console.log(acode);
            var e = eval(acode);
            w(String(e), "result");
            w(typeof e, "label");
         } catch (er) {
            w(er, "result");
            w("error", "label");
         }

      }

      var modify = false;

      w("Mbn - Multi-Byte Number Librabry", "title1");

      w("About", "title2");
      w("Library for PHP and JS to do calculations with any precission and correct approximations.");

      w("Tests and benchmark", "title2");
      w('<strong id="resultJS">..</strong>', "mono");
      w('<strong id="resultPHP">..</strong>', "mono");

      w("Downloads", "title2");

      w("//tests are run only on mbn.php and mbn.js", "mono")
      w("//slim version are made by cutting out some code from source", "mono");
      w("//minified JS is made with <a href='http://closure-compiler.appspot.com'>Google Closure api</a>", "mono");
      w("//minified PHP is made with php_strip_whitespace() and text replacements", "mono");

      var relFiles = JSON.parse("<?php echo addslashes(json_encode($relFiles)); ?>");
      relFiles.forEach(function (f, i) {
         w(['<a href="?gf=' + i + '">' + f[0] + "</a> (" + (new Mbn(f[2])).div(1024) + " kB)", f[1]], "mono");
      });

      w("Class declarations in JS", "title2");

      w(["//default: precission 2, dot separator, without trimming zeros", "//class allready defined in library", "//var Mbn = Mbn.extend();"], "mono");
      we('new Mbn("12.1");');

      w();
      var Mbn0 = Mbn.extend(0);
      w(["//precission 0", "var Mbn0 = Mbn.extend(0);"], "mono");
      we('new Mbn0("12.2");');

      w();
      var Mbn3 = Mbn.extend(3);
      w(['//precission 3', 'var Mbn3 = Mbn.extend(3);'], "mono");
      we('new Mbn3("12.1");');

      w();
      var Mbn4c = Mbn.extend({MbnP: 4, MbnS: ","});
      w(['//precission 4, coma separator', 'var Mbn4c = Mbn.extend({MbnP: 4, MbnS: ","});'], "mono");
      we('new Mbn4c("12.1");');

      w();
      var Mbn5t = Mbn.extend({MbnP: 5, MbnT: true});
      w(['//precission 5, trim zeros', 'var Mbn5t = Mbn.extend({MbnP: 5, MbnT: true});'], "mono");
      we('new Mbn5t("12.1");');

      w("Class declarations in PHP", "title2");

      w(['class Mbn0 extends Mbn{', '  //needed in each declaration', '  protected static $MbnX;', '  protected static $MbnP = 0;', '}'], "mono");

      w();
      w(['class Mbn4c extends Mbn{', '  protected static $MbnX;', '  protected static $MbnP = 4;', "  protected static $MbnS = ',';", '}'], "mono");

      w();
      w(['class Mbn5t extends Mbn{', '  protected static $MbnX;', '  protected static $MbnP = 5;', "  protected static $MbnT = true;", '}'], "mono");

      w("Constructor calls", "title2");

      we(["//empty", 'new Mbn();']);

      w();
      we(["//number", 'new Mbn(1.2);']);

      w();
      we(["//boolean", 'new Mbn(true);']);

      w();
      we(["//string with dot", 'new Mbn("1.2");']);

      w();
      we(['//string with coma', 'new Mbn("1,2");']);

      w();
      we(['//string without fractional part', 'new Mbn("1.");']);

      w();
      we(['//string without integer part', 'new Mbn(".2");']);

      w();
      we(['//another Mbn object', 'new Mbn(new Mbn("1,2"));']);

      w();
      we(['//another Mbn class object (any object convertible to numeric string)', 'new Mbn4c(new Mbn("1,2"));']);

      w();
      we(['//called as funcion, calls itself as constructor (JS only)', 'Mbn(4);']);

      w('Mbn behaviour is similar to string', "title2");

      we('new Mbn("1,2") + "txt";');

      w();
      we('new Mbn("1,2") + new Mbn("1,2");');

      w();
      we('new Mbn("1,2") + 2;');

      w('Conversion to string and number', "title2");

      we(['//correct, same as (new Mbn4c("1,2")).toString()', 'String(new Mbn4c("1,2"));']);

      w();
      we(['//incorrect for coma separator, same as Number("1,2000")', 'Number(new Mbn4c("1,2"));']);

      w();
      we(['//correct', '(new Mbn4c("1,2")).toNumber();']);

      w('Operator precedence difference between JS and PHP', "title2");

      w(['//correct in JS', 'new Mbn("1.12").toNumber();'], "mono");

      w();
      w(['//incorrect in PHP', 'new Mbn("1.12")->toNumber();'], "mono");

      w();
      w(['//correct in PHP', '(new Mbn("1.12"))->toNumber();'], "mono");

      w('Standard rules for operations', "title2");

      we(['//all numbers are rounded with half-up rule', 'new Mbn("1.125");']);

      w();
      we('new Mbn0("-1.5");');

      w();
      we(['//all numeric arguments converted to Mbn', 'new Mbn("1.125").add("1.125");']);
      we(['//because', 'new Mbn("1.13").add(new Mbn("1.13"));'], "mono");
      we(['//needed precission should be used', 'new Mbn(new Mbn3("1.125").add("1.125"));'], "mono");

      w();
      we(['//by default original value remains unchanged', 'var a = new Mbn("1.12");', 'a.add("1.12");', 'a;']);

      w();
      we(['//last argument (=== true) triggers modification of original variable', 'var a = new Mbn("1.12");', 'a.add("1.12", true);', 'a;']);

      w();
      we(['//returned values are Mbn objects, which allows method chaining', 'new Mbn("1.12").add("1.12").add("9");']);

      w();
      we(['var a = new Mbn("1.12");', 'a.add("1.12", true).add("9", true);', 'a;']);

      w();
      we(['//exceptions like wrong formats, division by zero and other are thrown', 'new Mbn("1.x12");', 'new Mbn("1.12");']);


      w('Basic methods, return number as Mbn object', "title2");

      we(['//add', 'new Mbn(5, modify).add(2);']);

      w();
      we(['//substract', 'new Mbn(5).sub(2, modify);']);

      w();
      we(['//multiply', 'new Mbn(5).mul(2, modify);']);

      w();
      we(['//divide', 'new Mbn(5).div(2, modify);']);

      w();
      we(['//modulo (result has same sign as the original number)', 'new Mbn(5).mod(-2.1, modify);']);

      w();
      we(['//power (integer exponent only)', 'new Mbn(5).pow(2, modify);']);

      w();
      we(['//square root', 'new Mbn(2).sqrt(modify);']);

      w();
      we(['//minimum', 'new Mbn(5).min(2, modify);']);

      w();
      we(['//maximum', 'new Mbn(5).max(2, modify);']);

      w();
      we(['//round', 'new Mbn(5.5).round(modify);']);

      w();
      we(['//ceiling', 'new Mbn(-5.6).ceil(modify);']);

      w();
      we(['//floor', 'new Mbn(-5.4).floor(modify);']);

      w();
      we(['//integer part of number', 'new Mbn(-5.6).intp(modify);']);

      w();
      we(['//absolute value', 'new Mbn(-5.4).abs(modify);']);

      w();
      we(['//additional inverse of number', 'new Mbn(5).inva(modify);']);

      w();
      we(['//multiplicative inverse', 'new Mbn(5).invm(modify);']);

      w();
      we(['//sign of number (-1, 0, 1)', 'new Mbn(0.5).sgn(modify);']);

      w("Other methods", "title2");

      we(['//set value', 'new Mbn(0.5).set(4);']);

      we(['//standard set, using =, sets reference to existing object', 'var a = new Mbn(2);', 'var b = new Mbn();', 'var c = new Mbn();', 'b = a;', 'c.set(a);', 'a.add(3, true);', 'b + " " + c;']);

      w();
      we(['//compare with other number, returns number', '//1 if number is greater than other value, 0 if equals, -1 if is lower', 'new Mbn(0.5).cmp(4);']);

      w();
      we(['//second argumend defines maximum difference still treated as equality', 'new Mbn(1.5).cmp(1.7, 0.2);']);

      w();
      we(['//check if numbers are equal, also maximum difference can be passed', 'new Mbn(1.9).eq(1.7, 0.2);']);

      w();
      we(['//split value to numbers, which sum correctly to it', '//returns array of Mbn objects', '//number of parts (default 2) or array with ratios can be given', 'new Mbn(3).split();']);

      we('new Mbn(3).split().join(" ");');

      we('new Mbn(5).split([1, 1, 2]).join(" ");');

      we('new Mbn(2.02).split([1, 1, 2]).join(" ");');

      w();
      w(['//in PHP works with assocjative arrays', "(new Mbn(2.02))->split(['a' => 1, 'c' => 1, 'b' => 2])", "//gives array ['a' => 0.51, 'c' => 0.50, 'b' => 1.01]"], 'mono');

      w("Other methods - reduce", "title2");
      we(['//reduce array to value or invoke single argument function on each element (typically called map)', '//2-argument functions: add, mul, min, max', 'Mbn.reduce("add", [2.5, 1.5, 3.4, -4.4]);']);

      w();
      we(['//1-argument functions: set (simply make array of Mbn objects), abs, inva, invm, ceil, floor, sqrt, round, sgn, intp', 'Mbn.reduce("set", [2.5, 1.5, 3.4, -4.4]);']);
      we(['Mbn.reduce("inva", [2.5, 1.5, 3.4, -4.4]).join(" ");']);

      w();
      w(["//in PHP works with assocjative arrays", "Mbn::reduce('sqrt', ['a'=>4, 'b'=>9])", "//gives array ['a'=>2.00, 'b'=>3.00]"], "mono");

      w("Other methods - calc", "title2");

      w();
      we(['//string value can be evaluated with library', 'Mbn.calc("2 + 2 * 2");']);

      w();
      we(['//standard operators work typically, also with power evaluated right-to-left', 'Mbn.calc("3 ^ 3 ^ 3") + " " + Mbn.calc("(3 ^ 3) ^ 3");']);

      w();
      we(['//it is posible, to use percentage values', 'Mbn.calc("200 * 123%");']);

      w();
      we(['//modulo has # operator', 'Mbn.calc("245 # 100");']);

      w();
      we(['//min and max use & and | symbols, and therefore work like logical operators or/and on 0/1 values', 'Mbn.calc("(1 | 0) & 0");']);

      w();
      w(['//operator priorities high to low (in partenthesis with the same priority): ^, (*, /, #), (+, -), &, |'], "mono");

      w();
      we(['//single argument functions abs, ceil, floor, round, sqrt, sgn, int (=intp) are accesible', 'Mbn.calc("((sqrt(5) + 1) / 2)^2");']);

      w();
      we(['//there are 3 standard constants: PI, E (with 40 digits precission) and MbnP', 'Mbn5t.calc("PI");']);
      we(['Mbn5t.calc("10^-MbnP");']);

      w();
      we(["//variables can be passed as second argument", 'Mbn.calc("a / b", {a: 7, b: 3});']);

      w();
      we(["//calc() is called when constructor is called with string begnning with =", 'new Mbn("=x*x", {x: 2});'])

      w("Defining constants", "title2");

      we(['//constants can be get by name', 'Mbn.def("PI");'])

      w();
      we(['//constants can be defined, have to start from capital letter', 'Mbn.def("Q", "2");', 'Mbn.def("Q");'])

      w();
      we(['//accessing to undefined constants and redefinition of defined throws exception', 'Mbn.def("Q", "2");'])

      w();
      we(['//constant can be checked if is defined', 'Mbn.def(null, "Q");'])

      w("Examples of calculations, that give wrong results, and can be easily corrected with Mbn", "title2");

      we("(1.4 - 0.4) === 1;");

      we("new Mbn(1.4).sub(0.4).eq(1);");

      w();
      we(["//correct in IE", "(315.5 * 1.23).toFixed(2);"]);

      we('new Mbn(315.5).mul(1.23);');

      w();
      we(["//correct in IE", "(13492105 / 1000).toFixed(2);"]);

      we('new Mbn(13492105).div(1000);');

   </script>
   <script src="mbn_test.js"></script>
   <script>
      setTimeout(function () {
         function displayTestStatus(lng, result) {
            var res = JSON.parse(result);
            var txt = lng + " v" + res.MbnV + ": " + res.status + " (" + res.count + " tests, " + res.time + " ms)";
            for (var i = 0; i < res.errors.length; i++) {
               var error = res.errors[i];
               txt += "\n\n" + error.id + ") " + error.code + "\n!) " + error.correct + "\n=) " + error.incorrect;
            }
            document.getElementById("result" + lng).innerText = txt;
         }

         var xmlhttp = new XMLHttpRequest();
         xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState === 4) {
               displayTestStatus("PHP", xmlhttp.responseText);
            }
         };
         xmlhttp.open("POST", "mbn_test.php", true);
         xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
         testMbn(function (responseText) {
            displayTestStatus("JS", responseText);
            xmlhttp.send("");
         });
      }, 100);
   </script>
</body>