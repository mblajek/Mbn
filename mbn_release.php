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

   function getSlim($code) {
      return preg_replace('/SLIM_EXCLUDE_START.*SLIM_EXCLUDE_END/s', 'SLIM_EXCLUDED', $code);
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
      curl_setopt($ch, CURLOPT_URL, 'http://closure-compiler.appspot.com/compile');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postfieldsStr);
      $resp = json_decode(curl_exec($ch), true);
      curl_close($ch);

      if (!empty($resp['errors'])) {
         foreach ($resp['errors'] as $err) {
            $errorsJS [] = array(
                'type' => $err['type'],
                'line' => $err['line'],
                'place' => $file . ':' . $err['lineno'] . ':' . $err['charno'],
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
      for ($o = $ll; $o < $mbn_min_phpLen; $o += $ll) {
         $o = strpos($mbn_min_php, ' ', $o);
         if ($o === false) {
            break;
         }
         $mbn_min_php[$o] = PHP_EOL;
      }

      $mbn_min_phpLenNew = strlen($mbn_min_php);
      do {
         $mbn_min_php = preg_replace('/(.) ([^\\w\\$\'])/', '$1$2', $mbn_min_php);
         $mbn_min_php = preg_replace('/([^\\w\\$\':]) (.)/', '$1$2', $mbn_min_php);
         $mbn_min_phpLen = $mbn_min_phpLenNew;
         $mbn_min_phpLenNew = strlen($mbn_min_php);
      } while ($mbn_min_phpLenNew < $mbn_min_phpLen);
      return $mbn_min_php;
   }

   $errorsJS = array();

   $mbn_slim_js = getSlim($mbn_js);
   $mbn_min_js = checkMinifyJS($errorsJS, 'mbn.js', $mbn_js);
   $mbn_slim_min_js = checkMinifyJS($errorsJS, 'mbn.slim.js', $mbn_slim_js);

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

   $mbn_slim_php = getSlim($mbn_php);

   file_put_contents('release/mbn.php', $mbn_php);
   file_put_contents('release/mbn.slim.php', $mbn_slim_php);

   $mbn_min_php = minifyPHP('release/mbn.php');
   $mbn_slim_min_php = minifyPHP('release/mbn.slim.php');

   file_put_contents('release/mbn.min.php', $mbn_min_php);
   file_put_contents('release/mbn.slim.min.php', $mbn_slim_min_php);

   file_put_contents('release/mbn.js', $mbn_js);
   file_put_contents('release/mbn.slim.js', $mbn_slim_js);
   file_put_contents('release/mbn.min.js', $mbn_min_js);
   file_put_contents('release/mbn.slim.min.js', $mbn_slim_min_js);

   file_put_contents('release/.LASTHASH', $newHash);

   return 'update finished';
}

?>

<!DOCTYPE html>
<head>
   <title>Mbn examples</title>
   <meta charset="UTF-8">
</head>
<body>
   <pre><?php echo releaseMbn(); ?></pre>
</body>