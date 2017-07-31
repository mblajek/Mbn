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
      while (($jsonStart = strpos($tst, '{')) !== false) {
         $jsonLen = strpos($tst, '}', $jsonStart) - $jsonStart + 1;
         $json =  substr($tst, $jsonStart, $jsonLen);
         $jsonCor = preg_replace('/[a-z]+/i', '\'$0\'',$json);
         $jsonArr = str_replace(explode('|', '{|}|:'), explode('|', '[|]|=>'), $jsonCor);
         $tst = str_replace($json,$jsonArr , $tst);
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
