<?php
set_time_limit(1);
$q = (filter_input(INPUT_GET, 'text') !== null) ? filter_input(INPUT_GET, 'text') : filter_input(INPUT_POST, 'text');
if ($q !== null) {
   header('Content-Type: text/plain');
   require_once 'release/mbn.min.php';
   try {
      die(Mbn::calc($q ?: '0'));
   } catch (Exception $e) {
      die($e->getMessage());
   }
}
?><!DOCTYPE html>
<head>
   <title>MbnCalc</title>
   <link rel="icon" href="index.php?gf=icon" type="image/bmp" />
   <meta charset="UTF-8">
   <meta name=viewport content="width=device-width, initial-scale=1">
</head>
<body style="margin:2px;">
   <script src="release/mbn.min.js"></script>
   <div style="border:2px solid green; max-width:512px; margin-left:auto; margin-right:auto; padding:2px;">
      <a href="https://mirkl.es"><img src="https://mirkl.es/favicon.ico" style="float:left; margin:-4px 2px 0px -4px"/></a>
      <div style="float:right; border: 1px solid black;">
         <button style="background-color:lightgray; cursor:pointer; border:none; padding:1px; font-size:1em;" onclick="pchange(0, true);" id="mbnst"></button>
         <button style="background-color:lightgray; cursor:pointer; border:none; padding:1px; font-size:1em;" onclick="pchange(-1);" >&lt;</button>
         <button style="background-color:white; border:none; padding:1px; font-size:1em; width:30px;" id="mbnp" disabled></button>
         <button style="background-color:lightgray; cursor:pointer; border:none; padding:1px; font-size:1em;" onclick="pchange(1);">&gt;</button>
         <button style="background-color:lightgray; cursor:pointer; border: none; padding:1px; font-size:1em;" onclick="newcalc()">+</button>
      </div>
      <div style="margin:2px;"><a href="lib" style="color:black">Mbn</a>.calc / constants: PI, E, eps</div>
      <div style="margin:2px;">functions: abs, ceil, floor, round, sqrt, sgn, int</div>
      <input onkeyup="inchange(this);" id="in" style="display: block; width:100%; box-sizing: border-box">
      =>
      <input readonly id="out" style="display: block; width:100%; box-sizing: border-box" onfocus="this.select();">
   </div>
   <script>
      var MbnP = new (Mbn.extend(0))(2);
      var MbnSTs = [".0", ",0", "._", ",_"];
      var MbnST = MbnSTs[0];
      var Mbnx;
      var lastIn = null;
      var out = document.getElementById("out");
      var vars = {
      };
      var inchange = function (el) {
         var currIn = el.value + "|" + MbnP + "|" + MbnST;
         if (lastIn === currIn) {
            return;
         }
         if (el.value !== "") {
            try {
               out.value = Mbnx.calc(el.value, vars);
               out.style.color = "black";
               lastIn = currIn;
            } catch (e) {
               out.value = e;
               out.style.color = "firebrick";
               lastIn = null;
            }
         } else {
            out.value = "";
            lastIn = null;
         }
      };
      var pchange = function (d, a) {
         if (a === true) {
            MbnST = MbnSTs[(MbnSTs.indexOf(MbnST) + 1) % MbnSTs.length];
         }
         if (MbnP.add(d, true).eq(-1)) {
            MbnP.sub(d, true);
            return;
         }
         Mbnx = Mbn.extend({MbnP: MbnP.toNumber(), MbnS: MbnST.charAt(0), MbnT: MbnST.charAt(1) === "_"});
         document.getElementById("mbnp").innerText = MbnP;
         document.getElementById("mbnst").innerText = MbnST;
         document.getElementById("in").focus();
         document.getElementById("in").onkeyup();
      };
      var newcalc = function () {
         window.open(location.href, 'w' + (new Date()), 'width=320,height=128,resizable=yes,toolbar=no,scrollbars=no');
      };
      window.onload = function () {
         pchange(0);
      };
   </script>
</body>
