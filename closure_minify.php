<?php
   $originalJs = file_get_contents('mbn.js');

   function getMinified($js, $json, $info = 'compiled_code') {
      $postfields = array(
          'js_code' => $js,
          'compilation_level' => 'SIMPLE_OPTIMIZATIONS',
          'output_format' => $json ? 'json' : 'text',
          'output_info' => $info,
          'warning_level' => 'VERBOSE',
      );

      foreach ($postfields as $field => &$postfield) {
         $postfield = $field . '=' . rawurlencode($postfield);
      }
      unset($postfield);
      $postfieldsStr = implode('&', $postfields);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'http://closure-compiler.appspot.com/compile');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $postfieldsStr);
      $resp = curl_exec($ch);
      curl_close($ch);
      return $resp;
   }

   $jsmin = getMinified($originalJs, false);


?><!DOCTYPE html>
<head>
   <title>Mbn minify</title>
   <meta charset="UTF-8">
</head><body>
<html>
   <body>
      <pre><?php echo htmlspecialchars($jsmin); ?></pre>
   </body>
</html></body>