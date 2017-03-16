<?php

class Mbn0 extends Mbn{
   protected static $MbnX;
   protected static $MbnP = 0;
}

class Mbn3c extends Mbn{
   protected static $MbnX;
   protected static $MbnP = 3;
   protected static $MbnS = ',';
}

class Mbn20u extends Mbn{
   protected static $MbnX;
   protected static $MbnP = 20;
   protected static $MbnS = ',';
   protected static $MbnT = true;
}

function testMbn ($nl  = '<br>' ) {
   $MbnErr = "Mbn error";
   function runTestMbn  ($tests, $nl) {
      $MbnErr = "Mbn error";
      $ret = '';
      $tl = count($tests);
      for ($i = 0; $i < $tl; $i++) {
         $test = $tests[$i];
         $err = 0;
         try {
            $o = '';
            eval('$o = ' . $test[0] . ';');
            if ($o === true) {
               $o = 'true';
            } else if ($o === false) {
               $o = 'false';
            } else if (is_array($o)) {
               $o = implode(',', $o);
            }
            $evv = (string)$o;
         } catch (Exception $s) {
            $evv = (get_class($s) === 'MbnErr') ? $MbnErr : $s->getMessage();
         }

         $req = $test[1];

         if (($req === false && $err !== 1) || $evv !== $req) {
            if ($ret !== "") {
               $ret .= $nl . $nl;
            }
            $ret .= "ERR " . $i . ":" . $nl . $test[0] . $nl;
            $ret .= "! " . $req . $nl . "= " . $evv;
         }
      }
      return ($ret === "") ? count($tests) : $ret;

   }


   $tests = [["0;", "0"]];

   $tests[] = ['new Mbn(null);', '0.00'];
   $tests[] = ['new Mbn(true);', '1.00'];
   $tests[] = ['new Mbn(false);', '0.00'];

   $tests[] = ['new Mbn0();', '0'];
   $tests[] = ['new Mbn0("1.4");', '1'];
   $tests[] = ['new Mbn0("1.5");', '2'];
   $tests[] = ['new Mbn0(1.6)', '2'];
   $tests[] = ['new Mbn0("-1.4")', '-1'];
   $tests[] = ['new Mbn0("-1.5")', '-2'];
   $tests[] = ['new Mbn0(-1.6)', '-2'];

   $tests[] = ['new Mbn0("-  1.6")', '-2'];
   $tests[] = ['new Mbn0(" - 1.6")', '-2'];
   $tests[] = ['new Mbn0("  -1.6")', '-2'];
   $tests[] = ['new Mbn0(" - 1.6 ")', '-2'];
   $tests[] = ['new Mbn0(" - 1. ")', '-1'];
   $tests[] = ['new Mbn0(" - .6 ")', '-1'];
   $tests[] = ['new Mbn0(" + .6 ")', '1'];

   $tests[] = ['new Mbn()', '0.00'];
   $tests[] = ['new Mbn("1.234")', '1.23'];
   $tests[] = ['new Mbn("1.235")', '1.24'];
   $tests[] = ['new Mbn(1.236)', '1.24'];
   $tests[] = ['new Mbn("-1.234")', '-1.23'];
   $tests[] = ['new Mbn("-1.235")', '-1.24'];
   $tests[] = ['new Mbn(-1.236)', '-1.24'];

   $tests[] = ['new Mbn("-  1.6")', '-1.60'];
   $tests[] = ['new Mbn(" - 1.6")', '-1.60'];
   $tests[] = ['new Mbn("  -1.6")', '-1.60'];
   $tests[] = ['new Mbn(" - 1.6 ")', '-1.60'];
   $tests[] = ['new Mbn(" - 1. ")', '-1.00'];
   $tests[] = ['new Mbn(" - .6 ")', '-0.60'];
   $tests[] = ['new Mbn(" + .6 ")', '0.60'];

   $tests[] = ['new Mbn20u()', '0'];
   $tests[] = ['new Mbn20u("0,000000000000000000005")', '0,00000000000000000001'];
   $tests[] = ['new Mbn20u("-0,000000000000000000005")', '-0,00000000000000000001'];

   $tests[] = ['new Mbn0(new Mbn(1.495))', '2'];
   $tests[] = ['new Mbn0(new Mbn(-1.495))', '-2'];

   $tests[] = ['new Mbn3c(new Mbn20u("0,999499999999999999994"))', '0,999'];
   $tests[] = ['new Mbn3c(new Mbn20u("0,999499999999999999995"))', '1,000'];
   $tests[] = ['new Mbn3c(new Mbn20u("-0,999499999999999999994"))', '-0,999'];
   $tests[] = ['new Mbn3c(new Mbn20u("-0,999499999999999999995"))', '-1,000'];
   $tests[] = ['new Mbn20u(new Mbn20u("4,5"))', '4,5'];
   $tests[] = ['new Mbn0(new Mbn20u("4,5"))', '5'];

   $tests[] = ['(new Mbn0(-1))->cmp(1)', '-1'];
   $tests[] = ['(new Mbn0(-1))->cmp(0)', '-1'];
   $tests[] = ['(new Mbn0(0))->cmp(1)', '-1'];
   $tests[] = ['(new Mbn0(-1))->cmp(-1)', '0'];
   $tests[] = ['(new Mbn0(0))->cmp(0)', '0'];
   $tests[] = ['(new Mbn0(1))->cmp(1)', '0'];
   $tests[] = ['(new Mbn0(1))->cmp(-1)', '1'];
   $tests[] = ['(new Mbn0(1))->cmp(0)', '1'];
   $tests[] = ['(new Mbn0(0))->cmp(-1)', '1'];

   $tests[] = ['(new Mbn0(-1))->eq(1)', 'false'];
   $tests[] = ['(new Mbn0(1))->eq(1)', 'true'];
   $tests[] = ['(new Mbn0(1))->eq(-1)', 'false'];

   $tests[] = ['(new Mbn0(4))->add(3)', '7'];
   $tests[] = ['(new Mbn0(4))->add(-3)', '1'];
   $tests[] = ['(new Mbn0(-4))->add(3)', '-1'];
   $tests[] = ['(new Mbn0(-4))->add(-3)', '-7'];
   $tests[] = ['(new Mbn0(3))->add(4)', '7'];
   $tests[] = ['(new Mbn0(3))->add(-4)', '-1'];
   $tests[] = ['(new Mbn0(-3))->add(4)', '1'];
   $tests[] = ['(new Mbn0(-3))->add(-4)', '-7'];
   $tests[] = ['(new Mbn0(3))->add(-3)', '0'];
   $tests[] = ['(new Mbn0(-3))->add(3)', '0'];

   $tests[] = ['(new Mbn0(4))->sub(3)', '1'];
   $tests[] = ['(new Mbn0(4))->sub(-3)', '7'];
   $tests[] = ['(new Mbn0(-4))->sub(3)', '-7'];
   $tests[] = ['(new Mbn0(-4))->sub(-3)', '-1'];
   $tests[] = ['(new Mbn0(3))->sub(4)', '-1'];
   $tests[] = ['(new Mbn0(3))->sub(-4)', '7'];
   $tests[] = ['(new Mbn0(-3))->sub(4)', '-7'];
   $tests[] = ['(new Mbn0(-3))->sub(-4)', '1'];
   $tests[] = ['(new Mbn0(3))->sub(3)', '0'];
   $tests[] = ['(new Mbn0(-3))->sub(-3)', '0'];

   $tests[] = ['(new Mbn0(3))->add(0)', '3'];
   $tests[] = ['(new Mbn0(-3))->add(0)', '-3'];
   $tests[] = ['(new Mbn0(0))->add(3)', '3'];
   $tests[] = ['(new Mbn0(0))->add(-3)', '-3'];
   $tests[] = ['(new Mbn0(3))->sub(0)', '3'];
   $tests[] = ['(new Mbn0(-3))->sub(0)', '-3'];
   $tests[] = ['(new Mbn0(0))->sub(3)', '-3'];
   $tests[] = ['(new Mbn0(0))->sub(-3)', '3'];

   $tests[] = ['(new Mbn0(4))->mul(3)', '12'];
   $tests[] = ['(new Mbn0(4))->mul(-3)', '-12'];
   $tests[] = ['(new Mbn0(-4))->mul(3)', '-12'];
   $tests[] = ['(new Mbn0(-4))->mul(-3)', '12'];
   $tests[] = ['(new Mbn0(3))->mul(4)', '12'];
   $tests[] = ['(new Mbn0(3))->mul(-4)', '-12'];
   $tests[] = ['(new Mbn0(-3))->mul(4)', '-12'];
   $tests[] = ['(new Mbn0(-3))->mul(-4)', '12'];

   $tests[] = ['(new Mbn0(4))->div(3)', '1'];
   $tests[] = ['(new Mbn0(4))->div(-3)', '-1'];
   $tests[] = ['(new Mbn0(-4))->div(3)', '-1'];
   $tests[] = ['(new Mbn0(-4))->div(-3)', '1'];
   $tests[] = ['(new Mbn0(3))->div(4)', '1'];
   $tests[] = ['(new Mbn0(3))->div(-4)', '-1'];
   $tests[] = ['(new Mbn0(-3))->div(4)', '-1'];
   $tests[] = ['(new Mbn0(-3))->div(-4)', '1'];

   $tests[] = ['(new Mbn0(5))->div(3)', '2'];
   $tests[] = ['(new Mbn0(5))->div(-3)', '-2'];
   $tests[] = ['(new Mbn0(-5))->div(3)', '-2'];
   $tests[] = ['(new Mbn0(-5))->div(-3)', '2'];
   $tests[] = ['(new Mbn0(2))->div(5)', '0'];
   $tests[] = ['(new Mbn0(2))->div(-5)', '0'];
   $tests[] = ['(new Mbn0(-2))->div(5)', '0'];
   $tests[] = ['(new Mbn0(-2))->div(-5)', '0'];

   $tests[] = ['(new Mbn0(3))->mul(0)', '0'];
   $tests[] = ['(new Mbn0(-3))->mul(0)', '0'];
   $tests[] = ['(new Mbn0(0))->mul(3)', '0'];
   $tests[] = ['(new Mbn0(0))->mul(-3)', '0'];
   $tests[] = ['(new Mbn0(3))->div(0)', $MbnErr];
   $tests[] = ['(new Mbn0(-3))->div(0)', $MbnErr];
   $tests[] = ['(new Mbn0(0))->div(3)', '0'];
   $tests[] = ['(new Mbn0(0))->div(-3)', '0'];

   $tests[] = ['(new Mbn0(1))->isInt()', 'true'];
   $tests[] = ['(new Mbn0(1))->round()', '1'];
   $tests[] = ['(new Mbn0(1))->floor()', '1'];
   $tests[] = ['(new Mbn0(1))->ceil()', '1'];

   $tests[] = ['(new Mbn("0.22"))->add("0.33")', '0.55'];
   $tests[] = ['(new Mbn("0.22"))->sub("0.33")', '-0.11'];
   $tests[] = ['(new Mbn("0.22"))->mul("0.33")', '0.07'];
   $tests[] = ['(new Mbn("0.22"))->div("0.33")', '0.67'];

   $tests[] = ['(new Mbn("0.22"))->add("-0.22")', '0.00'];
   $tests[] = ['(new Mbn("0.28"))->sub("0.28")', '0.00'];
   $tests[] = ['(new Mbn("0.08"))->mul("0.09")', '0.01'];
   $tests[] = ['(new Mbn("-0.02"))->mul("0.03")', '0.00'];
   $tests[] = ['(new Mbn("0.05"))->div("10")', '0.01'];
   $tests[] = ['(new Mbn("0.06"))->div("-20")', '0.00'];

   $tests[] = ['(new Mbn3c("1.1"))->inva()', '-1,100'];
   $tests[] = ['(new Mbn3c("0"))->inva()', '0,000'];
   $tests[] = ['(new Mbn3c("-1.1"))->inva()', '1,100'];

   $tests[] = ['(new Mbn3c("1.1"))->invm()','0,909'];
   $tests[] = ['(new Mbn3c("0"))->invm()', $MbnErr];
   $tests[] = ['(new Mbn3c("-1.1"))->invm()', '-0,909'];

   $tests[] = ['(new Mbn3c("1.1"))->abs()', '1,100'];
   $tests[] = ['(new Mbn3c("0"))->abs()', '0,000'];
   $tests[] = ['(new Mbn3c("-1.1"))->abs()', '1,100'];

   $tests[] = ['(new Mbn("0.4"))->floor()', '0.00'];
   $tests[] = ['(new Mbn("0.5"))->floor()', '0.00'];
   $tests[] = ['(new Mbn("0.4"))->ceil()', '1.00'];
   $tests[] = ['(new Mbn("0.5"))->ceil()', '1.00'];
   $tests[] = ['(new Mbn("0.4"))->round()', '0.00'];
   $tests[] = ['(new Mbn("0.49"))->round()', '0.00'];
   $tests[] = ['(new Mbn("0.5"))->round()', '1.00'];

   $tests[] = ['(new Mbn("-0.4"))->floor()', '-1.00'];
   $tests[] = ['(new Mbn("-0.5"))->floor()', '-1.00'];
   $tests[] = ['(new Mbn("-0.4"))->ceil()', '0.00'];
   $tests[] = ['(new Mbn("-0.5"))->ceil()', '0.00'];
   $tests[] = ['(new Mbn("-0.4"))->round()', '0.00'];
   $tests[] = ['(new Mbn("-0.5"))->round()', '-1.00'];

   $tests[] = ['(new Mbn("1"))->isInt()', 'true'];
   $tests[] = ['(new Mbn("1"))->isInt()', 'true'];
   $tests[] = ['(new Mbn("0.005"))->isInt()', 'false'];
   $tests[] = ['(new Mbn("-0.005"))->isInt()', 'false'];
   $tests[] = ['(new Mbn("0"))->isInt()', 'true'];

   $tests[] = ['(new Mbn20u("21,25"))->toNumber()', '21.25'];
   $tests[] = ['(new Mbn20u("-21,5"))->toNumber()', '-21.5'];

   $tests[] = ['(new Mbn3c("21.3"))->eq("21.3")', 'true'];
   $tests[] = ['(new Mbn3c("-21.3"))->eq("21.3")', 'false'];
   $tests[] = ['(new Mbn3c("21.3"))->eq("21.4")', 'false'];
   $tests[] = ['(new Mbn3c("21.4"))->eq("21.2")', 'false'];
   $tests[] = ['(new Mbn3c("21.3"))->eq("21.4", "0.1")', 'true'];
   $tests[] = ['(new Mbn3c("21.3"))->eq("21.2", "0.1")', 'true'];
   $tests[] = ['(new Mbn3c("21.3"))->eq("21.1", "0.1")', 'false'];
   $tests[] = ['(new Mbn3c("21.3"))->eq("21.5", "0.1")', 'false'];

   $tests[] = ['(new Mbn3c("21.3"))->cmp("21.3")', '0'];
   $tests[] = ['(new Mbn3c("-21.3"))->cmp("21.3")', '-1'];
   $tests[] = ['(new Mbn3c("21.3"))->cmp("21.4")', '-1'];
   $tests[] = ['(new Mbn3c("21.4"))->cmp("21.2")', '1'];
   $tests[] = ['(new Mbn3c("21.3"))->cmp("21.4", "0.1")', '0'];
   $tests[] = ['(new Mbn3c("21.3"))->cmp("21.2", "0.1")', '0'];
   $tests[] = ['(new Mbn3c("21.3"))->cmp("21.1", "0.1")', '1'];
   $tests[] = ['(new Mbn3c("21.3"))->cmp("21.5", "0.1")', '-1'];

   $tests[] = ['(new Mbn3c("0.1"))->eq("0.3", "0.2")', 'true'];
   $tests[] = ['(new Mbn3c("0.1"))->eq("-0.1", "0.2")', 'true'];
   $tests[] = ['(new Mbn3c("0.1"))->eq("0", "0.2")', 'true'];
   $tests[] = ['(new Mbn3c("0.1"))->eq("0.4", "0.2")', 'false'];
   $tests[] = ['(new Mbn3c("0.1"))->eq("-0.2", "0.2")', 'false'];

   $tests[] = ['(new Mbn0("2"))->pow("5")', '32'];
   $tests[] = ['(new Mbn0("2"))->pow("-5")', '0'];
   $tests[] = ['(new Mbn0("3"))->pow("3")', '27'];
   $tests[] = ['(new Mbn0("3"))->pow("-3")', '0'];
   $tests[] = ['(new Mbn(.5))->pow(7)', '0.01'];

   $tests[] = ['(new Mbn3c("2"))->pow("5")', '32,000'];
   $tests[] = ['(new Mbn3c("2"))->pow("-5")', '0,031'];
   $tests[] = ['(new Mbn3c("1.1"))->pow("4")', '1,464'];
   $tests[] = ['(new Mbn3c("1.1"))->pow("-4")', '0,683'];

   $tests[] = ['(new Mbn("2"))->sqrt()', '1.41'];
   $tests[] = ['(new Mbn3c("2"))->sqrt()', '1,414'];
   $tests[] = ['(new Mbn20u("2"))->sqrt()', '1,4142135623730950488'];
   $tests[] = ['(new Mbn20u("3"))->sqrt()', '1,73205080756887729353'];
   $tests[] = ['(new Mbn20u("4"))->sqrt()', '2'];

   $tests[] = ['$m = new Mbn("4.32"); $m->add(1.23); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->add(1.23, true); $o=$m', '5.55'];
   $tests[] = ['$m = new Mbn("4.32"); $m->sub(1.23); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->sub(1.23, true); $o=$m', '3.09'];
   $tests[] = ['$m = new Mbn("4.32"); $m->mul(1.23); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->mul(1.23, true); $o=$m', '5.31'];
   $tests[] = ['$m = new Mbn("4.32"); $m->div(1.23); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->div(1.23, true); $o=$m', '3.51'];
   $tests[] = ['$m = new Mbn("4.32"); $m->mod(1.23); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->mod(1.23, true); $o=$m', '0.63'];
   $tests[] = ['$m = new Mbn("4.32"); $m->pow(2); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->pow(2, true); $o=$m', '18.66'];
   $tests[] = ['$m = new Mbn("4.32"); $m->sqrt(); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->sqrt(true); $o=$m', '2.08'];
   $tests[] = ['$m = new Mbn("4.32"); $m->cmp("3"); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->cmp("3", "2"); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->eq("3"); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->eq("3", "2"); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->isInt(); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->inva(); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->inva(true); $o=$m', '-4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->invm(); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("4.32"); $m->invm(true); $o=$m', '0.23'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->abs(); $o=$m', '-4.32'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->abs(true); $o=$m', '4.32'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->intp(); $o=$m', '-4.32'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->intp(true); $o=$m', '-4.00'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->floor(); $o=$m', '-4.32'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->floor(true); $o=$m', '-5.00'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->ceil(); $o=$m', '-4.32'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->ceil(true); $o=$m', '-4.00'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->round(); $o=$m', '-4.32'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->round(true); $o=$m', '-4.00'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->mod(3); $o=$m', '-4.32'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->mod(3, true); $o=$m', '-1.32'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->set(3); $o=$m', '3.00'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->sgn(); $o=$m', '-4.32'];
   $tests[] = ['$m = new Mbn("-4.32"); $m->sgn(true); $o=$m', '-1.00'];
   
   $tests[] = ['(new Mbn("3"))->split([1,3])', '0.75,2.25'];
   $tests[] = ['(new Mbn("3"))->split([2,3])', '1.20,1.80'];
   $tests[] = ['(new Mbn("3"))->split([3,3])', '1.50,1.50'];
   $tests[] = ['(new Mbn("3"))->split([1,2,3])', '0.50,1.00,1.50'];
   $tests[] = ['(new Mbn("2"))->split([1,1,1])', '0.67,0.67,0.66'];
   $tests[] = ['(new Mbn("100"))->split([100,23])', '81.30,18.70'];
   $tests[] = ['(new Mbn("42"))->split()', '21.00,21.00'];
   $tests[] = ['(new Mbn("42"))->split(5)', '8.40,8.40,8.40,8.40,8.40'];
   
   $tests[] = ['(new Mbn3c("1.234"))->mod("0.401")', '0,031'];
   $tests[] = ['(new Mbn3c("3.234"))->mod("1")', '0,234'];
   $tests[] = ['(new Mbn3c("1.234"))->mod("-0.401")', '0,031'];
   $tests[] = ['(new Mbn3c("3.234"))->mod("-1")', '0,234'];
   $tests[] = ['(new Mbn3c("-1.234"))->mod("0.401")', '-0,031'];
   $tests[] = ['(new Mbn3c("-3.234"))->mod("1")', '-0,234'];
   $tests[] = ['(new Mbn3c("2.234"))->mod("4")', '2,234'];

   $tests[] = ['(new Mbn3c("2.123"))->intp()', '2,000'];
   $tests[] = ['(new Mbn3c("3.987"))->intp()', '3,000'];
   $tests[] = ['(new Mbn3c("-4.123"))->intp()', '-4,000'];
   $tests[] = ['(new Mbn3c("-5.987"))->intp()', '-5,000'];
   $tests[] = ['(new Mbn3c("0"))->intp()', '0,000'];

   $tests[] = ['(new Mbn("-99.5"))->mod(100)', '-99.50'];
   $tests[] = ['(new Mbn("99.5"))->mod(100)', '99.50'];
   $tests[] = ['(new Mbn0("55"))->mod(10)', '5'];
   $tests[] = ['(new Mbn0("-55"))->mod(10)', '-5'];
   $tests[] = ['(new Mbn0("54"))->mod(10)', '4'];
   $tests[] = ['(new Mbn0("-54"))->mod(10)', '-4'];

   $tests[] = ['(new Mbn("-2"))->max(-3)', '-2.00'];
   $tests[] = ['(new Mbn("-3"))->max(-2)', '-2.00'];
   $tests[] = ['(new Mbn("-2"))->max(3)', '3.00'];
   $tests[] = ['(new Mbn("3"))->max(-2)', '3.00'];
   $tests[] = ['(new Mbn("2"))->max(4)', '4.00'];
   $tests[] = ['(new Mbn("4"))->max(2)', '4.00'];
   $tests[] = ['(new Mbn("0"))->max(2)', '2.00'];
   $tests[] = ['(new Mbn("0"))->max(-2)', '0.00'];

   $tests[] = ['(new Mbn("-2"))->min(-3)', '-3.00'];
   $tests[] = ['(new Mbn("-3"))->min(-2)', '-3.00'];
   $tests[] = ['(new Mbn("-2"))->min(3)', '-2.00'];
   $tests[] = ['(new Mbn("3"))->min(-2)', '-2.00'];
   $tests[] = ['(new Mbn("2"))->min(4)', '2.00'];
   $tests[] = ['(new Mbn("4"))->min(2)', '2.00'];
   $tests[] = ['(new Mbn("0"))->min(2)', '0.00'];
   $tests[] = ['(new Mbn("0"))->min(-2)', '-2.00'];

   $tests[] = ['(new Mbn("0"))->set(-2)', '-2.00'];

   $tests[] = ['(new Mbn("0"))->sgn()', '0.00'];
   $tests[] = ['(new Mbn("-0.01"))->sgn()', '-1.00'];
   $tests[] = ['(new Mbn("0.03"))->sgn()', '1.00'];

   $tests[] = ['Mbn::reduce("add", [])', '0.00'];
   $tests[] = ['Mbn::reduce("add", [1,6,-2])', '5.00'];
   $tests[] = ['Mbn::reduce("mul", [1,6,-2])', '-12.00'];
   $tests[] = ['Mbn::reduce("inva", [1,6,-2])', '-1.00,-6.00,2.00'];
   $tests[] = ['Mbn::reduce("sgn", [1,6,-2])', '1.00,1.00,-1.00'];
   $tests[] = ['Mbn::reduce("sgn", [])', ''];

   /*tests.push(['Mbn.E()', '2.72']);
   tests.push(['Mbn0.E()', '3']);
   tests.push(['Mbn3c.E()', '2,718']);
   tests.push(['Mbn20u.E()', '2,71828182845904523536']);

   tests.push(['Mbn.PI()', '3.14']);
   tests.push(['Mbn0.PI()', '3']);
   tests.push(['Mbn3c.PI()', '3,142']);
   tests.push(['Mbn20u.PI()', '3,14159265358979323846']);

   tests.push(['Mbn.MbnP()', '2.00']);
   tests.push(['Mbn0.MbnP()', '0']);
   tests.push(['Mbn3c.MbnP()', '3,000']);
   tests.push(['Mbn20u.MbnP()', '20']);*/

   return runTestMbn($tests, $nl);
}
