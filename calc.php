<!DOCTYPE html>
<head>
   <title>MbnCalc</title>
   <meta charset="UTF-8">
   <meta name=viewport content="width=device-width, initial-scale=1">
</head>
<body style="margin:2px;">
   <script src="release/mbn.min.js"></script>
   <div style="border:2px solid green; max-width:512px; margin-left:auto; margin-right:auto; padding:2px;">
      <div style="float:right; border: 1px solid black;">
         <div style="display:inline-block; background-color:lightgray; cursor:pointer;" onclick="pchange(0,true);" id="pst"></div>
         <div style="display:inline-block; background-color:lightgray; cursor:pointer;" onclick="pchange(-1);">&lt;</div>
         <div style="display:inline-block; width:30px; text-align: center;" id="op"></div>
         <div style="display:inline-block; background-color:lightgray; cursor:pointer;" onclick="pchange(+1);">&gt;</div>
         <div style="display:inline-block; background-color:lightgray; cursor:pointer;" onclick="window.open(location.href, 'w' + (new Date()), 'width=320,height=128,resizable=yes,toolbar=no,scrollbars=no');">+</div>
      </div>
      <div style="padding:1px;">constants: PI, E, <a href="index.php" style="color:black">Mbn</a>P</div>
      <div>functions: abs, ceil, floor, round, sqrt, sgn, int</div>
      <input onkeyup="inchange(this);" id="in" style="display: block; width:100%; box-sizing: border-box">
      =>
      <input readonly style="display: block; width:100%; box-sizing: border-box" onfocus="this.select();">
   </div>
   <script>
var MbnP = new (MbnCr(0))(2);
var MbnSTs = [".0", ",0", "._", ",_"];
var MbnST = MbnSTs[0];
var Mbnx;
var lastIn = null;
var vars = {
};
var inchange = function(el){
   var currIn = el.value + "|" + MbnP + "|" + MbnST;
   if(lastIn === currIn){
      return;
   }
   var n = el.nextElementSibling;
   if(el.value !== "") {
      try{
         n.value = Mbnx.calc(el.value, vars);
         n.style.color = "black";
         lastIn = currIn;
      }catch(e){
         n.value = e;
         n.style.color = "firebrick";
         lastIn = null;
      }
   } else {
      n.value = "";
      lastIn = null;
   }
};
var pchange = function(d, a){
   if(a === true){
      MbnST = MbnSTs[(MbnSTs.indexOf(MbnST) + 1) % MbnSTs.length];
   }
   if(MbnP.add(d, true).eq(-1)){
      MbnP.sub(d, true);
      return;
   }
   Mbnx = MbnCr({MbnP: MbnP.toNumber(), MbnS: MbnST.charAt(0), MbnT: MbnST.charAt(1) === "_"});
   document.getElementById("in").onkeyup();
   document.getElementById("op").innerText = MbnP;
   document.getElementById("pst").innerText = MbnST;
   document.getElementById("in").focus();
};
window.onload = function() {
   pchange(0);
};
   </script>
</body>
