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
         list($raw, $req, $exp) = $test;
         $i++;
         try {
            $o = '';
            eval($exp);
            if ($o === true) {
               $o = 'true';
            } else if ($o === false) {
               $o = 'false';
            } else if (is_array($o)) {
               $o = implode(',', $o);
            }
            $evv = strval($o);
         } catch (Exception $s) {
            $evv = $s->getMessage();
         }

         if ($req[strlen($req) - 1] === '*') {
            $cmpn = strlen($req) - 1;
         } else {
            $cmpn = strlen($req) + strlen($evv);
         }

         if (strncmp($evv, $req, $cmpn) !== 0) {
            $ret [] = array(
                'id' => $i,
                'raw' => $raw,
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
   foreach($tests as &$test) {
      $expArr = explode('; ', $test[0]);
      $expArr[count($expArr) - 1] = '$o = ' . $expArr[count($expArr) - 1] . ';';
      $test[2] = implode('; ', $expArr);
   }
   unset($test);

   $starttimePHP = microtime(true);
   $testPHP = runTestMbn($tests);
   $testPHP['time'] = round((microtime(true) - $starttimePHP) * 1000);
   $testPHP['MbnV'] = Mbn::prop()['MbnV'];

   return json_encode($testPHP);
}
echo testMbn();
