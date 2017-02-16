<!DOCTYPE html>
<head>
<title>Mbn examples</title>
<meta charset="UTF-8">
</head><body>

<?php
include 'mbn.php';
include 'mbn_test.php';
$starttime = microtime(true);
$testPhp = testMbn();
$worktime = round((microtime(true) - $starttime) * 1000);
?>

<script src="mbn.js"></script>
<script src="mbn_test.js"></script>
<pre><script>

var Mbn = MbnCr();
var Mbn3 = MbnCr(3);

function w(a){
	document.write(a);
	document.write("<br>");
}

function we(a){
	w(a);
	var e = eval(a);
	w("=> " + e);
	w("(" + (typeof e) + ")");
	w("");
}

var testPhp = <?php echo is_int($testPhp) ? $testPhp : '"' . (addslashes($testPhp) . '"') ?>;
var worktimePhp = <?php echo $worktime ?>;
w("<strong>PHP<br>" + ((typeof testPhp === "number") ? ("OK: " + testPhp + " tests") : testPhp) + "</strong>");
w(worktimePhp + " ms");
w("");
var starttime = new Date();
var testJs = testMbn();
var worktimeJs = new Date() - starttime;
w("<strong>JS<br>" + ((typeof testJs === "number") ? ("OK: " + testJs + " tests") : testJs) + "</strong>");
w(worktimeJs + " ms");
w("");
w("");

w("MultiByteNumber v" + Mbn.prop().MbnV);
w("Cyfry po przecinku: " + Mbn.prop().MbnP);
w("Separator dla wyników: " + Mbn.prop().MbnS);
w("");

w('// Możliwe wywołania konstruktora');
w("");

we('new Mbn();');

we('new Mbn(1.2);');

we('new Mbn("1.2");');

we('new Mbn("1,2");');

we('new Mbn(new Mbn("1,2"));');

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

</script></pre></body>