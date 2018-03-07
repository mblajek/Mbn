<?php

if (!class_exists('Mbn')) {
   require_once 'mbn.php';
}

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

function testMbn() {

   function runTestMbn($tests) {
      $ret = array();
      $i = 0;
      foreach ($tests as $test) {
         list($raw, $req, $exp) = $test;
         try {
            $o = '';
            eval($exp);
            if ($o === true) {
               $o = 'true';
            } elseif ($o === false) {
               $o = 'false';
            } elseif (is_array($o)) {
               $o = implode(',', $o);
            }
            $evv = strval($o);
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
            $ret [] = array(
                'id' => $i,
                'raw' => $raw,
                'code' => $exp,
                'correct' => $req,
                'incorrect' => $evv
            );
         }
         $i++;
      }
      return array(
          'status' => (count($ret) === 0) ? 'OK' : 'ERR',
          'count' => $i,
          'errors' => $ret
      );
   }

   $testsAll = json_decode(file_get_contents('mbn_test_set.json'));
   $tests = array_merge($testsAll->both, $testsAll->php);
   foreach ($tests as &$test) {
      $tst = $test[0];
      $jsonA = [];
      while (preg_match('/{[^}]*}/', $tst, $jsonA) === 1) {
         $json = preg_replace('/([a-z]+):/i', '"$1":', $jsonA[0]);
         $jsonArr = var_export(json_decode($json, true), true);
         $tst = str_replace($jsonA[0], $jsonArr, $tst);
         break;
      }
      $expArr = explode('; ', $tst);
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
