<?php

require_once 'mbn.php';

class Mbn0 extends Mbn {

   protected static $MbnX;
   protected static $MbnP = 0;

}

class Mbn3c extends Mbn {

   protected static $MbnX;
   protected static $MbnP = 3;
   protected static $MbnS = ',';

}

class Mbn20u extends Mbn {

   protected static $MbnX;
   protected static $MbnP = 20;
   protected static $MbnS = ',';
   protected static $MbnT = true;

}

function testMbn() {

   function runTestMbn($tests) {
      $ret = array();
      $i = 0;
      foreach ($tests as $test) {
         list($exp, $req) = $test;
         $i++;
         try {
            $o = '';
            eval('$o = ' . $exp . ';');
            if ($o === true) {
               $o = 'true';
            } else if ($o === false) {
               $o = 'false';
            } else if (is_array($o)) {
               $o = implode(',', $o);
            }
            $evv = (string) $o;
         } catch (Exception $s) {
            $evv = $s->getMessage();
         }

         if (strrpos($req, '*')) {
            $cmpn = strpos($req, '*');
         } else {
            $cmpn = strlen($req) + strlen($evv);
         }

         if (strncmp($evv, $req, $cmpn)) {
            $ret [] = array(
                'id' => $i,
                'code' => $exp,
                'correct' => $req,
                'incorrect' => $evv
            );
         }
      }
      return array(
          'status' => (count($ret) === 0) ? 'OK' : 'ERR',
          'count' => $i,
          'errors' => $ret
      );
   }
   $testsAll = json_decode(file_get_contents('mbn_test_set.json'));
   $tests = array_merge($testsAll->php, $testsAll->both);

   $starttimePHP = microtime(true);
   $testPHP = runTestMbn($tests);
   $testPHP['time'] = round((microtime(true) - $starttimePHP) * 1000);
   $testPHP['MbnV'] = Mbn::prop()['MbnV'];

   return json_encode($testPHP);
}
echo testMbn();
