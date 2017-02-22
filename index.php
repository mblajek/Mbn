<!DOCTYPE html>
<head>
   <title>Mbn examples</title>
   <meta charset="UTF-8">
</head><body>
<?php
include 'mbn.php';
include 'mbn_test.php';
$starttimePHP = microtime(true);
$testPHP = testMbn();
$worktimePHP = round((microtime(true) - $starttimePHP) * 1000);
$versionPHP = Mbn::prop()['MbnV'];
?>
<script src="mbn.js"></script>
<script src="mbn_test.js"></script>
<script>
var testPHP = <?php echo is_int($testPHP) ? $testPHP : '"' . (addslashes($testPHP) . '"') ?>;
var worktimePHP = <?php echo $worktimePHP ?>;
var versionPHP = <?php echo $versionPHP ?>;
var starttimeJS = new Date();
var testJS = testMbn();
var worktimeJS = new Date() - starttimeJS;
</script>

<style>
div{
   min-height: 1em;
   margin: 0px 4px 0px 4px;
   border-radius: 2px;
   padding: 2px 6px 2px 6px
   
}
.title1{
   font-size: 2em;
   font-weight: bold;
}
.title2{
   margin-top: 20px;
   margin-bottom: 2px;
   font-size: 1.5em;
   font-weight: bold;
   border-left: 2px solid gray;
}
.mono{
   margin-bottom: 2px;
   font-family: "Consolas", monospace;
   background-color: lightgray;
   border: 1px solid gray;
}
.result{
   margin-bottom: 8px;
   font-family: "Consolas", monospace;
   border: 1px solid gray;
   display: inline-block;
   font-weight: bold;
   margin-right: 0px;
   border-radius: 2px 0px 0px 2px;
}
.label{
   margin-bottom: 8px;
   font-family: "Consolas", monospace;
   border: 1px solid gray;
   display: inline-block;
   margin-left: 0px;
   border-radius: 0px 2px 2px 0px;
   border-left: 0px;
}
</style>

<script>

function w(a, c){
	document.write("<div" + (c ? (" class=\"" + c + "\"") : "") + ">" + a + "</div>");
}

function we(a){
	w(a, "mono");
	var e = eval(a);
	w(e, "result");
	w(typeof e, "label");
}

//var Mbn3 = MbnCr(3);


w("Mbn examples (JS v" + Mbn.prop().MbnV + " / PHP v" + versionPHP + ")", "title1");
w("Tests", "title2");
w("<strong>PHP<br>" + ((typeof testPHP === "number") ? ("OK: " + testPHP + " tests") : testPHP) +
   "</strong><br>" + worktimePHP + " ms", "mono");
w("<strong>JS<br>" + ((typeof testJS === "number") ? ("OK: " + testJS + " tests") : testJS) +
   "</strong><br>" + worktimeJS + " ms", "mono");

w("Class declarations", "title2");

var Mbn0 = MbnCr(0);
w("var Mbn0 = MbnCr(0); //precission 0", "mono");
we('new Mbn0("12.12");');

var Mbn3 = MbnCr(3);
w('var Mbn3 = MbnCr(3); //precission 3', "mono");
we('new Mbn3("12.12");');

var Mbn4c = MbnCr({MbnP: 4, MbnS: ","});
w("var Mbn4c = MbnCr({MbnP: 4, MbnS: \",\"}); //precission 4, coma separator", "mono");
we('new Mbn4c("12.12");');

var Mbn5t = MbnCr({MbnP: 5, MbnT: true});
w("var Mbn5t = MbnCr({MbnP: 5, MbnT: true}); //precission 5, trim zeros", "mono");
we('new Mbn5t("12.12");');

w("Constructor calls", "title2");

we('new Mbn(); //empty');

we('new Mbn(1.2); //number');

we('new Mbn("1.2"); //string with dot');

we('new Mbn("1,2"); //string with coma');

we('new Mbn("1."); //string without fractional part');

we('new Mbn(".2"); //string without integer part');

we('new Mbn(new Mbn("1,2")); //another Mbn object');

we('new Mbn4c(new Mbn("1,2")); //another Mbn class object (convertible to string)');

we("Mbn(4); //called as funcion, calls itself as constructor");


w('// Przy działaniach identyczne zachowanie jak string "1.20", również dla $("#id").val(new Mbn(1.2));');
w("");

we('(new Mbn("1,2")) + "txt";');

we('(new Mbn("1,2")) + 2;');

we('(new Mbn("1,2")) * 2;');

we('Number(new Mbn("1,2")) + 2;');

we('(new Mbn("1,2")).toNumber() + 2;');

we('new Mbn("1.125");');

we('new Mbn("-1.125");');

w("var Mbn3 = MbnCr(3);");
w("");

we('new Mbn3("1.1255");');

we('(new Mbn("1,2")).inva();');

we('(new Mbn("1,2")).add("1.3");');

we('(new Mbn("1,2")).sub("1.3");');

we('(new Mbn("1,2")).mul("1.3");');

we('(new Mbn("1,2")).mul("1.3").inva();');

we('(new Mbn("1,2")).mul("1.3").mul("0,01");');

we('(new Mbn("1,2")).mul("1.3").mul("0,01").mul("100");');

we('(new Mbn("1,2")).mul("1.3").mul("100").mul("0,01");');

we('(new Mbn("1,2")).mul("1.3").div("0,01").div("100");');

we('(new Mbn("1,2")).mul("1.3").div("100").div("0,01");');

we('(new Mbn("1,2")).mul("1.3").div("1,2");');

we('(new Mbn("1,2")).mul("1.3").div("1,3");');

we('(new Mbn("1,2")).mul("1.3").div("1.56");');

we('(new Mbn("1,2")).mul("1.3").div("0.156");');

we('(new Mbn("1,2")).mul("1.3").div("0.16");');

w("przyklądy dające złe wyniki w standardowym JS (poza IE)");
w("");

we("(315.5 + (315.5 * 0.23)).toFixed(2)");

we('(new Mbn("315,5")).add( (new Mbn("315,5")).mul(23).mul("0.01") );');

we('new Mbn("23").mul("0.01").add("1").mul("315,5");');

we("(3509 * (3.845 * 1000) / 1000).toFixed(2)");

we('new Mbn(new Mbn3("3509").mul("3.845"))');

w("// ostatni parametr - zmiana zmiennej");w("");

we('var a = new Mbn("0,2"); a.add("0,9");');

we('var a = new Mbn("0,2"); a.add("0,9"); a;');

we('var a = new Mbn("0,2"); a.add("0,9", true);');

we('var a = new Mbn("0,2"); a.add("0,9", true); a;');

we('var a = new Mbn("0,2"); a.add("0,9", true); a.inva(true); a.sub("-1.2", true); a;');

w("// porównania");w("");

we('(new Mbn("0.1")).cmp("0");');

we('(new Mbn("0.1")).cmp("0.1");');

we('(new Mbn("0.1")).cmp("0.2");');

we('(new Mbn("0,1")).eq(0.1);');

we('(new Mbn("0.1")).eq("0.2");');

</script></body>