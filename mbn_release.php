<?php

function releaseMbn() {
   $err = [];

   if (!is_dir('release')) {
      if (!mkdir('release')) {
         return 'cannot create release folder';
      }
   }
   $oldHash = null;
   if (file_exists('release/v')) {
      $oldHash = json_decode(file_get_contents('release/v'))->hash;
   }

   $mbn_js = file_get_contents('mbn.js');
   $mbn_php = file_get_contents('mbn.php');

   $newHash = hash('sha256', $mbn_js . $mbn_php);

   if ($oldHash === $newHash) {
      return 'already up-to-date';
   }

   function checkMinifyJS(&$errors, $code) {
      $postfields = [
          'js_code' => $code,
          'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
          'output_format' => 'json',
          'output_info[1]' => 'compiled_code',
          'output_info[2]' => 'warnings',
          'output_info[3]' => 'errors',
          'warning_level' => 'verbose',
      ];

      foreach ($postfields as $field => &$postfield) {
         $postfield = preg_replace('/\\[\d+\\]$/', '', $field) . '=' . rawurlencode($postfield);
      }
      unset($postfield);
      $postfieldsStr = implode('&', $postfields);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://closure-compiler.appspot.com/compile');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postfieldsStr);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      $resp = json_decode(curl_exec($ch));
      $curlErr = curl_error($ch);
      curl_close($ch);
      if (empty($resp)) {
         throw new Exception('empty closure-compiler response ' . $curlErr);
      }
      $jsErrors = [];
      if (!empty($resp->errors)) {
         $jsErrors = array_merge($jsErrors, $resp->errors);
      }
      if (!empty($resp->warnings)) {
         $jsErrors = array_merge($jsErrors, $resp->warnings);
      }
      foreach ($jsErrors as $err) {
         $errors [] = [
             'place' => 'mbn.js:' . $err->lineno . ':' . $err->charno,
             'line' => $err->line,
             'type' => $err->type,
             'message' => isset($err->error) ? $err->error : $err->warning
         ];
      }

      return empty($resp->compiledCode) ? '' : $resp->compiledCode;
   }

   function minifyCheckPHP(&$errors) {
      $mbn_min_php = php_strip_whitespace('mbn.php');
      $ll = 500;
      $lineLen = 0;
      $mbn_min_php_out = '';
      $len = strlen($mbn_min_php);
      $stString = false;
      $stBack = false;
      $stSpace = false;
      $c0 = '';
      for ($i = 0; $i < $len; $i++) {
         $c = $mbn_min_php[$i];
         if ($stString && ($c === '\\') && !$stBack) {
            $stBack = true;
         } else {
            if ($c === '\'') {
               $stString = !$stString || $stBack;
            }
            $stBack = false;
         }
         if ($c === ' ') {
            if ($stString) {
               $mbn_min_php_out .= $c;
               $lineLen++;
            } else {
               if ($lineLen > $ll) {
                  $mbn_min_php_out .= PHP_EOL;
                  $lineLen = 0;
               } else {
                  $stSpace = true;
               }
            }
            continue;
         }
         if ($stSpace && preg_match('/\\w\\w/', $c0 . $c)) {
            if ($lineLen > $ll) {
               $mbn_min_php_out .= PHP_EOL;
               $lineLen = 0;
            } else {
               $mbn_min_php_out .= ' ';
               $lineLen++;
            }
         }
         $mbn_min_php_out .= $c;
         $lineLen++;
         $c0 = $c;
         $stSpace = false;
      }
      $tempFile = 'release/mbn.min.php.temp';
      file_put_contents($tempFile, $mbn_min_php_out);
      //test mbn.min.php
      require_once $tempFile;
      unlink($tempFile);
      ob_start();
      require_once 'mbn_test.php';
      $minPhpObj = json_decode(ob_get_clean());
      if ($minPhpObj->status !== 'OK') {
         foreach ($minPhpObj->errors as $error) {
            $errors[] = [$error->id . ') ' => $error->raw,
                '!) ' => $error->correct,
                '=) ' => $error->incorrect];
         }
      }
      return $mbn_min_php_out;
   }

   $errors = [];

   try {
      $mbn_min_js = checkMinifyJS($errors, $mbn_js);
      $mbn_min_php = minifyCheckPHP($errors);
   } catch (Exception $e) {
      return $e->getMessage();
   }

   if (!empty($errors)) {
      $errStr = '';
      foreach ($errors as $err) {
         foreach ($err as $errc => $errl) {
            $errStr .= $errc . ' => ' . trim($errl) . PHP_EOL;
         }
         $errStr .= PHP_EOL;
      }
      return $errStr;
   }

   function getVersion($code) {
      $varr = [];
      preg_match('/MbnV = [\'"]([\d\.]+)[\'"];/', $code, $varr);
      return 'v' . (isset($varr[1]) ? $varr[1] : '');
   }

   $license = '/* Mbn {V} | https://mirkl.es/n/lib | Copyright (c) 2016-' . date('Y')
           . ' Mikołaj Błajek | https://github.com/mblajek/Mbn/blob/master/LICENSE.txt */' . PHP_EOL;

   $versionJs = getVersion($mbn_js);
   $versionPhp = getVersion($mbn_php);

   $licenseJs = str_replace('{V}', $versionJs, $license);
   $licensePhp = '<?php ' . str_replace('{V}', $versionPhp, $license);

   file_put_contents('release/mbn.php', preg_replace('/^\<\?php\s*/i', $licensePhp, $mbn_php));
   file_put_contents('release/mbn.min.php', preg_replace('/^\<\?php\s*/i', $licensePhp, $mbn_min_php));

   file_put_contents('release/mbn.js', preg_replace('/^\s*/i', $licenseJs, $mbn_js));
   file_put_contents('release/mbn.min.js', preg_replace('/^\s*/i', $licenseJs, $mbn_min_js));

   $mbn_d_ts = file_get_contents('mbn.d.ts');
   file_put_contents('release/mbn.d.ts', preg_replace('/^\s*/i', $licenseJs, $mbn_d_ts));

   file_put_contents('release/v', json_encode([
       'mbn_js' => $versionJs,
       'mbn_php' => $versionPhp,
       'hash' => $newHash
   ]));

   return 'update finished: JS ' . $versionJs . ', PHP ' . $versionPhp;
}

echo releaseMbn();
