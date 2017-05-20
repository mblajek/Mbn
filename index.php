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
        'Slim library version in JS - without pow(), eval(), reduce() and constants'
    ),
    array(
        'mbn.slim.php',
        'Slim library version in PHP - without pow(), /*eval(),*/ reduce() and constants'
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
}

?><!DOCTYPE html>
<head>
   <title>Mbn examples</title>
   <meta charset="UTF-8">
   <link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAGUExURQAAAP///6XZn90AAAAxSURBVAgdY/zPwLhfj3G+HuP6f4zbtzGucmacG8C4zh7EBooAxYGy//+BUEMj438GAL6WE3n6ZaTTAAAAAElFTkSuQmCC" type="image/png" />
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
         white-space: pre;
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
            var al = a.length;
            for (var i = 0; i < al; i++) {
               //a[i] = a[i].replace(/^ +/g, "&nbsp;&nbsp;");
            }
            a = a.join("<br>");
         }
         document.write("<div" + (c ? (" class=\"" + c + "\"") : "") + ">" + a + "</div>");
      }

      function we(a) {
         var acode = (a instanceof Array) ? a.join("\n") : a;
         w(a, "mono");
         try {
            var e = eval(acode);
            w(String(e), "result");
            w(typeof e, "label");
         } catch (er) {
            w(er, "result");
            w("error", "label");
         }

      }

      var modify = false;

      w("Mbn examples", "title1");
      w("Tests and benchmark", "title2");
      w('<strong id="resultJS">..</strong>', "mono");
      w('<strong id="resultPHP">..</strong>', "mono");

      w("Downloads", "title2");

      w(["//tests are run only on mbn.php and mbn.js"], "mono");
      var relFiles = JSON.parse("<?php echo addslashes(json_encode($relFiles)); ?>");
      relFiles.forEach(function (f, i) {
         w(['<a href="?gf=' + i + '">' + f[0] + "</a> (" + (new Mbn(f[2])).div(1024) + " kB)", f[1]], "mono");
      });

      w("Class declarations in JS", "title2");

      w(["//default: precission 2, dot separator, without trimming zeros", "//class allready defined in library", "//var Mbn = MbnCr();"], "mono");
      we('new Mbn("12.1");');

      w();
      var Mbn0 = MbnCr(0);
      w(["//precission 0", "var Mbn0 = MbnCr(0);"], "mono");
      we('new Mbn0("12.2");');

      w();
      var Mbn3 = MbnCr(3);
      w(['//precission 3', 'var Mbn3 = MbnCr(3);'], "mono");
      we('new Mbn3("12.1");');

      w();
      var Mbn4c = MbnCr({MbnP: 4, MbnS: ","});
      w(['//precission 4, coma separator', 'var Mbn4c = MbnCr({MbnP: 4, MbnS: ","});'], "mono");
      we('new Mbn4c("12.1");');

      w();
      var Mbn5t = MbnCr({MbnP: 5, MbnT: true});
      w(['//precission 5, trim zeros', 'var Mbn5t = MbnCr({MbnP: 5, MbnT: true});'], "mono");
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
      we(['//another Mbn class object (any object convertible to string)', 'new Mbn4c(new Mbn("1,2"));']);

      w();
      we(['//called as funcion, calls itself as constructor', 'Mbn(4);']);

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

      w();
      we(['//because', 'new Mbn("1.125").add(1.125);', '//means', 'new Mbn("1.125").add(new Mbn("1.125"));', '//means', 'new Mbn("1.13").add(new Mbn("1.13"));'], "mono");

      w();
      we(['//by default original value remains unchanged', 'var a = new Mbn("1.12");', 'a.add("1.12");', 'a;']);

      w();
      we(['//last argument (=== true) triggers modification of original variable', 'var a = new Mbn("1.12");', 'a.add("1.12", true);', 'a;']);

      w();
      we(['//returned values are Mbn objects, which allows method chaining', 'new Mbn("1.12").add("1.12").add("9");']);

      w();
      we(['var a = new Mbn("1.12");', 'a.add("1.12", true).add("9", true);', 'a;']);

      w();
      we(['//this code does not make sense', 'var a = new Mbn("1.12");', 'var b = a.add("1.12").add("9", true);', 'a + " " + b;']);

      w();
      we(['//this code may be usefull, but is somewhat messy', 'var a = new Mbn("1.12");', 'var b = a.add("1.12", true).add("9");', 'a + " " + b;']);

      w();
      we(['//exceptions like wrong formats, division by zero and other are thrown', 'new Mbn("1.x12");', 'new Mbn("1.12");']);


      w('Basic methods, returning number as Mbn object', "title2");

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
      we(['//power (integer exponent)', 'new Mbn(5).pow(2, modify);']);

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
      we(['//second argumend defines maximum difference still treated as equality', 'new Mbn(0.5).cmp(0.7, 0.2);']);

      w();
      we(['//check if numbers are equal, also maximum difference can be passed', 'new Mbn(0.9).eq(0.7, 0.2);']);

      w();
      we(['//split value to numbers, which sum correctly to it, returns array', '//number of parts (default 2) or array with ratios can be given', 'new Mbn(3).split();']);

      we('new Mbn(3).split().join(" ");');

      we('new Mbn(5).split([1, 1, 2]).join(" ");');

      we('new Mbn(2.02).split([1, 1, 2]).join(" ");');

      //split








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

         displayTestStatus("JS", testMbn());

         var xmlhttp = new XMLHttpRequest();
         xmlhttp.onreadystatechange = function () {
            if (xmlhttp.readyState === 4) {
               displayTestStatus("PHP", xmlhttp.responseText);
            }
         };
         xmlhttp.open("POST", "mbn_test.php", true);
         xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
         xmlhttp.send("");
      }, 100);
   </script>
</body>