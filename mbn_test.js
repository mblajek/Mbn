var testMbn = function (displayResp) {
    var runTestMbn = function (tests) {
        var ret = [];
        var tl = tests.length;
        var evv;
        for (var i = 0; i < tests.length; i++) {
            var test = tests[i];
            var raw = test[0];
            var req = test[1];
            var exp = test[2];

            try {
                evv = String(eval(exp));
            } catch (ex) {
                if (ex instanceof Mbn.MbnErr) {
                    evv = String(ex.errorKey) + " " + String(ex);
                } else {
                    evv = String(ex);
                }
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
    var Mbn0 = Mbn.extend(0);
    var Mbn3c = Mbn.extend({MbnP: 3, MbnS: ','});
    var Mbn20u = Mbn.extend({MbnP: 20, MbnS: ',', MbnT: true});
    var Mbn2nef = Mbn.extend({MbnE: false, MbnF: true});
    var Mbn4yec = Mbn.extend({MbnP: 4, MbnE: true, MbnS: ",", MbnL: 20});

    Mbn.MbnErr.translate(function (key, value) {
        if (key === "mbn.invalid_argument") {
            return "Niepoprawny argument %a% dla konstruktora %v%".replace("%a%", value.v);
        }
        return null;
    });

    var xmlhttp = new XMLHttpRequest();
    xmlhttp.onreadystatechange = function () {
        if (xmlhttp.readyState === 4) {
            var testsAll = JSON.parse(xmlhttp.responseText);
            var tests = testsAll.both.concat(testsAll.js);
            var testsl = tests.length;
            for (var i = 0; i < testsl; i++) {
                var test = tests[i];
                test[2] = test[0]
                   .replace(/->|::/g, ".")
                   .replace(/^\$/, "var $")
                   .replace(/\n/g, "\\n\\")
                   .replace(/\r/g, "\\r\\");
            }
            var starttimeJS = Date.now();
            var ret = runTestMbn(tests);
            ret.time = Date.now() - starttimeJS;
            ret.MbnV = Mbn.prop().MbnV;
            displayResp(JSON.stringify(ret));
        }
    };
    xmlhttp.open("POST", "mbn_test_set.json", true);
    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xmlhttp.send("");
};

