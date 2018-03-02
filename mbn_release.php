<?php

function releaseMbn() {
   $err = array();

   if (!is_dir('release')) {
      if (!mkdir('release')) {
         return 'cannot create release folder';
      }
   }
   $oldHash = null;
   if (file_exists('release/.LASTHASH')) {
      $oldHash = file_get_contents('release/.LASTHASH');
   }

   $mbn_js = file_get_contents('mbn.js');
   $mbn_php = file_get_contents('mbn.php');

   $newHash = hash('sha256', $mbn_js . $mbn_php);

   if ($oldHash === $newHash) {
      return 'already up-to-date';
   }

   function checkMinifyJS(&$errorsJS, $file, $code = null) {
      if ($code === null) {
         $code = file_get_contents($file);
      }
      $postfields = array(
          'js_code' => $code,
          'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
          'output_format' => 'json',
          'output_info[1]' => 'compiled_code',
          'output_info[2]' => 'warnings',
          'output_info[3]' => 'errors',
          'warning_level' => 'verbose',
      );

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
      $resp = json_decode(curl_exec($ch), true);
      $curlErr = curl_error($ch);
      curl_close($ch);
      if (empty($resp)) {
         throw new Exception('empty closure-compiler response ' . $curlErr);
      }

      if (!empty($resp['errors'])) {
         foreach ($resp['errors'] as $err) {
            $errorsJS [] = array(
                'place' => $file . ':' . $err['lineno'] . ':' . $err['charno'],
                'line' => $err['line'],
                'type' => $err['type'],
                'message' => $err['error']
            );
         }
      }
      if (!empty($resp['warnings'])) {
         foreach ($resp['warnings'] as $err) {
            $errorsJS [] = array(
                'type' => $err['type'],
                'line' => $err['line'],
                'place' => $file . ':' . $err['lineno'] . ':' . $err['charno'],
                'message' => $err['warning']
            );
         }
      }

      if (!empty($resp['compiledCode'])) {
         return $resp['compiledCode'];
      }
      return '';
   }

   function minifyPHP($file) {
      $mbn_min_php = php_strip_whitespace($file);
      $ll = 600;
      $mbn_min_phpLen = strlen($mbn_min_php);
      $o = $ll;
      for (; $o < $mbn_min_phpLen; $o += $ll) {
         $o = strpos($mbn_min_php, ' ', $o);
         if ($o === false) {
            break;
         }
         $mbn_min_php[$o] = PHP_EOL;
      }

      $mbn_min_phpLenNew = strlen($mbn_min_php);
      do {
         $mbn_min_php0 = preg_replace('/([^ ]) ([^\\w\\$\' ])/', '$1$2', $mbn_min_php);
         $mbn_min_php = preg_replace('/([^\\w\\$\': ]) ([^ ])/', '$1$2', $mbn_min_php0);
         $mbn_min_phpLen = $mbn_min_phpLenNew;
         $mbn_min_phpLenNew = strlen($mbn_min_php);
      } while ($mbn_min_phpLenNew < $mbn_min_phpLen);
      return $mbn_min_php;
   }

   $errorsJS = array();

   try {
      $mbn_min_js = checkMinifyJS($errorsJS, 'mbn.js', $mbn_js);
   } catch (Exception $e) {
      return $e->getMessage();
   }

   if (!empty($errorsJS)) {
      $errJsStr = '';
      foreach ($errorsJS as $err) {
         foreach ($err as $errc => $errl) {
            $errJsStr .= $errc . ' => ' . trim($errl) . PHP_EOL;
         }
         $errJsStr .= PHP_EOL;
      }
      return $errJsStr;
   }

   function getVersion($code) {
      $varr = [];
      preg_match('/MbnV = [\'"]([\d\.]+)[\'"];/', $code, $varr);
      return isset($varr[1]) ? $varr[1] : '';
   }

   require_once 'mbn.php';
   $license = '/* Mbn v{V} | Copyright (c) 2016-' . date('Y')
           . ' Mikołaj Błajek | https://github.com/mblajek/Mbn/blob/master/LICENSE.txt */' . PHP_EOL;

   $versionJs = getVersion($mbn_js);
   $versionPhp = getVersion($mbn_php);

   $licenseJs = str_replace('{V}', $versionJs, $license);
   $licensePhp = '<?php ' . str_replace('{V}', $versionPhp, $license);

   file_put_contents('release/mbn.php', preg_replace('/^\<\?php\s+/i', $licensePhp, $mbn_php));
   $mbn_min_php = minifyPHP('release/mbn.php');
   file_put_contents('release/mbn.min.php', preg_replace('/^\<\?php\s+/i', $licensePhp, $mbn_min_php));

   file_put_contents('release/mbn.js', preg_replace('/^\s+/i', $licenseJs, $mbn_js));
   file_put_contents('release/mbn.min.js', preg_replace('/^\s+/i', $licenseJs, $mbn_min_js));

   file_put_contents('release/.LASTHASH', $newHash);

   return 'update finished: JS v' . $versionJs . ', PHP v' . $versionPhp;
}

echo releaseMbn();
