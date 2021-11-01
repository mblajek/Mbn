<?php ob_start(); ?>
function displayResult(displayTestStatusOpt) {
    var displayTestStatus = displayTestStatusOpt || (function (lang, result) {
        document.getElementsByTagName('pre')[0].innerHTML += ((result + " [" + lang + "]<br>"))
    });

    <?= FileHelper::getFile('mbn.js'); ?>

    var Mbn0 = Mbn.extend(0);
    var Mbn3c = Mbn.extend({MbnP: 3, MbnS: ','});
    var Mbn20u = Mbn.extend({MbnP: 20, MbnS: ',', MbnT: true});
    var Mbn2nef = Mbn.extend({MbnE: false, MbnF: true});
    var Mbn4yec = Mbn.extend({MbnP: 4, MbnE: true, MbnS: ",", MbnL: 20});

    Mbn.MbnErr.translate(function (key, value) {
        if (key === "mbn.invalid_argument") {
            return "Niepoprawny argument %a% dla konstruktora %v%".replace("%a%", value.v);
        }
    });

    var hasOwnProperty = {}.hasOwnProperty;

    //partial JSON support for environment without JSON
    function jsonEncode(o) {
        switch (typeof o) {
            case "number":
                return String(o);
            case "string":
                return '"' + o.replace(/"/g, '\\"') + '"';
            case "object":
                var a = [], r = (o instanceof Array), i;
                for (i in o) {
                    if (hasOwnProperty.call(o, i)) {
                        a.push((r ? "" : jsonEncode(i) + ":") + jsonEncode(o[i]));
                    }
                }
                return (r ? "[" : "{") + a.join(",") + (r ? "]" : "}");
            default:
                throw "invalid type " + (typeof o);
        }
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
            } catch (ex) {
                if (ex instanceof Mbn.MbnErr) {
                    evv = String(ex.errorKey) + " " + String(ex);
                } else {
                    evv += String(ex);
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

    function testMbn() {
        /** @type {{both:array, js:array, php:array}} */
        var testsAll = (<?= MbnTest::getTestsAllJson(); ?>);

        var tests = testsAll.both.concat(testsAll.js);
        for (var i = 0; i < tests.length; i++) {
            var test = tests[i];
            test[2] = test[0].replace(/->|::/g, ".").replace(/^\$/, "var $")
               .replace(/\n/g, "\\n").replace(/\r/g, "\\r").replace(/\t/g, "\\t");
        }
        var startTimeJS = new Date();
        var ret = runTestMbn(tests);
        ret.MbnV = Mbn.prop().MbnV;
        ret.time = (new Date()) - startTimeJS;
        ret.env = 'JS';

        displayTestStatus("JS", jsonEncode(ret));
    }

    displayTestStatus("PHP", <?= MbnTest::testMbnResult(true); ?>);
    setTimeout(testMbn, 100);
}
displayResult(((typeof displayTestStatus) !== "undefined") ? displayTestStatus : undefined);

<?php
MbnTest::output(ob_get_clean(), isset($query) ? $query : null);
