/* Mbn v1.53.0 / 04.04.2023 | https://mbn.li | Copyright (c) 2016-2023 Mikołaj Błajek | https://mbn.li/LICENSE */
"use strict";

var Mbn = (function () {
    var messages = {
        invalid_argument: "invalid argument: %v%",
        invalid_format: "invalid format: %v%",
        limit_exceeded: "value exceeded %v% digits limit",
        calc: {
            undefined: "undefined: %v%",
            unexpected: "unexpected: %v%"
        },
        cmp: {
            negative_diff: "negative maximal difference: %v%"
        },
        def: {
            undefined: "undefined constant: %v%",
            already_set: "constant already set: %v% = %w%",
            invalid_name: "invalid name for constant: %v%"
        },
        div: {
            zero_divisor: "division by zero"
        },
        extend: {
            invalid_precision: "invalid precision (non-negative integer): %v%",
            invalid_separator: "invalid separator (dot, comma): %v%",
            invalid_truncation: "invalid truncation (bool): %v%",
            invalid_evaluating: "invalid evaluating (bool, null): %v%",
            invalid_formatting: "invalid formatting (bool): %v%",
            invalid_limit: "invalid digit limit (positive int): %v%"
        },
        fact: {
            invalid_value: "factorial of invalid value (non-negative integer): %v%"
        },
        format: {_: "extend"},
        pow: {
            unsupported_exponent: "only integer exponents supported: %v%"
        },
        reduce: {
            invalid_function: "invalid function name: %v%",
            no_array: "no array given",
            invalid_argument_count: "two arguments can be used only with two-argument functions",
            different_lengths: "arrays have different lengths: %v%, %w%",
            different_keys: "arrays have different keys: [%v%], [%w%]"
        },
        split: {
            invalid_part_count: "only positive integer number of parts supported: %v%",
            zero_part_sum: "cannot split value when sum of parts is zero"
        },
        sqrt: {
            negative_value: "square root of negative value: %v%"
        }
    };
    var errTranslation = null;
    /**
     * @param {Object} val
     * @param {*} key
     * @returns {boolean}
     */
    var own = function (val, key) {
        return messages.hasOwnProperty.call(val, key)
    }
    /**
     * Convert value to readable string
     * @param {*} val value to stringify
     * @param {boolean=} implodeArr implode array (first level) or replace contents with ".."
     * @returns {string}
     */
    var valToMsgString = function (val, implodeArr) {
        if (val instanceof Array) {
            var valArr = [], i;
            if (implodeArr === undefined || implodeArr === true) {
                for (i = 0; i < val.length; i++) {
                    valArr.push(valToMsgString(val[i], false));
                }
            } else {
                valArr.push("..");
            }
            return "[" + valArr.join(",") + "]";
        }
        return (typeof val === "string") ? ("\"" + val + "\"") : String(val);
    };
    /**
     * Common error message object
     * @export
     * @constructor
     * @param {string} key error code
     * @param {*=} values incorrect value to message
     * @param {boolean=} multi passing array with multiple values
     */
    var MbnErr = function (key, values, multi) {
        var valObj = {};
        var i, val;
        if (arguments.length !== 1) {
            if (typeof values !== "object" || multi !== true) {
                values = {v: values};
            }
            for (i in values) {
                if (own(values, i)) {
                    val = valToMsgString(values[i]);
                    valObj[i] = ((val.length > 20) ? (val.slice(0, 18) + "..") : val);
                }
            }
        }
        this.errorKey = "mbn." + key;
        this.errorValues = valObj;

        var msg = null;
        if (typeof errTranslation === "function") {
            try {
                msg = errTranslation(this.errorKey, valObj)
            } catch (e) {
            }
        }
        if (typeof msg !== "string") {
            var keyArr = key.split(".");
            var keyArrLength = keyArr.length;
            msg = "Mbn";
            if (keyArrLength > 1) {
                msg += "." + keyArr[0];
            }
            var subMessages = messages;
            for (i = 0; i < keyArrLength; i++) {
                var word = keyArr[i];
                var nextSubMessages = subMessages[word];
                if (typeof nextSubMessages === "object" && own(nextSubMessages, "_")) {
                    nextSubMessages = subMessages[nextSubMessages._];
                }
                subMessages = nextSubMessages;
            }
            msg += " error: " + subMessages;
        }
        for (i in valObj) {
            if (own(valObj, i)) {
                msg = msg.replace("%" + i + "%", valObj[i]);
            }
        }
        this.message = msg;
    };
    MbnErr.prototype.toString = function () {
        return this.message;
    };

    /**
     * Translation for errors
     * @param {function} translation
     */
    MbnErr.translate = function (translation) {
        errTranslation = translation;
    };

    //version of Mbn library
    var MbnV = "1.53.0";
    //default precision
    var MbnDP = 2;
    //default separator
    var MbnDS = ".";
    //default truncation
    var MbnDT = false;
    //default evaluating
    var MbnDE = null;
    //default formatting
    var MbnDF = false;
    //default digit limit
    var MbnDL = 1000;

    /**
     * fill options with default parameters and check
     * @param opt {Object} params by reference
     * @param MbnDP {number} default precision
     * @param MbnDS {string} default separator
     * @param MbnDT {boolean} default truncation
     * @param MbnDE {boolean|null} default evaluating
     * @param MbnDF {boolean} default formatting
     * @param MbnDL {number} default digit limit
     * @param fname name of function for exception
     * @throws {MbnErr} invalid options
     * @return {Object} checked and filled class options
     */
    var prepareOpt = function (opt, MbnDP, MbnDS, MbnDT, MbnDE, MbnDF, MbnDL, fname) {
        var MbnP = MbnDP, MbnS = MbnDS, MbnT = MbnDT, MbnE = MbnDE, MbnF = MbnDF, MbnL = MbnDL;
        if (own(opt, "MbnP")) {
            MbnP = opt.MbnP;
            if (typeof MbnP !== "number" || MbnP < 0 || !isFinite(MbnP) || Math.round(MbnP) !== MbnP) {
                throw new MbnErr(fname + "invalid_precision", MbnP);
            }
        }
        if (own(opt, "MbnS")) {
            MbnS = opt.MbnS;
            if (MbnS !== "." && MbnS !== ",") {
                throw new MbnErr(fname + "invalid_separator", MbnS);
            }
        }
        if (own(opt, "MbnT")) {
            MbnT = opt.MbnT;
            if (MbnT !== true && MbnT !== false) {
                throw new MbnErr(fname + "invalid_truncation", MbnT);
            }
        }
        if (own(opt, "MbnE")) {
            MbnE = opt.MbnE;
            if (MbnE !== true && MbnE !== false && MbnE !== null) {
                throw new MbnErr(fname + "invalid_evaluating", MbnE);
            }
        }
        if (own(opt, "MbnF")) {
            MbnF = opt.MbnF;
            if (MbnF !== true && MbnF !== false) {
                throw new MbnErr(fname + "invalid_formatting", MbnF);
            }
        }
        if (own(opt, "MbnL")) {
            MbnL = opt.MbnL;
            if (typeof MbnL !== "number" || MbnL <= 0 || !isFinite(MbnL) || Math.round(MbnL) !== MbnL) {
                throw new MbnErr(fname + "invalid_limit", MbnL);
            }
        }
        return {MbnV: MbnV, MbnP: MbnP, MbnS: MbnS, MbnT: MbnT, MbnE: MbnE, MbnF: MbnF, MbnL: MbnL};
    };

    /**
     * Function returns constructor of Mbn objects
     * MbnP - precision, number of digits in fractional part
     * MbnS - output separator(".", ","), default "."
     * MbnT - trim insignificant zeros in output string ("0.20" to "0.2"), default false (no trimming)
     * MbnF - format thousands in output string ("1234" to "1 234"), default false
     * MbnE - evaluate strings, true - always, null - starting with "=", false - never, default null
     * @export
     * @param {number|Object=} opt precision or object with params
     * @throws {MbnErr} invalid class options
     */
    var MbnCr = function (opt) {
        if (typeof opt !== "object") {
            opt = (opt !== undefined) ? {MbnP: opt} : {};
        }
        opt = prepareOpt(opt, MbnDP, MbnDS, MbnDT, MbnDE, MbnDF, MbnDL, "extend.");
        var MbnP = opt.MbnP, MbnS = opt.MbnS, MbnT = opt.MbnT, MbnE = opt.MbnE, MbnF = opt.MbnF, MbnL = opt.MbnL;
        var mbn0d = [0];
        while (mbn0d.length <= MbnP) {
            mbn0d.push(0);
        }

        /**
         * Private function, carries digits bigger than 9, and removes leading zeros
         * @param {Mbn} a
         */
        var mbnCarry = function (a) {
            var ad = a._d;
            var adlm1 = ad.length - 1;
            var i = adlm1;
            var adi, adid, adic;
            while (i >= 0) {
                adi = ad[i];
                while (adi < 0) {
                    adi += 10;
                    ad[i - 1]--;
                }
                adid = adi % 10;
                adic = (adi - adid) / 10;
                ad[i] = adid;
                if (adic !== 0) {
                    if (i !== 0) {
                        ad[--i] += adic;
                    } else {
                        ad.unshift(adic);
                        adlm1++;
                    }
                } else {
                    i--;
                }
            }
            while (adlm1 > MbnP && ad[0] === 0) {
                ad.shift();
                adlm1--;
            }
            while (adlm1 < MbnP) {
                ad.unshift(0);
                adlm1++;
            }
            if (adlm1 === MbnP) {
                for (i = 0; i <= adlm1 && ad[i] === 0; i++) {
                }
                a._s *= (i <= adlm1) ? 1 : 0;
            } else if (adlm1 - MbnP > MbnL) {
                throw new MbnErr("limit_exceeded", MbnL);
            }

        };

        /**
         * Private function, removes last digit and rounds next-to-last depending on it
         * @param {Mbn} a
         */
        var mbnRoundLast = function (a) {
            var ad = a._d;
            var adl = ad.length;
            if (adl < 2) {
                ad.unshift(0);
                adl++;
            }
            if (ad.pop() >= 5) {
                ad[adl - 2]++;
            }
            mbnCarry(a);
        };

        var wsRx2 = /^\s*(=)?[\s=]*([-+])?\s*((?:[\s\S]*\S)?)/;
        /**
         * Private function, sets value from string
         * @param {Mbn} a
         * @param {string} ns
         * @param {Object|boolean=} v
         * @throws {MbnErr} invalid format, calc error
         */
        var mbnFromString = function (a, ns, v) {
            var np = ns.match(wsRx2);
            var n = np[3];
            if (np[2] === "-") {
                a._s = -1;
            }
            var ln = ((n.indexOf(".") + 1) || (n.indexOf(",") + 1)) - 1;
            var nl = n.length;
            var al = nl;
            if (ln === -1) {
                ln = nl;
            } else {
                al = ln + 1;
            }
            var l = Math.max(al + MbnP, nl);
            var i, c, cs = 0;
            for (i = 0; i <= l; i++) {
                c = (i < nl) ? (n.charCodeAt(i) - 48) : 0;
                if (c >= 0 && c <= 9) {
                    if (i <= al + MbnP) {
                        a._d.push(c);
                    }
                } else if (!((i === ln && nl !== 1) || (c === -16 && i > cs && (i + 1) < ln))) {
                    if (v === true || (v instanceof Object) || (v !== false && (MbnE === true || (MbnE === null && np[1] === "=")))) {
                        a.set(mbnCalc(ns, v, null));
                        return;
                    }
                    throw new MbnErr("invalid_format", ns);
                } else if (c === -16) {
                    cs = i + 1;
                }
            }
            mbnRoundLast(a);
        };


        /**
         * Private function, sets value from number
         * @param {Mbn} a
         * @param {number} nn
         * @throws {MbnErr} infinite value
         */
        var mbnFromNumber = function (a, nn) {
            if (!isFinite(nn)) {
                if (isNaN(nn)) {
                    throw new MbnErr("invalid_argument", nn);
                }
                throw new MbnErr("limit_exceeded", MbnL);
            }
            if (nn < 0) {
                nn = -nn;
                a._s = -1;
            }
            var ni = Math.floor(nn);
            var nf = nn - ni;
            var nfi, c, i;
            do {
                c = Math.round(ni % 10);
                ni = Math.round((ni - c) / 10);
                a._d.unshift(c);
            } while (ni > 0);
            for (i = 0; i <= MbnP; i++) {
                nf *= 10;
                nfi = Math.floor(nf);
                c = (nfi === 10) ? 9 : nfi;
                a._d.push(c);
                nf -= c;
            }
            mbnRoundLast(a);
        };

        /**
         * Private function, returns string value
         * @param {Mbn} a
         * @param {number} p target precision
         * @param {string} s target separator
         * @param {boolean=} t truncation
         * @param {boolean=} f formatting
         * @return {string}
         */
        var mbnToString = function (a, p, s, t, f) {
            var v = a, li = a._d.length - MbnP, i;
            if (p < MbnP) {
                var b = ppNewMbn(a);
                var bl = b._d.length;
                if (p < MbnP - 1) {
                    b._d = b._d.slice(0, bl - MbnP + p + 1);
                }
                mbnRoundLast(b);
                bl = b._d.length;
                if (bl - p > li) {
                    b._d = b._d.slice(bl - p - li);
                }
                v = b;
            }
            var di = v._d.slice(0, li);
            if (f) {
                var dl = di.length;
                for (i = 0; 3 * i < dl - 3; i++) {
                    di.splice(-3 - 4 * i, 0, " ");
                }
            }
            var df = v._d.slice(li);
            if (p > MbnP && !t) {
                for (i = 0; i < p - MbnP; i++) {
                    df.push(0);
                }
            }
            if (t) {
                for (i = df.length - 1; i >= 0; i--) {
                    if (df[i] !== 0) {
                        break;
                    }
                }
                df = df.slice(0, i + 1);
            }
            var r = ((a._s < 0) ? "-" : "") + di.join("");
            if (df.length > 0) {
                r += s + df.join("");
            }
            return r;
        };


        /**@param {*} n
         * @param {Mbn=} a
         * @param {Object|boolean=} v
         * @return {Mbn}*/
        var ppNewReset = function (n, a, v) {
            a._s = 0;
            a._d = mbn0d.slice();
            return a;
        }
        /**@param {*} n
         * @param {Mbn=} a
         * @param {Object|boolean=} v
         * @return {Mbn}*/
        var ppNewAny = ppNewReset;
        /**
         * Constructor of Mbn object
         * @export
         * @constructor
         * @property {number} _s
         * @property {number[]} _d
         * @param {*=} n Value, default 0
         * @param {Object|boolean=} v Object with vars for evaluation
         * @throws {MbnErr} invalid argument, invalid format, calc error
         */
        var Mbn = function (n, v) {
            if (!(this instanceof Mbn)) {
                return new Mbn(n, v);
            }
            ppNewAny(n, this, v);
        };
        var mbn0 = Mbn(0);
        /**@param {Mbn=} a
         * @return {Mbn}*/
        var ppNew0 = own(Object, "setPrototypeOf") ? (function (a) {
            return a ? ppNewReset(0, a) : Object.setPrototypeOf({_s: 0, _d: mbn0d.slice()}, mbn0);
        }) : function (a) {
            return a ? ppNewReset(0, a) : {__proto__: mbn0, _s: 0, _d: mbn0._d.slice()};
        }
        /**@param {number} n
         * @param {Mbn=} a
         * @return {Mbn}*/
        var ppNewInt = function (n, a) {
            var mbn = ppNew0(a);
            if (n !== 0) {
                var na = Math.abs(n)
                mbn._s = n / na;
                mbn._d[0] = na % 10;
                while (na >= 10) {
                    na = (na - mbn._d[0]) / 10;
                    mbn._d.unshift(na % 10)
                }
            }
            return mbn;
        };
        var mbn1 = ppNewInt(1);
        /**@param {number} n
         * @param {Mbn=} a
         * @return {Mbn}*/
        var ppNewNumber = function (n, a) {
            var mbn = ppNew0(a);
            if (Math.abs(n) < 1e9 && n === Math.round(n)) {
                return ppNewInt(n, mbn);
            }
            mbn._d = [];
            mbn._s = 1;
            mbnFromNumber(mbn, n);
            return mbn;
        };
        /**@param {Mbn} n
         * @param {Mbn=} a
         * @return {Mbn}*/
        var ppNewMbn = function (n, a) {
            var mbn = ppNew0(a);
            mbn._s = n._s;
            mbn._d = n._d.slice();
            return mbn;
        };
        /**@param {string} n
         * @param {Mbn=} a
         * @param {Object|boolean=} v
         * @return {Mbn}*/
        var ppNewString = function (n, a, v) {
            var mbn = ppNew0(a);
            if (n.length < -15) {
                var nr = n.replace(",", ".");
                var nn = Number(nr);
                if (isFinite(nn) && nr === String(n)) {
                    return ppNewNumber(nn, mbn);
                }
            }
            mbn._d = [];
            mbn._s = 1;
            mbnFromString(mbn, n, v);
            return mbn;
        };
        /**@param {Object} n
         * @param {Mbn=} a
         * @param {Object|boolean=} v
         * @return {Mbn}*/
        var ppNewObject = function (n, a, v) {
            var mbn = a || ppNew0();
            if (n instanceof Mbn) {
                return ppNewMbn(n, mbn)
            } else if (n && n.toString === Array.prototype.toString) {
                throw new MbnErr("invalid_argument", n);
            }
            return ppNewString((n !== null) ? String(n) : "0", mbn, v);
        };
        ppNewAny = function (n, a, v) {
            var mbn = ppNew0(a);
            switch (typeof n) {
                case "undefined":
                case "boolean":
                    return ppNewInt(Number(n || false), mbn);
                case "number":
                    return ppNewNumber(n, mbn);
                case "bigint":
                case "object":
                    return ppNewObject(n, mbn, v);
                case "string":
                    return ppNewString(n, mbn, v)
                default:
                    throw new MbnErr("invalid_argument", n);
            }
        }

        /**
         * Returns properties of Mbn class
         * @return {Object} properties
         */
        Mbn.prop = function () {
            return {MbnV: MbnV, MbnP: MbnP, MbnS: MbnS, MbnT: MbnT, MbnE: MbnE, MbnF: MbnF, MbnL: MbnL};
        };

        /**
         * Returns string value
         * @return string
         */
        Mbn.prototype.toString = function () {
            return mbnToString(this, MbnP, MbnS, MbnT, MbnF);
        };

        /**
         * Returns reformatted string value
         * @param {boolean|Object=} opt thousand grouping or object with params, default true
         * @return {string}
         */
        Mbn.prototype.format = function (opt) {
            if (typeof opt !== "object") {
                opt = {MbnF: arguments.length === 0 ? true : opt};
            }
            opt = prepareOpt(opt, MbnP, MbnS, MbnT, MbnE, MbnF, MbnL, "format.");
            return mbnToString(this, opt.MbnP, opt.MbnS, opt.MbnT, opt.MbnF);
        };

        /**
         * Returns number value
         * @return {number}
         */
        Mbn.prototype.toNumber = function () {
            return Number(mbnToString(this, MbnP, "."));
        };






        /**@param {function} fun
         * @param {Array} argv
         * @return {*}*/
        var pfFun = function (fun, argv) {
            var argvMbn = [argv[0]];
            for (var i = 1; i < argv.length; i++) {
                var arg = argv[i];
                argvMbn.push((arg instanceof Mbn) ? arg : ppNewAny(arg))
            }
            return fun.apply(null, argvMbn);
        }
        /**@param {function} fun
         * @param {boolean=} m
         * @param {Array} argv
         * @return {Mbn}*/
        var ppFun = function (fun, m, argv) {
            return ppNewMbn(pfFun(fun, argv), (m === true) ? argv[0] : null);
        }
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        var ppSet = function (a, b) {
            a._d = b._d.slice();
            a._s = b._s;
        }
        var pfToString;
        var pfFormat;
        var pfToNumber;

        /**@param {Mbn} a
         * @param {Mbn} b
         * @param {Mbn} d
         * @return {number}*/
        var pfCmp =  function (a, b, d) {
            if (d._s === 0) {
                if (a._s !== b._s) {
                    return (a._s > b._s) ? 1 : -1;
                }
                if (a._s === 0) {
                    return 0;
                }
                var bl = b._d.length;
                var ld = a._d.length - bl;
                if (ld !== 0) {
                    return (ld > 0) ? a._s : -a._s;
                }
                for (var i = 0; i < bl; i++) {
                    if (a._d[i] !== b._d[i]) {
                        return (a._d[i] > b._d[i]) ? a._s : -a._s;
                    }
                }
                return 0;
            }
            if (d._s === -1) {
                throw new MbnErr("cmp.negative_diff", d);
            }
            if (pfCmp(pfAbs(pfSub(a, b)), d, mbn0) !== 1) {
                return 0;
            }
            return pfCmp(a, b, mbn0);
        };
        /**@param {Mbn} a
         * @param {Mbn} b
         * @param {Mbn} d
         * @return {boolean}*/
        var pfEq = function (a, b, d) {
            return pfCmp(a, b, d) === 0;
        };


        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        /**@param {Mbn} a
         * @return {boolean}*/
        var pfIsInt = function (a) {
            for (var l = a._d.length - MbnP; l < a._d.length; l++) {
                if (a._d[l] !== 0) {
                    return false;
                }
            }
            return true;
        }
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        var pfSet = function (a, b) {
            return b
        }
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        var pfAdd = function (a, b) {
            var r = ppNewMbn(b);
            if (a._s !== 0) {
                if (b._s === 0) {
                    ppSet(r, a);
                } else if (b._s === a._s) {
                    var ld = a._d.length - b._d.length;
                    if (ld < 0) {
                        b = a;
                        ld = -ld;
                    } else {
                        ppSet(r, a);
                    }
                    for (var i = 0; i < r._d.length; i++) {
                        if (i >= ld) {
                            r._d[i] += b._d[i - ld];
                        }
                    }
                    mbnCarry(r);
                } else {
                    r._s = -r._s;
                    r = pfSub(r, a)
                    r._s = -r._s;
                }
            }
            return r;
        };
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        var pfSub = function (a, b) {
            var r = ppNewMbn(b);
            if (a._s === 0) {
                r._s = -r._s;
            } else if (b._s === 0) {
                ppSet(r, a);
            } else if (b._s === a._s) {
                var ld = a._d.length - b._d.length;
                var cmp = pfCmp(a, b, mbn0) * a._s;
                if (cmp === 0) {
                    r = ppNewInt(0);
                } else {
                    if (cmp === -1) {
                        b = a;
                        ld = -ld;
                    } else {
                        ppSet(r, a);
                    }
                    for (var i = 0; i < r._d.length; i++) {
                        if (i >= ld) {
                            r._d[i] -= b._d[i - ld];
                        }
                    }
                    r._s = cmp * a._s;
                    mbnCarry(r);
                }
            } else {
                r._s = -r._s;
                r = pfAdd(r, a);
            }
            return r;
        };
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        var pfMul = function (a, b) {
            var r = ppNewMbn(b);
            r._d = [];
            for (var i = 0; i < a._d.length; i++) {
                for (var j = 0; j < b._d.length; j++) {
                    r._d[i + j] = a._d[i] * b._d[j] + (r._d[i + j] || 0);
                }
            }
            r._s = a._s * b._s;
            mbnCarry(r);
            if (MbnP >= 1) {
                if (MbnP > 1) {
                    r._d = r._d.slice(0, 1 - MbnP);
                }
                mbnRoundLast(r);
            }
            return r;
        };
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        var pfDiv = function (a, b) {
            if (b._s === 0) {
                throw new MbnErr("div.zero_divisor");
            }
            if (a._s === 0) {
                return ppNewMbn(a);
            }
            var x = a._d.slice();
            var y = b._d.slice();
            var p = 0;
            var ra = [0];
            while (y[0] === 0) {
                y.shift();
            }
            while (x[0] === 0) {
                x.shift();
            }
            var mp = MbnP + 1;
            while (y.length < x.length) {
                y.push(0);
                mp++;
            }
            var i, xl, yl;
            do {
                while ((x[(xl = x.length) - 1] + y[(yl = y.length) - 1]) === 0) {
                    x.pop();
                    y.pop();
                }
                var xge = (xl >= yl);
                if (xl === yl) {
                    for (i = 0; i < xl; i++) {
                        if (x[i] !== y[i]) {
                            xge = x[i] > y[i];
                            break;
                        }
                    }
                }
                if (xge) {
                    ra[p]++;
                    var ld = xl - yl;
                    for (i = yl - 1; i >= 0; i--) {
                        if (x[i + ld] < y[i]) {
                            x[i + ld - 1]--;
                            x[i + ld] += 10 - y[i];
                        } else {
                            x[i + ld] -= y[i];
                        }
                    }
                } else {
                    x.push(0);
                    p++;
                    ra[p] = 0;
                }
                while (x[0] === 0) {
                    x.shift();
                }
            } while (x.length !== 0 && p <= mp);
            while (p <= mp) {
                ra[++p] = 0;
            }
            ra.pop();
            var r = ppNewMbn(b);
            r._s *= a._s;
            r._d = ra;
            mbnRoundLast(r);
            return r;
        };
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        var pfMod = function (a, b) {
            var ba = pfAbs(b);
            var r = pfSub(a, pfMul(pfIntp(pfDiv(a, ba)), ba));
            if ((r._s * a._s) === -1) {
                r = pfSub(ba, pfAbs(r));
                r._s = a._s;
            }
            return r;
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfFloor = function (a) {
            var r = ppNewMbn(a);
            if (MbnP !== 0) {
                var ds = 0;
                for (var l = r._d.length - MbnP; l < r._d.length; l++) {
                    ds += r._d[l];
                    r._d[l] = 0;
                }
                if (r._s === -1 && ds > 0) {
                    r._d[r._d.length - MbnP - 1]++;
                }
                mbnCarry(r);
            }
            return r;
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfRound = function (a) {
            var r = ppNewMbn(a);
            if (MbnP !== 0) {
                var l = r._d.length - MbnP;
                r._d[l - 1] += (r._d[l] >= 5) ? 1 : 0;
                while (l < r._d.length) {
                    r._d[l++] = 0;
                }
                mbnCarry(r);
            }
            return r;
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfAbs=function (a) {
            var r= ppNewMbn(a);
            r._s *= r._s;
            return r;
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfInva = function (a) {
            var r = ppNewMbn(a);
            r._s = -r._s;
            return r;
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfInvm = function (a){
            return pfDiv(mbn1, a);
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfCeil = function (a) {
            return pfInva(pfFloor(pfInva(a)));
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfIntp = function (a) {
            return (a._s === 1) ? pfFloor(a) : pfCeil(a);
        };
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        var pfMin =function (a, b) {
            return ppNewMbn(pfCmp(a, b, mbn0) === -1 ? a : b);
        };
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        var pfMax = function (a, b) {
            return ppNewMbn(pfCmp(a, b, mbn0) === 1 ? a : b);
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfSqrt = function (a) {
            var t = pfMul(a, ppNewNumber(100));
            var r = ppNewMbn(t);
            if (r._s === -1) {
                throw new MbnErr("sqrt.negative_value", a);
            }
            if (r._s === 1) {
                var mbn2 = ppNewInt(2), diff = mbn0, lastDiff, cnt = 0;
                do {
                    cnt+=.01;
                    lastDiff = diff;
                    diff = pfSub(r, pfDiv(pfAdd(r, pfDiv(t, r)), mbn2));
                    r=pfSub(r, diff)
                    cnt += (diff._s * lastDiff._s === -1) ? 1 : 0;
                } while (diff._s !== 0 && cnt < 4);
                mbnRoundLast(r);
            }
            return r
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfSgn = function (a) {
            return ppNewInt(a._s);
        };
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        var pfPow = function (a, b) {
            var n = ppNewMbn(b);
            if (!pfIsInt(n)) {
                throw new MbnErr("pow.unsupported_exponent", n);
            }
            var ns = n._s;
            n._s *= n._s;
            var ni = n.toNumber();
            var rx = ppNewMbn(a);
            if (ns === -1 && pfCmp(pfAbs(rx), mbn1, mbn0) === -1) {
                rx = pfInvm(rx);
                ns = -ns;
            }
            var dd = 0;
            var cdd = 0;
            var r = mbn1;
            while (!pfIsInt(rx)) {
                rx._d.push(0);
                mbnCarry(rx);
                dd++;
            }
            while (true) {
                if (ni % 2 === 1) {
                    r = pfMul(r, rx)
                    cdd += dd;
                }
                ni = Math.floor(ni / 2);
                if (ni === 0) {
                    break;
                }
                rx = pfMul(rx, rx);
                dd *= 2;
            }
            if (cdd >= 1) {
                if (cdd > 1) {
                    r._d = r._d.slice(0, 1 - cdd);
                }
                mbnRoundLast(r);
            }
            if (ns === -1) {
                r = pfInvm(r)
            }
            return r;
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfFact = function (a) {
            if (!pfIsInt(a) || a._s === -1) {
                throw new MbnErr("fact.invalid_value", a);
            }
            var n = pfSub(a, mbn1), r = pfMax(a, mbn1);
            while (n._s === 1) {
                r = pfMul(r, n);
                n = pfSub(n, mbn1)
            }
            return r;
        };

        /**Sets value from b
         * @param {*} b
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.set = function (b) {
            return ppFun(pfSet, true, [this, b]);
        };
        /**Compare value with b, a.cmp(b)<=0 means a<=b
         * @param {*=} b
         * @param {*=} d Maximum difference treated as equality, default 0
         * @return {number} 1 if value > b, -1 if value < b, otherwise 0
         * @throws {MbnErr} negative maximal difference
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.cmp = function (b, d) {
            return pfFun(pfCmp, [this, b, d]);
        };
        /**Returns if value equals b
         * @param {*} b
         * @param {boolean=} d Maximum difference treated as equality, default 0
         * @return {boolean}
         * @throws {MbnErr} negative maximal difference
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.eq = function (b, d) {
            return pfFun(pfEq, [this, b, d]);
        };
        /**Returns if the number is integer
         * @return {boolean}
         */
        Mbn.prototype.isInt = function () {
            return pfFun(pfIsInt, [this]);
        };
        /**Add b to value
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.add = function (b, m) {
            return ppFun(pfAdd, m, [this, b]);
        };
        /**Subtract b from value
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.sub = function (b, m) {
            return ppFun(pfSub, m, [this, b]);
        };
        /**
         * Multiple value by b
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.mul = function (b, m) {
            return ppFun(pfMul, m, [this, b]);
        };
        /**Divide value by b
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} division by zero
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.div = function (b, m) {
            return ppFun(pfDiv, m, [this, b]);
        };
        /**Modulo, remainder of division value by b, keep sign of value
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws (MbnErr) division by zero
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.mod = function (b, m) {
            return ppFun(pfMod, m, [this, b]);
        };
        /**
         * Returns greatest integer value not greater than number
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.floor = function (m) {
            return ppFun(pfFloor, m, [this]);
        };
        /**Round number to closest integer value (half-up)
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn} */
        Mbn.prototype.round = function (m) {
            return ppFun(pfRound, m, [this]);
        };
        /**Absolute value
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.abs = function (m) {
            return ppFun(pfAbs, m, [this]);
        };
        /**Additive inverse of value
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.inva = function (m) {
            return ppFun(pfInva, m, [this]);
        };
        /**Returns multiplicative inverse of value
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} division by zero
         */
        Mbn.prototype.invm = function (m) {
            return ppFun(pfInvm, m, [this]);
        };
        /**Returns lowest integer value not lower than value
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.ceil = function (m) {
            return ppFun(pfCeil, m, [this]);
        };
        /**Returns integer part of number
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.intp = function (m) {
            return ppFun(pfIntp, m, [this]);
        };
        /**Returns minimum from value and b
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.min = function (b, m) {
            return ppFun(pfMin, m, [this, b]);
        };
        /**Returns maximum from value and b
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.max = function (b, m) {
            return ppFun(pfMax, m, [this, b]);
        };
        /**Returns square root of value
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} square root of negative number
         */
        Mbn.prototype.sqrt = function (m) {
            return ppFun(pfSqrt, m, [this]);
        };
        /**Returns sign from value, 1 - positive, -1 - negative, otherwise 0
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.sgn = function (m) {
            return ppFun(pfSgn, m, [this]);
        };
        /**Returns value to the power of b, b must be integer
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} not integer exponent
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.pow = function (b, m) {
            return ppFun(pfPow, m, [this, b]);
        };
        /**Returns factorial, value must be non-negative integer
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} value is not non-negative integer
         */
        Mbn.prototype.fact = function (m){
            return ppFun(pfFact, m ,[this]);
        }

        /**
         * Split value to array of values, with same ratios as in given array, or to given number of parts, default 2
         * @param {Array|*=} ar Ratios array or number of parts, default 2
         * @return {Array}
         * @throws {MbnErr} negative ratio, non-positve or not integer number of parts
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.split = function (ar) {
            var arr = [];
            var asum, n, i;
            if (ar === undefined) {
                ar = 2;
            }
            if (!(ar instanceof Array)) {
                asum = ppNewAny(ar);
                if (!asum.isInt()) {
                    throw new MbnErr("split.invalid_part_count", ar);
                }
                n = asum.toNumber();
                for (i = 0; i < n; i++) {
                    arr.push([i, mbn1]);
                }
            } else {
                var mulp = pfPow(ppNewNumber(10), ppNewNumber(MbnP));
                asum = ppNew0();
                n = ar.length;
                var sgns = [false, false, false];
                for (i = 0; i < n; i++) {
                    var ai = pfMul(ppNewAny(ar[i]), mulp);
                    sgns[ai._s + 1] = true;
                    asum = pfAdd(asum, ai);
                    arr.push([i, ai]);
                }
                if (sgns[0] && sgns[2]) {
                    arr.sort(function (a, b) {
                        return asum._s * pfCmp(a[1], b[1], mbn0);
                    });
                }
            }
            if (n <= 0) {
                throw new MbnErr("split.invalid_part_count", n);
            }
            if (asum._s === 0) {
                throw new MbnErr("split.zero_part_sum");
            }
            var a = ppNewMbn(this);
            var brr = [];
            brr.length = n;
            var v, idx;
            for (i = 0; i < n; i++) {
                idx = arr[i][0];
                v = arr[i][1];
                if (v._s === 0) {
                    brr[idx] = v;
                } else {
                    var b = pfDiv(pfMul(a,v),asum);
                    asum = pfSub(asum, v);
                    a = pfSub(a, b);
                    brr[idx] = b;
                }
            }
            return brr;
        };


        var fnReduce = {
            set: pfAdd, abs: pfAbs, inva: pfInva, invm: pfInvm, ceil: pfCeil, floor: pfFloor, sqrt: pfSqrt,
            round: pfRound, sgn: pfSgn, intp: pfIntp, fact: pfFact, min: pfMin, max: pfMax, add: pfAdd,
            sub: pfSub, mul: pfMul, div: pfDiv, mod: pfMod, pow: pfPow
        };
        /**
         * Runs function on each element, returns:
         * single value for 2 argument function (arr[0].fn(arr[1]).fn(arr[2]), ..)
         * array of products for 1 argument function [arr[0].fn(), arr[1].fn(), ..]
         * array of products for 2 argument function and when b is same size array or single value
         * [arr[0].fn(b[0]), arr[1].fn(b[1]), ..] or [arr[0].fn(b), arr[1].fn(b), ..]
         * @param {string} fn
         * @param {*} arr
         * @param {*=} b
         * @return {Mbn|Array}
         * @throws {MbnErr} invalid function name, wrong number of arguments, different array sizes
         * @throws {MbnErr} invalid argument format
         */
        Mbn.reduce = function (fn, arr, b) {
            if (!own(fnReduce, fn)) {
                throw new MbnErr("reduce.invalid_function", fn);
            }
            var inv = false;
            var f = fnReduce[fn];
            if (!(arr instanceof Array)) {
                if (!(b instanceof Array)) {
                    throw new MbnErr("reduce.no_array");
                }
                inv = b;
                b = arr;
                arr = inv;
            }
            var r, i;
            var arrl = arr.length;
            var mode = (fn === "set") ? 0 : f.length;
            var bmode = (arguments.length === 3) ? ((b instanceof Array) ? 2 : 1) : 0;
            if (mode !== 2 && bmode !== 0) {
                throw new MbnErr("reduce.invalid_argument_count");
            }
            if (mode === 2 && bmode === 0) {
                r = ppNewAny(arrl ? arr[0] : 0);
                for (i = 1; i < arrl; i++) {
                    r = f(r, ppNewAny(arr[i]))
                }
            } else {
                r = [];
                if (bmode === 2 && arrl !== b.length) {
                    throw new MbnErr("reduce.different_lengths", {"v": arrl, "w": b.length}, true);
                }
                var bv = (mode && (bmode === 1)) ? ppNewAny(b) : mbn0;
                for (i = 0; i < arrl; i++) {
                    var e = ppNewAny(arr[i]);
                    var bi = (bmode === 2) ? (ppNewAny(b[i])) : bv;
                    r.push(inv ? f(bi, e) : f(e, bi));
                }
            }
            return r;
        };

        var MbnConst = {
            PI: "3.1415926535897932384626433832795028841972",
            E: "2.7182818284590452353602874713526624977573",
            eps: true
        };

        var cnRx = /^[A-Za-z_]\w*/;
        /**
         * Sets and reads constant
         * @param {string|null} n Constant name, must start with letter or _
         * @param {*=} v Constant value to set
         * @return {Mbn|boolean}
         * @throws {MbnErr} undefined constant, constant already set, incorrect name
         * @throws {MbnErr} invalid argument format
         */
        Mbn.def = function (n, v) {
            var c = (n === null), o = own(MbnConst, c ? v : n);
            if (!cnRx.test(c ? v : n)) {
                throw new MbnErr("def.invalid_name", c ? v : n);
            }
            if (c) {
                return o;
            }
            if (arguments.length === 1) {
                if (!o) {
                    throw new MbnErr("def.undefined", n);
                }
                if (!(MbnConst[n] instanceof Mbn)) {
                    MbnConst[n] = (n === "eps") ? pfPow(ppNewInt(10), ppNewNumber(-MbnP)) : (ppNewString(MbnConst[n]));
                }
            } else {
                if (o) {
                    throw new MbnErr("def.already_set", {v: n, w: Mbn.def(n)}, true);
                }
                MbnConst[n] = ppNewAny(v);
            }
            return ppNewMbn(MbnConst[n]);
        };

        var fnEval = {
            abs: true, inva: false, ceil: true, floor: true, fact: true,
            sqrt: true, round: true, sgn: true, "int": "intp", div_100: "div_100"
        };
        var states = {
            endBop: ["bop", "pc", "fs"],
            uopVal: ["num", "name", "uop", "po"],
            fn: ["po"]
        };
        var ops = {
            "|": [1, true, "max"],
            "&": [2, true, "min"],
            "+": [3, true, "add"],
            "-": [3, true, "sub"],
            "*": [4, true, "mul"],
            "#": [4, true, "mod"],
            "/": [4, true, "div"],
            "^": [5, false, "pow"],
            "%": [7, true, "div_100"],
            "!": [7, true, "fact"],
            "inva": [6, true, "inva"],
            "fn": [7, true]
        };
        var rxs = {
            num: {rx: /^([0-9., ]+)\s*/, next: states.endBop},
            name: {rx: /^([A-Za-z_]\w*)\s*/},
            fn: {next: states.fn},
            vr: {next: states.endBop},
            bop: {rx: /^([-+*\/#^&|])\s*/, next: states.uopVal},
            uop: {rx: /^([-+])\s*/, next: states.uopVal},
            po: {rx: /^(\()\s*/, next: states.uopVal},
            pc: {rx: /^(\))\s*/, next: states.endBop},
            fs: {rx: /^([%!])\s*/, next: states.endBop}
        };

        var wsRx3 = /^[\s=]+/;
        /**
         * Evaluate expression
         * @param {string} exp Expression
         * @param {Object|boolean=} vars Object with vars for evaluation
         * @return {Mbn}
         * @throws {MbnErr} syntax error, operation error
         */
        Mbn.calc = function (exp, vars) {
            return new Mbn(exp, (vars instanceof Object) ? vars : true);
        };

        /**
         * Check expression, get names of used vars
         * @param {string} exp Expression
         * @param {boolean=} omitOptional Omit vars available as constans or results
         * @return {Array|boolean}
         */
        Mbn.check = function (exp, omitOptional) {
            try {
                var varName, vars = mbnCalc(exp, false, omitOptional === true), varNames = [];
                for (varName in vars) {
                    if (own(vars, varName)) {
                        varNames[vars[varName]] = varName;
                    }
                }
                return varNames;
            } catch (e) {
                return false;
            }
        };
        /**
         * Evaluate expression
         * @param {string} exp Expression
         * @param {Object|boolean|undefined} vars Object with vars for evaluation
         * @param {boolean|null} checkOmitOptional Omit vars available as constans or results
         * @return {Mbn|Object}
         * @throws {MbnErr} syntax error, operation error
         */
        var mbnCalc = function (exp, vars, checkOmitOptional) {
            var expr = String(exp), varsUsed = {size: 0, vars: {}}
            if (!(vars instanceof Object)) {
                vars = {};
            }
            var mtch, comStart, comEnd, i, j, results = {r0: new Mbn()};
            while (mtch = expr.match(/{+/)) {
                mtch = mtch[0];
                comStart = expr.indexOf(mtch);
                comEnd = expr.indexOf(mtch.replace(/{/g, "}"), comStart);
                expr = expr.slice(0, comStart) + ((comEnd === -1) ? "" : ("\t" + expr.slice(comEnd + mtch.length)))
            }
            var exprArr = expr.split(";");
            for (i = 0; i < exprArr.length; i++) {
                expr = exprArr[i].replace(wsRx3, "");
                results["r" + (i + 1)] = results.r0 = ((expr === "") ? results.r0
                   : mbnCalcSingle(expr, vars, results, varsUsed, checkOmitOptional));
                for (j = 0; j <= i; j++) {
                    results["r0" + (j + 1)] = results["r" + (i - j + 1)];
                }
            }
            return (checkOmitOptional === null) ? results.r0 : varsUsed.vars;
        };
        /**
         * Evaluate expression
         * @param {string} expr Expression
         * @param {Object} vars Object with vars for evaluation
         * @param {Object} results Object with used vars
         * @param {Object} varsUsed Object with used vars
         * @param {boolean|null} checkOmitOptional Bool: only check syntax and used vars
         * @return {Mbn|null}
         * @throws {MbnErr} syntax error, operation error
         */
        var mbnCalcSingle = function (expr, vars, results, varsUsed, checkOmitOptional) {
            var state = states.uopVal, rpns = [], rpno = [];
            var t, tok, mtch, i, rolp;
            while (expr !== "") {
                mtch = null;
                for (i = 0; i < state.length && mtch === null; i++) {
                    t = state[i];
                    mtch = expr.match(rxs[t].rx);
                }
                if (mtch !== null) {
                    tok = mtch[1];
                    expr = expr.slice(mtch[0].length);
                } else if (state === states.endBop && !expr.match(rxs.num.rx)) {
                    tok = "*";
                    t = "bop";
                } else {
                    throw new MbnErr("calc.unexpected", expr);
                }
                switch (t) {
                    case "num":
                        rpns.push(new Mbn(tok, false));
                        break;
                    case "name":
                        t = "vr";
                        if (own(fnEval, tok) && fnEval[tok] !== false) {
                            t = "fn";
                            rpno.push(ops.fn.concat([tok]));
                        } else if (checkOmitOptional !== null) {
                            if (!own(varsUsed.vars, tok) && (!checkOmitOptional || (!own(results, tok) && !Mbn.def(null, tok)))) {
                                varsUsed.vars[tok] = varsUsed.size++;
                            }
                        } else if (own(vars, tok)) {
                            if (!own(varsUsed.vars, tok)) {
                                varsUsed.vars[tok] = new Mbn(vars[tok]);
                            }
                            rpns.push(new Mbn(varsUsed.vars[tok]));
                        } else if (own(results, tok)) {
                            rpns.push(new Mbn(results[tok]));
                        } else if (Mbn.def(null, tok)) {
                            rpns.push(Mbn.def(tok));
                        } else {
                            throw new MbnErr("calc.undefined", tok);
                        }
                        break;
                    case "fs":
                    case "bop":
                        var op = ops[tok];
                        while ((rolp = rpno.pop()) !== undefined) {
                            if (rolp === "(" || (rolp[0] <= op[0] - (op[1] ? 1 : 0))) {
                                rpno.push(rolp);
                                break;
                            }
                            rpns.push(rolp[2]);
                        }
                        rpno.push(op);
                        break;
                    case "uop":
                        if (tok === "-") {
                            rpno.push(ops.inva);
                        }
                        break;
                    case "po":
                        rpno.push(tok);
                        break;
                    case "pc":
                        while ((rolp = rpno.pop()) !== "(") {
                            if (rolp === undefined) {
                                throw new MbnErr("calc.unexpected", ")");
                            }
                            rpns.push(rolp[2]);
                        }
                        break;
                    default:
                }

                state = rxs[t].next;
            }
            while ((rolp = rpno.pop()) !== undefined) {
                if (rolp === "(") {
                    throw new MbnErr("calc.unexpected", "(");
                }
                rpns.push(rolp[2]);
            }
            if (state !== states.endBop) {
                throw new MbnErr("calc.unexpected", "END");
            }

            if (checkOmitOptional !== null) {
                return null;
            }

            var rpn = [], tn;
            for (i = 0; i < rpns.length; i++) {
                tn = rpns[i];
                if (tn instanceof Mbn) {
                    rpn.push(tn);
                } else if (own(fnEval, tn)) {
                    if (typeof fnEval[tn] === "string") {
                        tn = fnEval[tn];
                        if (tn.indexOf("_") !== -1) {
                            tn = tn.split("_");
                            rpn[rpn.length - 1][tn[0]](tn[1], true);
                            continue;
                        }
                    }
                    rpn[rpn.length - 1][tn](true);
                } else {
                    rpn[rpn.length - 2][tn](rpn.pop(), true);
                }
            }
            return rpn[0];
        };
        return Mbn;
    };
    var Mbn = MbnCr();
    Mbn.extend = MbnCr;
    Mbn.MbnErr = MbnErr;
    return Mbn;
})();
if (typeof module === "object") {
    module.exports["default"] = module.exports = Mbn;
}
