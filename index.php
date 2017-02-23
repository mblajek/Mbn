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
.title3{
   font-size: 1.2em;
   /*font-weight: bold;*/
   border-left: 20px solid gray;
   border-radius: 0px;
   xborder-right: 2px solid gray;
}
</style>

<script>

function w(a, c){
	if(a instanceof Array){	
		a = a.join("<br>");
	}
	document.write("<div" + (c ? (" class=\"" + c + "\"") : "") + ">" + a + "</div>");
}

function we(a){
	var acode = (a instanceof Array) ?  a.join("\n") : a;
	w(a, "mono");
	try {
		var e = eval(acode);
		w(e, "result");
		w(typeof e, "label");
	} catch (er) {
		w(er, "result");
		w("error", "label");
	}
		
}

//var Mbn3 = MbnCr(3);


w("Mbn examples (JS v" + Mbn.prop().MbnV + " / PHP v" + versionPHP + ")", "title1");
w("Tests", "title2");
w("<strong>PHP<br>" + ((typeof testPHP === "number") ? ("OK: " + testPHP + " tests") : testPHP) +
   "</strong><br>" + worktimePHP + " ms", "mono");
w("<strong>JS<br>" + ((typeof testJS === "number") ? ("OK: " + testJS + " tests") : testJS) +
   "</strong><br>" + worktimeJS + " ms", "mono");

w("Class declarations", "title2");

w(["//default: precission 2, dot separator, without trimming zeros", "//class allready defined in library", "//var Mbn = MbnCr();"], "mono");
we('new Mbn("12.1");');

var Mbn0 = MbnCr(0);
w(["//precission 0", "var Mbn0 = MbnCr(0);"], "mono");
we('new Mbn0("12.2");');

var Mbn3 = MbnCr(3);
w(['//precission 3', 'var Mbn3 = MbnCr(3);'], "mono");
we('new Mbn3("12.1");');

var Mbn4c = MbnCr({MbnP: 4, MbnS: ","});
w(['//precission 4, coma separator', 'var Mbn4c = MbnCr({MbnP: 4, MbnS: ","});'], "mono");
we('new Mbn4c("12.1");');

var Mbn5t = MbnCr({MbnP: 5, MbnT: true});
w(['//precission 5, trim zeros', 'var Mbn5t = MbnCr({MbnP: 5, MbnT: true});'], "mono");
we('new Mbn5t("12.1");');

w("Constructor calls", "title2");

we(["//empty", 'new Mbn();']);

we(["//number", 'new Mbn(1.2);']);

we(["//string with dot", 'new Mbn("1.2");']);

we(['//string with coma', 'new Mbn("1,2");']);

we(['//string without fractional part', 'new Mbn("1.");']);

we(['//string without integer part', 'new Mbn(".2");']);

we(['//another Mbn object', 'new Mbn(new Mbn("1,2"));']);

we(['//another Mbn class object (any object convertible to string)', 'new Mbn4c(new Mbn("1,2"));']);

we(['//called as funcion, calls itself as constructor', 'Mbn(4);']);


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

we("(13492105 / 1000).toFixed(2)");

we('new Mbn(new Mbn3("13492105").div("1000"))');

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