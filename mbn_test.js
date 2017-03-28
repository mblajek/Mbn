var testMbn = function () {
   var runTestMbn = function (tests) {
      var ret = [];
      var tl = tests.length;
      for (var i = 0; i < tests.length; i++) {
         var test = tests[i];
         var evv;
         var err = 0;
         try {
            evv = String(eval(test[0]));
         } catch (s) {
            evv = String(s);
         }

         var req = test[1];


         if ((req === false && err !== 1) || evv !== req) {
            var rlm1 = req.length - 1;
            if (req.charAt(rlm1) === '*' && req.slice(0, rlm1) === evv.slice(0, rlm1)) {
               continue;
            }
            ret.push( {id: i, code: test[0], correct: req, incorrect: evv});
         }
      }
      return {status: (ret.length === 0) ? 'OK' : 'ERR', count: tl, errors: ret};
   };
   var Mbn0 = MbnCr(0);
   var Mbn3c = MbnCr({MbnP: 3, MbnS: ','});
   var Mbn20u = MbnCr({MbnP: 20, MbnS: ',', MbnT: true});

   var tests = [["0", "0"]];

   tests.push(['new Mbn(null);', '0.00']);
   tests.push(['new Mbn(true);', '1.00']);
   tests.push(['new Mbn(false);', '0.00']);

   tests.push(['new Mbn0()', '0']);
   tests.push(['new Mbn0("1.4")', '1']);
   tests.push(['new Mbn0("1.5")', '2']);
   tests.push(['new Mbn0(1.6)', '2']);
   tests.push(['new Mbn0("-1.4")', '-1']);
   tests.push(['new Mbn0("-1.5")', '-2']);
   tests.push(['new Mbn0(-1.6)', '-2']);

   tests.push(['new Mbn0("-  1.6")', '-2']);
   tests.push(['new Mbn0(" - 1.6")', '-2']);
   tests.push(['new Mbn0("  -1.6")', '-2']);
   tests.push(['new Mbn0(" - 1.6 ")', '-2']);
   tests.push(['new Mbn0(" - 1. ")', '-1']);
   tests.push(['new Mbn0(" - .6 ")', '-1']);
   tests.push(['new Mbn0(" + .6 ")', '1']);

   tests.push(['new Mbn()', '0.00']);
   tests.push(['new Mbn("1.234")', '1.23']);
   tests.push(['new Mbn("1.235")', '1.24']);
   tests.push(['new Mbn(1.236)', '1.24']);
   tests.push(['new Mbn("-1.234")', '-1.23']);
   tests.push(['new Mbn("-1.235")', '-1.24']);
   tests.push(['new Mbn(-1.236)', '-1.24']);

   tests.push(['new Mbn("-  1.6")', '-1.60']);
   tests.push(['new Mbn(" - 1.6")', '-1.60']);
   tests.push(['new Mbn("  -1.6")', '-1.60']);
   tests.push(['new Mbn(" - 1.6 ")', '-1.60']);
   tests.push(['new Mbn(" - 1. ")', '-1.00']);
   tests.push(['new Mbn(" - .6 ")', '-0.60']);
   tests.push(['new Mbn(" + .6 ")', '0.60']);

   tests.push(['new Mbn20u()', '0']);
   tests.push(['new Mbn20u("0,000000000000000000005")', '0,00000000000000000001']);
   tests.push(['new Mbn20u("-0,000000000000000000005")', '-0,00000000000000000001']);

   tests.push(['new Mbn0(new Mbn(1.495))', '2']);
   tests.push(['new Mbn0(new Mbn(-1.495))', '-2']);

   tests.push(['new Mbn3c(new Mbn20u("0,999499999999999999994"))', '0,999']);
   tests.push(['new Mbn3c(new Mbn20u("0,999499999999999999995"))', '1,000']);
   tests.push(['new Mbn3c(new Mbn20u("-0,999499999999999999994"))', '-0,999']);
   tests.push(['new Mbn3c(new Mbn20u("-0,999499999999999999995"))', '-1,000']);
   tests.push(['new Mbn20u(new Mbn20u("4,5"))', '4,5']);
   tests.push(['new Mbn0(new Mbn20u("4,5"))', '5']);

   tests.push(['new Mbn0(-1).cmp(1)', '-1']);
   tests.push(['new Mbn0(-1).cmp(0)', '-1']);
   tests.push(['new Mbn0(0).cmp(1)', '-1']);
   tests.push(['new Mbn0(-1).cmp(-1)', '0']);
   tests.push(['new Mbn0(0).cmp(0)', '0']);
   tests.push(['new Mbn0(1).cmp(1)', '0']);
   tests.push(['new Mbn0(1).cmp(-1)', '1']);
   tests.push(['new Mbn0(1).cmp(0)', '1']);
   tests.push(['new Mbn0(0).cmp(-1)', '1']);

   tests.push(['new Mbn0(-1).eq(1)', 'false']);
   tests.push(['new Mbn0(1).eq(1)', 'true']);
   tests.push(['new Mbn0(1).eq(-1)', 'false']);

   tests.push(['new Mbn0(4).add(3)', '7']);
   tests.push(['new Mbn0(4).add(-3)', '1']);
   tests.push(['new Mbn0(-4).add(3)', '-1']);
   tests.push(['new Mbn0(-4).add(-3)', '-7']);
   tests.push(['new Mbn0(3).add(4)', '7']);
   tests.push(['new Mbn0(3).add(-4)', '-1']);
   tests.push(['new Mbn0(-3).add(4)', '1']);
   tests.push(['new Mbn0(-3).add(-4)', '-7']);
   tests.push(['new Mbn0(3).add(-3)', '0']);
   tests.push(['new Mbn0(-3).add(3)', '0']);

   tests.push(['new Mbn0(4).sub(3)', '1']);
   tests.push(['new Mbn0(4).sub(-3)', '7']);
   tests.push(['new Mbn0(-4).sub(3)', '-7']);
   tests.push(['new Mbn0(-4).sub(-3)', '-1']);
   tests.push(['new Mbn0(3).sub(4)', '-1']);
   tests.push(['new Mbn0(3).sub(-4)', '7']);
   tests.push(['new Mbn0(-3).sub(4)', '-7']);
   tests.push(['new Mbn0(-3).sub(-4)', '1']);
   tests.push(['new Mbn0(3).sub(3)', '0']);
   tests.push(['new Mbn0(-3).sub(-3)', '0']);

   tests.push(['new Mbn0(3).add(0)', '3']);
   tests.push(['new Mbn0(-3).add(0)', '-3']);
   tests.push(['new Mbn0(0).add(3)', '3']);
   tests.push(['new Mbn0(0).add(-3)', '-3']);
   tests.push(['new Mbn0(3).sub(0)', '3']);
   tests.push(['new Mbn0(-3).sub(0)', '-3']);
   tests.push(['new Mbn0(0).sub(3)', '-3']);
   tests.push(['new Mbn0(0).sub(-3)', '3']);

   tests.push(['new Mbn0(4).mul(3)', '12']);
   tests.push(['new Mbn0(4).mul(-3)', '-12']);
   tests.push(['new Mbn0(-4).mul(3)', '-12']);
   tests.push(['new Mbn0(-4).mul(-3)', '12']);
   tests.push(['new Mbn0(3).mul(4)', '12']);
   tests.push(['new Mbn0(3).mul(-4)', '-12']);
   tests.push(['new Mbn0(-3).mul(4)', '-12']);
   tests.push(['new Mbn0(-3).mul(-4)', '12']);

   tests.push(['new Mbn0(4).div(3)', '1']);
   tests.push(['new Mbn0(4).div(-3)', '-1']);
   tests.push(['new Mbn0(-4).div(3)', '-1']);
   tests.push(['new Mbn0(-4).div(-3)', '1']);
   tests.push(['new Mbn0(3).div(4)', '1']);
   tests.push(['new Mbn0(3).div(-4)', '-1']);
   tests.push(['new Mbn0(-3).div(4)', '-1']);
   tests.push(['new Mbn0(-3).div(-4)', '1']);

   tests.push(['new Mbn0(5).div(3)', '2']);
   tests.push(['new Mbn0(5).div(-3)', '-2']);
   tests.push(['new Mbn0(-5).div(3)', '-2']);
   tests.push(['new Mbn0(-5).div(-3)', '2']);
   tests.push(['new Mbn0(2).div(5)', '0']);
   tests.push(['new Mbn0(2).div(-5)', '0']);
   tests.push(['new Mbn0(-2).div(5)', '0']);
   tests.push(['new Mbn0(-2).div(-5)', '0']);

   tests.push(['new Mbn0(3).mul(0)', '0']);
   tests.push(['new Mbn0(-3).mul(0)', '0']);
   tests.push(['new Mbn0(0).mul(3)', '0']);
   tests.push(['new Mbn0(0).mul(-3)', '0']);
   tests.push(['new Mbn0(3).div(0)', 'Mbn.div error*']);
   tests.push(['new Mbn0(-3).div(0)', 'Mbn.div error*']);
   tests.push(['new Mbn0(0).div(3)', '0']);
   tests.push(['new Mbn0(0).div(-3)', '0']);

   tests.push(['new Mbn0(1).isInt()', 'true']);
   tests.push(['new Mbn0(1).round()', '1']);
   tests.push(['new Mbn0(1).floor()', '1']);
   tests.push(['new Mbn0(1).ceil()', '1']);

   tests.push(['new Mbn("0.22").add("0.33")', '0.55']);
   tests.push(['new Mbn("0.22").sub("0.33")', '-0.11']);
   tests.push(['new Mbn("0.22").mul("0.33")', '0.07']);
   tests.push(['new Mbn("0.22").div("0.33")', '0.67']);

   tests.push(['new Mbn("0.22").add("-0.22")', '0.00']);
   tests.push(['new Mbn("0.28").sub("0.28")', '0.00']);
   tests.push(['new Mbn("0.08").mul("0.09")', '0.01']);
   tests.push(['new Mbn("-0.02").mul("0.03")', '0.00']);
   tests.push(['new Mbn("0.05").div("10")', '0.01']);
   tests.push(['new Mbn("0.06").div("-20")', '0.00']);

   tests.push(['new Mbn3c("1.1").inva()', '-1,100']);
   tests.push(['new Mbn3c("0").inva()', '0,000']);
   tests.push(['new Mbn3c("-1.1").inva()', '1,100']);

   tests.push(['new Mbn3c("1.1").invm()', '0,909']);
   tests.push(['new Mbn3c("0").invm()', 'Mbn.div error*']);
   tests.push(['new Mbn3c("-1.1").invm()', '-0,909']);

   tests.push(['new Mbn3c("1.1").abs()', '1,100']);
   tests.push(['new Mbn3c("0").abs()', '0,000']);
   tests.push(['new Mbn3c("-1.1").abs()', '1,100']);

   tests.push(['new Mbn("0.4").floor()', '0.00']);
   tests.push(['new Mbn("0.5").floor()', '0.00']);
   tests.push(['new Mbn("0.4").ceil()', '1.00']);
   tests.push(['new Mbn("0.5").ceil()', '1.00']);
   tests.push(['new Mbn("0.4").round()', '0.00']);
   tests.push(['new Mbn("0.49").round()', '0.00']);
   tests.push(['new Mbn("0.5").round()', '1.00']);

   tests.push(['new Mbn("-0.4").floor()', '-1.00']);
   tests.push(['new Mbn("-0.5").floor()', '-1.00']);
   tests.push(['new Mbn("-0.4").ceil()', '0.00']);
   tests.push(['new Mbn("-0.5").ceil()', '0.00']);
   tests.push(['new Mbn("-0.4").round()', '0.00']);
   tests.push(['new Mbn("-0.5").round()', '-1.00']);

   tests.push(['new Mbn("1").isInt()', 'true']);
   tests.push(['new Mbn("1").isInt()', 'true']);
   tests.push(['new Mbn("0.005").isInt()', 'false']);
   tests.push(['new Mbn("-0.005").isInt()', 'false']);
   tests.push(['new Mbn("0").isInt()', 'true']);

   tests.push(['new Mbn20u("21,25").toNumber()', '21.25']);
   tests.push(['new Mbn20u("-21,5").toNumber()', '-21.5']);

   tests.push(['new Mbn3c("21.3").eq("21.3")', 'true']);
   tests.push(['new Mbn3c("-21.3").eq("21.3")', 'false']);
   tests.push(['new Mbn3c("21.3").eq("21.4")', 'false']);
   tests.push(['new Mbn3c("21.4").eq("21.2")', 'false']);
   tests.push(['new Mbn3c("21.3").eq("21.4", "0.1")', 'true']);
   tests.push(['new Mbn3c("21.3").eq("21.2", "0.1")', 'true']);
   tests.push(['new Mbn3c("21.3").eq("21.1", "0.1")', 'false']);
   tests.push(['new Mbn3c("21.3").eq("21.5", "0.1")', 'false']);

   tests.push(['new Mbn3c("21.3").cmp("21.3")', '0']);
   tests.push(['new Mbn3c("-21.3").cmp("21.3")', '-1']);
   tests.push(['new Mbn3c("21.3").cmp("21.4")', '-1']);
   tests.push(['new Mbn3c("21.4").cmp("21.2")', '1']);
   tests.push(['new Mbn3c("21.3").cmp("21.4", "0.1")', '0']);
   tests.push(['new Mbn3c("21.3").cmp("21.2", "0.1")', '0']);
   tests.push(['new Mbn3c("21.3").cmp("21.1", "0.1")', '1']);
   tests.push(['new Mbn3c("21.3").cmp("21.5", "0.1")', '-1']);

   tests.push(['new Mbn3c("0.1").eq("0.3", "0.2")', 'true']);
   tests.push(['new Mbn3c("0.1").eq("-0.1", "0.2")', 'true']);
   tests.push(['new Mbn3c("0.1").eq("0", "0.2")', 'true']);
   tests.push(['new Mbn3c("0.1").eq("0.4", "0.2")', 'false']);
   tests.push(['new Mbn3c("0.1").eq("-0.2", "0.2")', 'false']);

   tests.push(['new Mbn0("2").pow("5")', '32']);
   tests.push(['new Mbn0("2").pow("-5")', '0']);
   tests.push(['new Mbn0("3").pow("3")', '27']);
   tests.push(['new Mbn0("3").pow("-3")', '0']);
   tests.push(['new Mbn(.5).pow(7)', '0.01']);

   tests.push(['new Mbn3c("2").pow("5")', '32,000']);
   tests.push(['new Mbn3c("2").pow("-5")', '0,031']);
   tests.push(['new Mbn3c("1.1").pow("4")', '1,464']);
   tests.push(['new Mbn3c("1.1").pow("-4")', '0,683']);

   tests.push(['new Mbn("2").sqrt()', '1.41']);
   tests.push(['new Mbn3c("2").sqrt()', '1,414']);
   tests.push(['new Mbn20u("2").sqrt()', '1,4142135623730950488']);
   tests.push(['new Mbn20u("3").sqrt()', '1,73205080756887729353']);
   tests.push(['new Mbn20u("4").sqrt()', '2']);

   tests.push(['var m = new Mbn("4.32"); m.add(1.23); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.add(1.23, true); m', '5.55']);
   tests.push(['var m = new Mbn("4.32"); m.sub(1.23); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.sub(1.23, true); m', '3.09']);
   tests.push(['var m = new Mbn("4.32"); m.mul(1.23); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.mul(1.23, true); m', '5.31']);
   tests.push(['var m = new Mbn("4.32"); m.div(1.23); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.div(1.23, true); m', '3.51']);
   tests.push(['var m = new Mbn("4.32"); m.mod(1.23); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.mod(1.23, true); m', '0.63']);
   tests.push(['var m = new Mbn("4.32"); m.pow(2); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.pow(2, true); m', '18.66']);
   tests.push(['var m = new Mbn("4.32"); m.sqrt(); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.sqrt(true); m', '2.08']);
   tests.push(['var m = new Mbn("4.32"); m.cmp("3"); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.cmp("3", "2"); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.eq("3"); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.eq("3", "2"); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.isInt(); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.inva(); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.inva(true); m', '-4.32']);
   tests.push(['var m = new Mbn("4.32"); m.invm(); m', '4.32']);
   tests.push(['var m = new Mbn("4.32"); m.invm(true); m', '0.23']);
   tests.push(['var m = new Mbn("-4.32"); m.abs(); m', '-4.32']);
   tests.push(['var m = new Mbn("-4.32"); m.abs(true); m', '4.32']);
   tests.push(['var m = new Mbn("-4.32"); m.intp(); m', '-4.32']);
   tests.push(['var m = new Mbn("-4.32"); m.intp(true); m', '-4.00']);
   tests.push(['var m = new Mbn("-4.32"); m.floor(); m', '-4.32']);
   tests.push(['var m = new Mbn("-4.32"); m.floor(true); m', '-5.00']);
   tests.push(['var m = new Mbn("-4.32"); m.ceil(); m', '-4.32']);
   tests.push(['var m = new Mbn("-4.32"); m.ceil(true); m', '-4.00']);
   tests.push(['var m = new Mbn("-4.32"); m.round(); m', '-4.32']);
   tests.push(['var m = new Mbn("-4.32"); m.round(true); m', '-4.00']);
   tests.push(['var m = new Mbn("-4.32"); m.mod(3); m', '-4.32']);
   tests.push(['var m = new Mbn("-4.32"); m.mod(3, true); m', '-1.32']);
   tests.push(['var m = new Mbn("-4.32"); m.set(3); m', '3.00']);
   tests.push(['var m = new Mbn("-4.32"); m.sgn(); m', '-4.32']);
   tests.push(['var m = new Mbn("-4.32"); m.sgn(true); m', '-1.00']);

   tests.push(['new Mbn("3").split([1,3])', '0.75,2.25']);
   tests.push(['new Mbn("3").split([2,3])', '1.20,1.80']);
   tests.push(['new Mbn("3").split([3,3])', '1.50,1.50']);
   tests.push(['new Mbn("3").split([1,2,3])', '0.50,1.00,1.50']);
   tests.push(['new Mbn("2").split([1,1,1])', '0.67,0.67,0.66']);
   tests.push(['new Mbn("100").split([100,23])', '81.30,18.70']);
   tests.push(['new Mbn("42").split()', '21.00,21.00']);
   tests.push(['new Mbn("42").split(5)', '8.40,8.40,8.40,8.40,8.40']);

   tests.push(['new Mbn3c("1.234").mod("0.401")', '0,031']);
   tests.push(['new Mbn3c("3.234").mod("1")', '0,234']);
   tests.push(['new Mbn3c("1.234").mod("-0.401")', '0,031']);
   tests.push(['new Mbn3c("3.234").mod("-1")', '0,234']);
   tests.push(['new Mbn3c("-1.234").mod("0.401")', '-0,031']);
   tests.push(['new Mbn3c("-3.234").mod("1")', '-0,234']);
   tests.push(['new Mbn3c("2.234").mod("4")', '2,234']);

   tests.push(['new Mbn3c("2.123").intp()', '2,000']);
   tests.push(['new Mbn3c("3.987").intp()', '3,000']);
   tests.push(['new Mbn3c("-4.123").intp()', '-4,000']);
   tests.push(['new Mbn3c("-5.987").intp()', '-5,000']);
   tests.push(['new Mbn3c("0").intp()', '0,000']);

   tests.push(['new Mbn("-99.5").mod(100)', '-99.50']);
   tests.push(['new Mbn("99.5").mod(100)', '99.50']);
   tests.push(['new Mbn0("55").mod(10)', '5']);
   tests.push(['new Mbn0("-55").mod(10)', '-5']);
   tests.push(['new Mbn0("54").mod(10)', '4']);
   tests.push(['new Mbn0("-54").mod(10)', '-4']);

   tests.push(['new Mbn("-2").max(-3)', '-2.00']);
   tests.push(['new Mbn("-3").max(-2)', '-2.00']);
   tests.push(['new Mbn("-2").max(3)', '3.00']);
   tests.push(['new Mbn("3").max(-2)', '3.00']);
   tests.push(['new Mbn("2").max(4)', '4.00']);
   tests.push(['new Mbn("4").max(2)', '4.00']);
   tests.push(['new Mbn("0").max(2)', '2.00']);
   tests.push(['new Mbn("0").max(-2)', '0.00']);

   tests.push(['new Mbn("-2").min(-3)', '-3.00']);
   tests.push(['new Mbn("-3").min(-2)', '-3.00']);
   tests.push(['new Mbn("-2").min(3)', '-2.00']);
   tests.push(['new Mbn("3").min(-2)', '-2.00']);
   tests.push(['new Mbn("2").min(4)', '2.00']);
   tests.push(['new Mbn("4").min(2)', '2.00']);
   tests.push(['new Mbn("0").min(2)', '0.00']);
   tests.push(['new Mbn("0").min(-2)', '-2.00']);

   tests.push(['new Mbn("0").set(-2)', '-2.00']);

   tests.push(['new Mbn("0").sgn()', '0.00']);
   tests.push(['new Mbn("-0.01").sgn()', '-1.00']);
   tests.push(['new Mbn("0.03").sgn()', '1.00']);

   tests.push(['Mbn.const("E")', '2.72']);
   tests.push(['Mbn0.const("E")', '3']);
   tests.push(['Mbn3c.const("E")', '2,718']);
   tests.push(['Mbn20u.const("E")', '2,71828182845904523536']);

   tests.push(['Mbn.const("PI")', '3.14']);
   tests.push(['Mbn0.const("PI")', '3']);
   tests.push(['Mbn3c.const("PI")', '3,142']);
   tests.push(['Mbn20u.const("PI")', '3,14159265358979323846']);

   tests.push(['new Mbn("=2")', '2.00']);
   tests.push(['new Mbn("=2+3")', '5.00']);
   tests.push(['new Mbn("=2-3")', '-1.00']);
   tests.push(['new Mbn("=2/3")', '0.67']);
   tests.push(['new Mbn("=2^3")', '8.00']);
   tests.push(['new Mbn("=sqrt(2)")', '1.41']);
   tests.push(['new Mbn("=2+2*2")', '6.00']);
   tests.push(['new Mbn("=-2")', '-2.00']);
   tests.push(['new Mbn("=(-2+1)")', '-1.00']);
   tests.push(['new Mbn("=(-2+-1)")', '-3.00']);
   tests.push(['new Mbn("=2a",{a:0.1})', '0.20']);
   tests.push(['new Mbn("=a(a+1)",{a:0.5})', '0.75']);
   tests.push(['new Mbn("=a(-a+1)",{a:0.6})', '0.24']);
   tests.push(['new Mbn("=1",{a:0.5})', '1.00']);
   tests.push(['new Mbn("=-7(-sqrt(4))")', '14.00']);
   tests.push(['new Mbn("=(1+2)(2+3)")', '15.00']);
   tests.push(['new Mbn("=.3a",{a:0.6})', '0.18']);
   tests.push(['new Mbn("=2 a a",{a:0.6})', '0.72']);
   tests.push(['new Mbn("=2^3^2")', '512.00']);
   tests.push(['new Mbn("=(2^3)^2")', '64.00']);
   tests.push(['new Mbn("=12/3/2")', '2.00']);
   tests.push(['new Mbn("=12/(3/2)")', '8.00']);
   tests.push(['new Mbn("=12*3/2")', '18.00']);
   tests.push(['new Mbn("=12*3/2")', '18.00']);
   tests.push(['new Mbn("=12/3*2")', '8.00']);
   tests.push(['new Mbn("=12/(3*2)")', '2.00']);
   tests.push(['new Mbn("=abs(-2-3)")', '5.00']);
   tests.push(['new Mbn("=-abs(-2-3)")', '-5.00']);
   tests.push(['new Mbn("=floor(3/2)")', '1.00']);
   tests.push(['new Mbn("=floor(3/-2)")', '-2.00']);
   tests.push(['new Mbn("=floor(-3/-2)")', '1.00']);
   tests.push(['new Mbn("=ceil(3/2)")', '2.00']);
   tests.push(['new Mbn("=ceil(-3/2)")', '-1.00']);

   tests.push(['Mbn.reduce("add", [])', '0.00']);
   tests.push(['Mbn.reduce("add", [1,6,-2])', '5.00']);
   tests.push(['Mbn.reduce("mul", [1,6,-2])', '-12.00']);
   tests.push(['Mbn.reduce("inva", [1,6,-2])', '-1.00,-6.00,2.00']);
   tests.push(['Mbn.reduce("sgn", [1,6,-2])', '1.00,1.00,-1.00']);
   tests.push(['Mbn.reduce("sgn", [])', '']);

   tests.push(['new Mbn("=ceil(PI)")', '4.00']);
   tests.push(['new Mbn("=floor(PI)")', '3.00']);
   tests.push(['new Mbn("=ceil(-PI)")', '-3.00']);
   tests.push(['new Mbn("=floor(-PI)")', '-4.00']);
   tests.push(['new Mbn("=round(PI)")', '3.00']);
   tests.push(['new Mbn("=round(-PI)")', '-3.00']);
   tests.push(['new Mbn("=int(PI)")', '3.00']);
   tests.push(['new Mbn("=int(-PI)")', '-3.00']);
   tests.push(['new Mbn("=ceil(E)")', '3.00']);
   tests.push(['new Mbn("=floor(E)")', '2.00']);
   tests.push(['new Mbn("=ceil(-E)")', '-2.00']);
   tests.push(['new Mbn("=floor(-E)")', '-3.00']);
   tests.push(['new Mbn("=round(E)")', '3.00']);
   tests.push(['new Mbn("=round(-E)")', '-3.00']);
   tests.push(['new Mbn("=int(E)")', '2.00']);
   tests.push(['new Mbn("=int(-E)")', '-2.00']);
   tests.push(['new Mbn("=round(E)&E")', '2.72']);
   tests.push(['new Mbn("=round(PI)&PI")', '3.00']);
   tests.push(['new Mbn("=round(E)|E")', '3.00']);
   tests.push(['new Mbn("=round(PI)|PI")', '3.14']);
   tests.push(['new Mbn("=0&1|1")', '1.00']);
   tests.push(['new Mbn("=1|1&0")', '1.00']);
   tests.push(['new Mbn("=0&(1|1)")', '0.00']);
   tests.push(['new Mbn("=(1|1)&0")', '0.00']);
   tests.push(['Mbn.eval("0&1|1")', '1.00']);
   tests.push(['Mbn.eval("0&(1|1)")', '0.00']);

   var starttimeJS = new Date();
   var ret = runTestMbn(tests);
   ret.time = new Date() - starttimeJS;
   ret.MbnV = Mbn.prop().MbnV;
   return JSON.stringify(ret);
};

