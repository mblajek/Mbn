<script>
<?php readfile('mbn.js'); ?>

   var Mbn0 = Mbn.extend(0);
   var Mbn3c = Mbn.extend({MbnP: 3, MbnS: ','});
   var Mbn20u = Mbn.extend({MbnP: 20, MbnS: ',', MbnT: true});
   var Mbn2nef = Mbn.extend({MbnE: false, MbnF: true});

   //partial JSON support for envirment without JSON
   if (typeof JSON === "undefined") {
      JSON = {parse: function (s) {
            return eval("(" + s + ")");
         }, stringify: function (o) {
            switch (typeof o) {
               case "number":
                  return String(o);
               case "string":
                  return'"' + o.replace(/"/g, '\\"') + '"';
               case "object":
                  var a = [], r = o instanceof Array, i;
                  for (i in o)
                     a.push((r ? "" : this.stringify(i) + ":") + this.stringify(o[i]));
                  return(r ? "[" : "{") + a.join(",") + (r ? "]" : "}");
               default:
                  throw"invalid type " + typeof o;
            }
         }
      };
   }

   var runTestMbn = function (tests) {
      var ret = [];
      var tl = tests.length;
      for (var i = 0; i < tests.length; i++) {
         var test = tests[i];
         var raw = test[0];
         var req = test[1];
         var exp = test[2];

         var evv;
         try {
            evv = String(eval(exp));
         } catch (s) {
            evv = String(s);
         }

         var cmpn;
         var reql = req.length;
         if (reql !== 0 && req.charAt(reql - 1) === '*') {
            cmpn = reql - 1;
         } else {
            cmpn = reql + evv.length;
         }

         if (req.slice(0, cmpn) !== evv.slice(0, cmpn)) {
            ret.push({id: i, raw: raw, code: exp, correct: req, incorrect: evv});
         }
      }
      return {status: (ret.length === 0) ? 'OK' : 'ERR', count: tl, errors: ret};
   };

   var testsAll = <?php readfile('mbn_test_set.json'); ?>;
   var tests = testsAll.both.concat(testsAll.js);
   var testsl = tests.length;
   for (var i = 0; i < testsl; i++) {
      var test = tests[i];
      test[2] = test[0].replace(/->|::/g, ".").replace(/^\$/, "var $");
   }
   var starttimeJS = new Date();
   var ret = runTestMbn(tests);
   ret.time = new Date() - starttimeJS;
   ret.MbnV = Mbn.prop().MbnV;
   document.write(JSON.stringify(ret));
</script>
