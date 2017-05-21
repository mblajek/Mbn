var testMbn = function (displayResp) {
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
         if(req.charAt(req.length - 1) === '*') {
            cmpn = req.length - 1;
         } else {
            cmpn = req.length + evv.length;
         }

         if (req.slice(0, cmpn) !== evv.slice(0, cmpn)) {
            ret.push({id: i, raw: raw, code: exp, correct: req, incorrect: evv});
         }
      }
      return {status: (ret.length === 0) ? 'OK' : 'ERR', count: tl, errors: ret};
   };
   var Mbn0 = MbnCr(0);
   var Mbn3c = MbnCr({MbnP: 3, MbnS: ','});
   var Mbn20u = MbnCr({MbnP: 20, MbnS: ',', MbnT: true});

   var xmlhttp = new XMLHttpRequest();
   xmlhttp.onreadystatechange = function () {
      if (xmlhttp.readyState === 4) {
         var testsAll = JSON.parse(xmlhttp.responseText);
         var tests = testsAll.js.concat(testsAll.both);
         var testsl = tests.length;
         for(var i=0; i< testsl; i++) {
            var test = tests[i];
            test[2] = test[0].replace(/->|::/g, ".").replace(/^\$/, "var $");
         }
         var starttimeJS = new Date();
         var ret = runTestMbn(tests);
         ret.time = new Date() - starttimeJS;
         ret.MbnV = Mbn.prop().MbnV;
         displayResp(JSON.stringify(ret));
      }
   };
   xmlhttp.open("POST", "mbn_test_set.json", true);
   xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
   xmlhttp.send("");
};

