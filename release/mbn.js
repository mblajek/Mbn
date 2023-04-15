/* Mbn v1.53.0 / 15.04.2023 | https://mbn.li | Copyright (c) 2016-2023 Mikołaj Błajek | https://mbn.li/LICENSE */
"use strict";

var Mbn = (function () {
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
    //default operations limit
    var MbnDO = 1e6;

    var errMessages = {
        invalid_argument: "invalid argument: %v%",
        invalid_format: "invalid format: %v%",
        limit_exceeded: "value exceeded %v% digits limit",
        operations_limit: "calculation exceeded %v% operations limit",
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
            invalid_limit: "invalid digit limit (positive int): %v%",
            invalid_operations: "invalid operations limit (positive int): %v%"
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
    /**@param {Object} val
     * @param {*} key
     * @returns {boolean}
     */
    var wsRx2 = /^\s*(=)?[\s=]*([-+])?\s*((?:[\s\S]*\S)?)/;
    var wsRx3 = /^[\s=]+/;
    var cnRx = /^[A-Za-z_]\w*/;
    var stateEval = {
        endBop: ["bop", "pc", "fs"],
        uopVal: ["num", "name", "uop", "po"],
        fn: ["po"]
    };
    var rxEval = {
        num: {rx: /^([0-9., ]+)\s*/, next: stateEval.endBop},
        name: {rx: /^([A-Za-z_]\w*)\s*/},
        fn: {next: stateEval.fn},
        vr: {next: stateEval.endBop},
        bop: {rx: /^([-+*\/#^&|])\s*/, next: stateEval.uopVal},
        uop: {rx: /^([-+])\s*/, next: stateEval.uopVal},
        po: {rx: /^(\()\s*/, next: stateEval.uopVal},
        pc: {rx: /^(\))\s*/, next: stateEval.endBop},
        fs: {rx: /^([%!])\s*/, next: stateEval.endBop}
    };
    var own = function (val, key) {
        return errMessages.hasOwnProperty.call(val, key)
    }
    var ioArr = function (a) {
        return (a instanceof Array);
    };
    var ioObj = function (a) {
        return (a instanceof Object);
    }

    /**Common error message object
     * @export
     * @constructor
     * @property {string} message
     * @property {string} errorKey
     * @property {Object} errorValues
     */
    var MbnErr = function (errorKey, errorValues, message) {
        this.errorKey = errorKey;
        this.errorValues = errorValues;
        this.message = message;
    };
    var valFlatToMsgString = function (val) {
        return (typeof val === "string") ? ("\"" + val + "\"") : String(val);
    }
    var valToMsgString = function (val) {
        if (ioArr(val)) {
            var valArr = [], i;
            for (i = 0; i < val.length && i < 20; i++) {
                valArr.push(ioArr(val[i]) ? "[..]" : valFlatToMsgString(val[i]));
            }
            return "[" + valArr.join() + "]";
        }
        return valFlatToMsgString(val);
    };
    /**@param {string} key error code
     * @param {*=} values incorrect value to message
     * @param {boolean=} multi passing array with multiple values
     */
    var throwMbnErr = function (key, values, multi) {
        var errorValues = {}, errorKey = "mbn." + key;
        var i, val;
        if (arguments.length !== 1) {
            if (!ioObj(values) || multi !== true) {
                values = {v: values};
            }
            for (i in values) {
                if (own(values, i)) {
                    val = valToMsgString(values[i]);
                    errorValues[i] = ((val.length > 20) ? (val.slice(0, 18) + "..") : val);
                }
            }
        }
        var msg = null;
        if (typeof errTranslation === "function") {
            try {
                msg = errTranslation(errorKey, errorValues)
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
            var subMessages = errMessages;
            for (i = 0; i < keyArrLength; i++) {
                var word = keyArr[i];
                var nextSubMessages = subMessages[word];
                if (ioObj(nextSubMessages) && own(nextSubMessages, "_")) {
                    nextSubMessages = subMessages[nextSubMessages._];
                }
                subMessages = nextSubMessages;
            }
            msg += " error: " + subMessages;
        }
        for (i in errorValues) {
            if (own(errorValues, i)) {
                msg = msg.replace("%" + i + "%", errorValues[i]);
            }
        }
        throw new MbnErr(errorKey, errorValues, msg);
    };

    MbnErr.prototype.toString = function () {
        return this.message;
    };
    /**Translation for errors
     * @param {function} translation
     */
    MbnErr.translate = function (translation) {
        errTranslation = translation;
    };
    /**fill options with default parameters and check
     * @param opt {Object} params by reference
     * @param MbnDP {number} default precision
     * @param MbnDS {string} default separator
     * @param MbnDT {boolean} default truncation
     * @param MbnDE {boolean|null} default evaluating
     * @param MbnDF {boolean} default formatting
     * @param MbnDL {number} default digit limit
     * @param MbnDO {number} default operations limit
     * @param fname name of function for exception
     * @throws {MbnErr} invalid options
     * @return {Object} checked and filled class options
     */
    var prepareOpt = function (opt, MbnDP, MbnDS, MbnDT, MbnDE, MbnDF, MbnDL, MbnDO, fname) {
        var MbnP = MbnDP, MbnS = MbnDS, MbnT = MbnDT, MbnE = MbnDE, MbnF = MbnDF, MbnL = MbnDL, MbnO = MbnDO;
        if (own(opt, "MbnP")) {
            MbnP = opt.MbnP;
            if ((typeof MbnP !== "number") || MbnP < 0 || !isFinite(MbnP) || Math.round(MbnP) !== MbnP) {
                throwMbnErr(fname + "invalid_precision", MbnP);
            }
        }
        if (own(opt, "MbnS")) {
            MbnS = opt.MbnS;
            if (MbnS !== "." && MbnS !== ",") {
                throwMbnErr(fname + "invalid_separator", MbnS);
            }
        }
        if (own(opt, "MbnT")) {
            MbnT = opt.MbnT;
            if (MbnT !== true && MbnT !== false) {
                throwMbnErr(fname + "invalid_truncation", MbnT);
            }
        }
        if (own(opt, "MbnE")) {
            MbnE = opt.MbnE;
            if (MbnE !== true && MbnE !== false && MbnE !== null) {
                throwMbnErr(fname + "invalid_evaluating", MbnE);
            }
        }
        if (own(opt, "MbnF")) {
            MbnF = opt.MbnF;
            if (MbnF !== true && MbnF !== false) {
                throwMbnErr(fname + "invalid_formatting", MbnF);
            }
        }
        if (own(opt, "MbnL")) {
            MbnL = opt.MbnL;
            if ((typeof MbnL !== "number") || MbnL <= 0 || !isFinite(MbnL) || Math.round(MbnL) !== MbnL) {
                throwMbnErr(fname + "invalid_limit", MbnL);
            }
        }
        if (own(opt, "MbnO")) {
            MbnO = opt.MbnO;
            if ((typeof MbnO !== "number") || MbnO <= 0 || !isFinite(MbnO) || Math.round(MbnO) !== MbnO) {
                throwMbnErr(fname + "invalid_operations", MbnO);
            }
        }
        return {MbnV: MbnV, MbnP: MbnP, MbnS: MbnS, MbnT: MbnT, MbnE: MbnE, MbnF: MbnF, MbnL: MbnL, MbnO: MbnO};
    };

    /**
     * Function returns constructor of Mbn objects
     * @export
     * @param {number|Object=} opt precision or object with params
     * @throws {MbnErr} invalid class options
     */
    var MbnCr = function (opt) {
        if (!ioObj(opt)) {
            opt = (opt !== undefined) ? {MbnP: opt} : {};
        }
        opt = prepareOpt(opt, MbnDP, MbnDS, MbnDT, MbnDE, MbnDF, MbnDL, MbnDO, "extend.");
        var MbnP = opt.MbnP, MbnS = opt.MbnS, MbnT = opt.MbnT, MbnE = opt.MbnE;
        var MbnF = opt.MbnF, MbnL = opt.MbnL, MbnO = opt.MbnO;
        var numOpt = {MbnP: MbnP, MbnS: ".", MbnT: MbnT, MbnE: MbnE, MbnF: false, MbnL: MbnL, MbnO: MbnO};
        var mbn0d = [0];
        while (mbn0d.length <= MbnP) {
            mbn0d.push(0);
        }
        var oc = 0;
        var oi = function (n) {
            oc += (n || 1)
            if (oc > MbnO) {
                throwMbnErr('operations_limit', MbnO);
            }
        }
        /**Fix digits bigger than 9, remove leading zeros
         * @param {Mbn} a*/
        var ppCarry = function (a) {
            var ad = a._d;
            var adlm1 = ad.length - 1;
            var i = adlm1;
            var adi, adid, adic;
            oi(2 * i);
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
                throwMbnErr("limit_exceeded", MbnL);
            }

        };
        /**Remove last digit
         * @param {Mbn} a*/
        var ppRoundLast = function (a) {
            var d = a._d;
            if (d.length < 2) {
                d.unshift(0);
            }
            if (d.pop() >= 5) {
                d[d.length - 1]++;
            }
            ppCarry(a);
        };
        /**@param {*} n
         * @param {Mbn=} a
         * @param {Object|boolean=} v
         * @return {Mbn}*/
        var ppNewReset = function (n, a, v) {
            oi(MbnP);
            a._s = 0;
            a._d = mbn0d.slice();
            return a;
        }
        /**@param {*} n
         * @param {Mbn=} a
         * @param {Object|boolean=} v
         * @return {Mbn}*/
        var ppNewAny = ppNewReset;
        /**Constructor of Mbn object
         * @export
         * @constructor
         * @property {number} _s
         * @property {number[]} _d
         * @param {*=} n Value, default 0
         * @param {Object|boolean=} v Object with vars for evaluation
         * @throws {MbnErr} invalid argument, invalid format, calc error
         */
        var Mbn = function (n, v) {
            if (!ioMbn(this)) {
                return new Mbn(n, v);
            }
            oc = 0;
            ppNewAny(n, this, v);
        };
        var ioMbn = function (a) {
            return (a instanceof Mbn);
        };
        var mbn0 = Mbn(0);
        /**@param {Mbn=} a
         * @return {Mbn}*/
        var ppNew0 = own(Object, "setPrototypeOf") ? (function (a) {
            return ppNewReset(0, a || Object.setPrototypeOf({}, mbn0));
        }) : function (a) {
            return ppNewReset(0, a || {__proto__: mbn0});
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
            oi(mbn._d.length);
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
            if (!isFinite(n)) {
                if (isNaN(n)) {
                    throwMbnErr("invalid_argument", n);
                }
                throwMbnErr("limit_exceeded", MbnL);
            }
            var d = [], s = 1;
            if (n < 0) {
                n *= (s = -1);
            }
            var ni = Math.floor(n);
            var nf = n - ni;
            var c, i;
            do {
                c = Math.round(ni % 10);
                ni = Math.round((ni - c) / 10);
                d.unshift(c);
            } while (ni > 0);
            for (i = 0; i <= MbnP; i++) {
                nf *= 10;
                c = Math.min(Math.floor(nf), 9);
                nf -= c;
                d.push(c);
            }
            mbn._d = d;
            mbn._s = s;
            ppRoundLast(a);
            return mbn;
        };
        /**@param {Mbn} n
         * @param {Mbn=} a
         * @return {Mbn}*/
        var ppNewMbn = function (n, a) {
            var mbn = ppNew0(a);
            mbn._s = n._s;
            mbn._d = n._d.slice();
            oi(mbn._d.length);
            return mbn;
        };
        /**@param {string} n
         * @param {Mbn=} a
         * @param {Object|boolean=} v
         * @return {Mbn}*/
        var ppNewString = function (n, a, v) {
            var mbn = ppNew0(a);
            var np = n.match(wsRx2), nn = np[3];
            var d = [], s = 1;
            if (np[2] === "-") {
                s = -1;
            }
            var ln = ((nn.indexOf(".") + 1) || (nn.indexOf(",") + 1)) - 1;
            var nl = nn.length;
            var al = nl;
            if (ln === -1) {
                ln = nl;
            } else {
                al = ln + 1;
            }
            var l = Math.max(al + MbnP, nl);
            var i, c, cs = 0;
            for (i = 0; i <= l; i++) {
                c = (i < nl) ? (nn.charCodeAt(i) - 48) : 0;
                if (c >= 0 && c <= 9) {
                    if (i <= al + MbnP) {
                        d.push(c);
                    }
                } else if (!((i === ln && nl !== 1) || (c === -16 && i > cs && (i + 1) < ln))) {
                    if (v === true || ioObj(v) || (v !== false && (MbnE === true || (MbnE === null && np[1] === "=")))) {
                        ppNewMbn(ppCalcFull(n, v, null), mbn);
                        return mbn;
                    }
                    throwMbnErr("invalid_format", n);
                } else if (c === -16) {
                    cs = i + 1;
                }
            }
            mbn._d = d;
            mbn._s = s;
            ppRoundLast(mbn);
            return mbn;
        };
        /**@param {Object} n
         * @param {Mbn=} a
         * @param {Object|boolean=} v
         * @return {Mbn}*/
        var ppNewObject = function (n, a, v) {
            var mbn = a || ppNew0();
            if (ioMbn(n)) {
                return ppNewMbn(n, mbn)
            } else if (n && n.toString === Array.prototype.toString) {
                throwMbnErr("invalid_argument", n);
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
                    throwMbnErr("invalid_argument", n);
            }
        };
        /**@param {function} fun
         * @param {Array} argv
         * @param {boolean=} nc
         * @return {*}*/
        var pfFun = function (fun, argv, nc) {
            oc = 0;
            var argvMbn = [argv[0]];
            for (var i = 1; i < argv.length; i++) {
                var arg = argv[i];
                argvMbn.push((ioMbn(arg) || nc) ? arg : ppNewAny(arg))
            }
            return fun.apply(null, argvMbn);
        }
        /**@param {function} fun
         * @param {Array} argv
         * @param {boolean=} m
         * @return {Mbn}*/
        var ppFun = function (fun, argv, m) {
            return ppNewMbn(pfFun(fun, argv), (m === true) ? argv[0] : null);
        }
        /**@param {Mbn} a
         * @param {Object|boolean=} o
         * @return {string}*/
        var pfFormat = function (a, o) {
            if (!ioObj(o)) {
                o = {MbnF: o === undefined || o};
            }
            o = (o === opt || o === numOpt) ? o : prepareOpt(o, MbnP, MbnS, MbnT, MbnE, MbnF, MbnL, MbnO, "format.");
            var p = o.MbnP, v = a, li = a._d.length - MbnP, i;
            if (p < MbnP) {
                var b = ppNewMbn(a);
                var bl = b._d.length;
                if (p < MbnP - 1) {
                    b._d = b._d.slice(0, bl - MbnP + p + 1);
                }
                ppRoundLast(b);
                bl = b._d.length;
                if (bl - p > li) {
                    b._d = b._d.slice(bl - p - li);
                }
                v = b;
            }
            var di = v._d.slice(0, li);
            if (o.MbnF) {
                var dl = di.length;
                for (i = 0; 3 * i < dl - 3; i++) {
                    di.splice(-3 - 4 * i, 0, " ");
                }
            }
            var df = v._d.slice(li);
            if (p > MbnP && !o.MbnT) {
                for (i = 0; i < p - MbnP; i++) {
                    df.push(0);
                }
            }
            if (o.MbnT) {
                for (i = df.length - 1; i >= 0; i--) {
                    if (df[i] !== 0) {
                        break;
                    }
                }
                df = df.slice(0, i + 1);
            }
            var r = ((a._s < 0) ? "-" : "") + di.join("");
            if (df.length > 0) {
                r += o.MbnS + df.join("");
            }
            return r;
        };
        /**@param {Mbn} a
         * @return {string}*/
        var pfToString = function (a) {
            return pfFormat(a, opt);
        };
        /**@param {Mbn} a
         * @return {number}*/
        var pfToNumber = function (a) {
            return Number(pfFormat(a, numOpt));
        };
        /**@param {Mbn} a
         * @param {Mbn} b
         * @param {Mbn} d
         * @return {number}*/
        var pfCmp = function (a, b, d) {
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
                oi(bl);
                for (var i = 0; i < bl; i++) {
                    if (a._d[i] !== b._d[i]) {
                        return (a._d[i] > b._d[i]) ? a._s : -a._s;
                    }
                }
                return 0;
            }
            if (d._s === -1) {
                throwMbnErr("cmp.negative_diff", d);
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
         * @return {boolean}*/
        var pfIsInt = function (a) {
            oi(MbnP);
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
                    ppNewMbn(a, r);
                } else if (b._s === a._s) {
                    var ld = a._d.length - b._d.length;
                    if (ld < 0) {
                        b = a;
                        ld = -ld;
                    } else {
                        ppNewMbn(a, r);
                    }
                    for (var i = 0; i < r._d.length; i++) {
                        if (i >= ld) {
                            r._d[i] += b._d[i - ld];
                        }
                    }
                    ppCarry(r);
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
                ppNewMbn(a, r);
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
                        ppNewMbn(a, r);
                    }
                    for (var i = 0; i < r._d.length; i++) {
                        if (i >= ld) {
                            r._d[i] -= b._d[i - ld];
                        }
                    }
                    r._s = cmp * a._s;
                    ppCarry(r);
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
            oi(a._d.length * b._d.length);
            for (var i = 0; i < a._d.length; i++) {
                for (var j = 0; j < b._d.length; j++) {
                    r._d[i + j] = a._d[i] * b._d[j] + (r._d[i + j] || 0);
                }
            }
            r._s = a._s * b._s;
            ppCarry(r);
            if (MbnP >= 1) {
                if (MbnP > 1) {
                    r._d = r._d.slice(0, 1 - MbnP);
                }
                ppRoundLast(r);
            }
            return r;
        };
        /**@param {Mbn} a
         * @param {Mbn} b
         * @return {Mbn}*/
        var pfDiv = function (a, b) {
            if (b._s === 0) {
                throwMbnErr("div.zero_divisor");
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
                    oi(xl);
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
                    oi(yl);
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
            ppRoundLast(r);
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
                ppCarry(r);
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
                ppCarry(r);
            }
            return r;
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfAbs = function (a) {
            var r = ppNewMbn(a);
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
        var pfInvm = function (a) {
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
        var pfMin = function (a, b) {
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
            var t = pfMul(a, ppNewInt(100));
            var r = ppNewMbn(t);
            if (r._s === -1) {
                throwMbnErr("sqrt.negative_value", a);
            }
            if (r._s === 1) {
                var mbn2 = ppNewInt(2), diff = mbn0, lastDiff, cnt = 0;
                do {
                    lastDiff = diff;
                    diff = pfSub(r, pfDiv(pfAdd(r, pfDiv(t, r)), mbn2));
                    r = pfSub(r, diff)
                    cnt += (diff._s * lastDiff._s === -1) ? 1 : 0;
                } while (diff._s !== 0 && cnt < 4);
                ppRoundLast(r);
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
                throwMbnErr("pow.unsupported_exponent", n);
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
                ppCarry(rx);
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
                ppRoundLast(r);
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
                throwMbnErr("fact.invalid_value", a);
            }
            var n = pfSub(a, mbn1), r = pfMax(a, mbn1);
            while (n._s === 1) {
                r = pfMul(r, n);
                n = pfSub(n, mbn1)
            }
            return r;
        };
        /**@param {Mbn} a
         * @param {Array|number=} ar
         * @return {Array}*/
        var pfSplit = function (a, ar) {
            var arr = [];
            var asum, n, i;
            if (ar === undefined) {
                ar = 2;
            }
            if (!(ioArr(ar))) {
                asum = ppNewAny(ar);
                if (!asum.isInt()) {
                    throwMbnErr("split.invalid_part_count", ar);
                }
                n = asum.toNumber();
                for (i = 0; i < n; i++) {
                    arr.push([i, mbn1]);
                }
            } else {
                var mulp = pfPow(ppNewInt(10), ppNewNumber(MbnP));
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
                throwMbnErr("split.invalid_part_count", n);
            }
            if (asum._s === 0) {
                throwMbnErr("split.zero_part_sum");
            }
            var r = ppNewMbn(a);
            var brr = [];
            brr.length = n;
            var v, idx;
            for (i = 0; i < n; i++) {
                idx = arr[i][0];
                v = arr[i][1];
                if (v._s === 0) {
                    brr[idx] = v;
                } else {
                    var b = pfDiv(pfMul(r, v), asum);
                    asum = pfSub(asum, v);
                    r = pfSub(r, b);
                    brr[idx] = b;
                }
            }
            return brr;
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfDiv100 = function (a) {
            return pfDiv(a, ppNewInt(100));
        };
        /**@param {Mbn} a
         * @return {Mbn}*/
        var pfZero = function (a) {
            return ppNewInt((a._s === 0) ? 1 : 0);
        };
        var fnReduce = {
            set: pfAdd, abs: pfAbs, inva: pfInva, invm: pfInvm, ceil: pfCeil, floor: pfFloor, sqrt: pfSqrt,
            round: pfRound, sgn: pfSgn, intp: pfIntp, fact: pfFact, min: pfMin, max: pfMax, add: pfAdd,
            sub: pfSub, mul: pfMul, div: pfDiv, mod: pfMod, pow: pfPow
        };
        var MbnConst = {
            PI: "3.1415926535897932384626433832795028841972",
            E: "2.7182818284590452353602874713526624977573",
            eps: true
        };
        var fnEval = {
            abs: pfAbs, ceil: pfCeil, floor: pfFloor, fact: pfFact, sqrt: pfSqrt,
            round: pfRound, sgn: pfSgn, "int": pfIntp, "zero": pfZero
        };
        var opEval = {
            "|": [1, true, pfMax],
            "&": [2, true, pfMin],
            "+": [3, true, pfAdd],
            "-": [3, true, pfSub],
            "*": [4, true, pfMul],
            "#": [4, true, pfMod],
            "/": [4, true, pfDiv],
            "^": [5, false, pfPow],
            "%": [7, true, pfDiv100],
            "!": [7, true, pfFact],
            "inva": [6, true, pfInva],
            "fn": [7, true]
        };
        /**@param {string|null} n
         * @param {*=} v
         * @return {Mbn|boolean}
         */
        var ppDef = function (n, v) {
            var c = (n === null), o = own(MbnConst, c ? v : n);
            if (!cnRx.test(c ? v : n)) {
                throwMbnErr("def.invalid_name", c ? v : n);
            }
            if (c) {
                return o;
            }
            if (arguments.length === 1) {
                if (!o) {
                    throwMbnErr("def.undefined", n);
                }
                if (!ioMbn(MbnConst[n])) {
                    MbnConst[n] = (n === "eps") ? pfPow(ppNewInt(10), ppNewNumber(-MbnP)) : (ppNewString(MbnConst[n]));
                }
            } else {
                if (o) {
                    throwMbnErr("def.already_set", {v: n, w: ppDef(n)}, true);
                }
                MbnConst[n] = ppNewAny(v);
            }
            return ppNewMbn(MbnConst[n]);
        }
        /**@param {string} fn
         * @param {*} arr
         * @param {*=} b
         * @return {Mbn|Array}
         */
        var pfReduce = function (fn, arr, b) {
            if (!own(fnReduce, fn)) {
                throwMbnErr("reduce.invalid_function", fn);
            }
            var inv = false;
            var f = fnReduce[fn];
            if (!ioArr(arr)) {
                if (!ioArr(b)) {
                    throwMbnErr("reduce.no_array");
                }
                inv = b;
                b = arr;
                arr = inv;
            }
            var r, i;
            var arrl = arr.length;
            var mode = (fn === "set") ? 0 : f.length;
            var bmode = (arguments.length === 3) ? (ioArr(b) ? 2 : 1) : 0;
            if (mode !== 2 && bmode !== 0) {
                throwMbnErr("reduce.invalid_argument_count");
            }
            if (mode === 2 && bmode === 0) {
                r = ppNewAny(arrl ? arr[0] : 0);
                for (i = 1; i < arrl; i++) {
                    r = f(r, ppNewAny(arr[i]))
                }
            } else {
                r = [];
                if (bmode === 2 && arrl !== b.length) {
                    throwMbnErr("reduce.different_lengths", {"v": arrl, "w": b.length}, true);
                }
                var bv = (mode && (bmode === 1)) ? ppNewAny(b) : mbn0;
                for (i = 0; i < arrl; i++) {
                    var e = ppNewAny(arr[i]);
                    var bi = (bmode === 2) ? (ppNewAny(b[i])) : bv;
                    r.push(inv ? f(bi, e) : f(e, bi));
                }
            }
            return r;
        }
        /**Evaluate expression with comments and parts
         * @param {string} exp Expression
         * @param {Object|boolean|undefined} vars Object with vars for evaluation
         * @param {boolean|null} checkOmitOptional Omit vars available as constans or results
         * @return {Mbn|Object}
         * @throws {MbnErr} syntax error, operation error
         */
        var ppCalcFull = function (exp, vars, checkOmitOptional) {
            var expr = String(exp), varsUsed = {size: 0, vars: {}}
            if (!ioObj(vars)) {
                vars = {};
            }
            var mtch, comStart, comEnd, i, j, results = {r0: ppNew0()};
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
                    : ppCalcSingle(expr, vars, results, varsUsed, checkOmitOptional));
                for (j = 0; j <= i; j++) {
                    results["r0" + (j + 1)] = results["r" + (i - j + 1)];
                }
            }
            return (checkOmitOptional === null) ? results.r0 : varsUsed.vars;
        };
        /**Evaluate single expression
         * @param {string} expr Expression
         * @param {Object} vars Object with vars for evaluation
         * @param {Object} results Object with used vars
         * @param {Object} varsUsed Object with used vars
         * @param {boolean|null} checkOmitOptional Bool: only check syntax and used vars
         * @return {Mbn|null}
         * @throws {MbnErr} syntax error, operation error
         */
        var ppCalcSingle = function (expr, vars, results, varsUsed, checkOmitOptional) {
            var state = stateEval.uopVal, rpns = [], rpno = [];
            var t, tok, mtch, i, rolp;
            while (expr !== "") {
                mtch = null;
                oi(state.length);
                for (i = 0; i < state.length && mtch === null; i++) {
                    t = state[i];
                    mtch = expr.match(rxEval[t].rx);
                }
                if (mtch !== null) {
                    tok = mtch[1];
                    expr = expr.slice(mtch[0].length);
                } else if (state === stateEval.endBop && !expr.match(rxEval.num.rx)) {
                    tok = "*";
                    t = "bop";
                } else {
                    throwMbnErr("calc.unexpected", expr);
                }
                switch (t) {
                    case "num":
                        rpns.push(new Mbn(tok, false));
                        break;
                    case "name":
                        t = "vr";
                        if (own(fnEval, tok)/* && fnEval[tok] !== false*/) {
                            t = "fn";
                            rpno.push(opEval.fn.concat(fnEval[tok]));
                        } else if (checkOmitOptional !== null) {
                            if (!own(varsUsed.vars, tok) && (!checkOmitOptional || (!own(results, tok) && !ppDef(null, tok)))) {
                                varsUsed.vars[tok] = varsUsed.size++;
                            }
                        } else if (own(vars, tok)) {
                            if (!own(varsUsed.vars, tok)) {
                                varsUsed.vars[tok] = ppNewAny(vars[tok]);
                            }
                            rpns.push(new Mbn(varsUsed.vars[tok]));
                        } else if (own(results, tok)) {
                            rpns.push(new Mbn(results[tok]));
                        } else if (ppDef(null, tok)) {
                            rpns.push(ppDef(tok));
                        } else {
                            throwMbnErr("calc.undefined", tok);
                        }
                        break;
                    case "fs":
                    case "bop":
                        var op = opEval[tok];
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
                            rpno.push(opEval.inva);
                        }
                        break;
                    case "po":
                        rpno.push(tok);
                        break;
                    case "pc":
                        while ((rolp = rpno.pop()) !== "(") {
                            if (rolp === undefined) {
                                throwMbnErr("calc.unexpected", ")");
                            }
                            rpns.push(rolp[2]);
                        }
                        break;
                    default:
                }

                state = rxEval[t].next;
            }
            while ((rolp = rpno.pop()) !== undefined) {
                if (rolp === "(") {
                    throwMbnErr("calc.unexpected", "(");
                }
                rpns.push(rolp[2]);
            }
            if (state !== stateEval.endBop) {
                throwMbnErr("calc.unexpected", "END");
            }

            if (checkOmitOptional !== null) {
                return null;
            }

            var rpn = [], tn, b;
            for (i = 0; i < rpns.length; i++) {
                tn = rpns[i];
                if (ioMbn(tn)) {
                    rpn.push(tn);
                } else {
                    b = (tn.length === 2) ? rpn.pop() : null;
                    rpn[rpn.length - 1] = tn(rpn[rpn.length - 1], b);
                }
            }
            return rpn[0];
        };
        /**@param {string} exp
         * @param {Object=} vars
         * @return {Mbn}
         */
        var pfCalcEval = function (exp, vars) {
            return ppNewAny(exp, null, ioObj(vars) ? vars : true)
        }
        /**@param {string} exp
         * @param {boolean=} omitOptional Omit vars available as constans or results
         * @return {Array|boolean}
         */
        var pfCalcCheck = function (exp, omitOptional) {
            try {
                var varName, vars = ppCalcFull(exp, false, omitOptional === true), varNames = [];
                for (varName in vars) {
                    if (own(vars, varName)) {
                        varNames[vars[varName]] = varName;
                    }
                }
                return varNames;
            } catch (e) {
                return false;
            }
        }


        /**Returns string value
         * @return string
         */
        Mbn.prototype.toString = function () {
            return pfFun(pfToString, [this]);
        };
        /**
         * Returns reformatted string value
         * @param {boolean|Object=} opt thousand grouping or object with params, default true
         * @return {string}
         */
        Mbn.prototype.format = function (opt) {
            return pfFun(pfFormat, [this, opt], true);
        };
        /**
         * Returns number value
         * @return {number}
         */
        Mbn.prototype.toNumber = function () {
            return pfFun(pfToNumber, [this]);
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
        /**Sets value from b
         * @param {*} b
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.set = function (b) {
            return ppFun(pfSet, [this, b], true);
        };
        /**Add b to value
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.add = function (b, m) {
            return ppFun(pfAdd, [this, b], m);
        };
        /**Subtract b from value
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.sub = function (b, m) {
            return ppFun(pfSub, [this, b], m);
        };
        /**
         * Multiple value by b
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.mul = function (b, m) {
            return ppFun(pfMul, [this, b], m);
        };
        /**Divide value by b
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} division by zero
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.div = function (b, m) {
            return ppFun(pfDiv, [this, b], m);
        };
        /**Modulo, remainder of division value by b, keep sign of value
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws (MbnErr) division by zero
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.mod = function (b, m) {
            return ppFun(pfMod, [this, b], m);
        };
        /**
         * Returns greatest integer value not greater than number
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.floor = function (m) {
            return ppFun(pfFloor, [this], m);
        };
        /**Round number to the closest integer value (half-up/away-from-zero)
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn} */
        Mbn.prototype.round = function (m) {
            return ppFun(pfRound, [this], m);
        };
        /**Absolute value
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.abs = function (m) {
            return ppFun(pfAbs, [this], m);
        };
        /**Additive inverse of value
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.inva = function (m) {
            return ppFun(pfInva, [this], m);
        };
        /**Returns multiplicative inverse of value
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} division by zero
         */
        Mbn.prototype.invm = function (m) {
            return ppFun(pfInvm, [this], m);
        };
        /**Returns lowest integer value not lower than value
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.ceil = function (m) {
            return ppFun(pfCeil, [this], m);
        };
        /**Returns integer part of number
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.intp = function (m) {
            return ppFun(pfIntp, [this], m);
        };
        /**Returns minimum from value and b
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.min = function (b, m) {
            return ppFun(pfMin, [this, b], m);
        };
        /**Returns maximum from value and b
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.max = function (b, m) {
            return ppFun(pfMax, [this, b], m);
        };
        /**Returns square root of value
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} square root of negative number
         */
        Mbn.prototype.sqrt = function (m) {
            return ppFun(pfSqrt, [this], m);
        };
        /**Returns sign from value, 1 - positive, -1 - negative, otherwise 0
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         */
        Mbn.prototype.sgn = function (m) {
            return ppFun(pfSgn, [this], m);
        };
        /**Returns value to the power of b, b must be integer
         * @param {*} b
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} not integer exponent
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.pow = function (b, m) {
            return ppFun(pfPow, [this, b], m);
        };
        /**Returns factorial, value must be non-negative integer
         * @param {boolean=} m Modify original variable, default false
         * @return {Mbn}
         * @throws {MbnErr} value is not non-negative integer
         */
        Mbn.prototype.fact = function (m) {
            return ppFun(pfFact, [this], m);
        }
        /**Split value to array of values, with same ratios as in given array, or to given number of parts, default 2
         * @param {Array|*=} ar Ratios array or number of parts, default 2
         * @return {Array}
         * @throws {MbnErr} negative ratio, non-positve or not integer number of parts
         * @throws {MbnErr} invalid argument format
         */
        Mbn.prototype.split = function (ar) {
            return pfFun(pfSplit, [this, ar], true);
        };
        /**Properties of Mbn class
         * @return {Object} properties
         */
        Mbn.prop = function () {
            return {MbnV: MbnV, MbnP: MbnP, MbnS: MbnS, MbnT: MbnT, MbnE: MbnE, MbnF: MbnF, MbnL: MbnL, MbnO: MbnO};
        };
        /**Sets and reads constant
         * @param {string|null} n Constant name, must start with letter or _
         * @param {*=} v Constant value to set
         * @return {Mbn|boolean}
         * @throws {MbnErr} undefined constant, constant already set, incorrect name
         * @throws {MbnErr} invalid argument format
         */
        Mbn.def = function (n, v) {
            return pfFun(ppDef, [n, v].slice(0, arguments.length), true);
        };
        /**Runs function on each element, returns:
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
            return pfFun(pfReduce, [fn, arr, b].slice(0, arguments.length), true);
        };
        /**Evaluate expression
         * @param {string} exp Expression
         * @param {Object|boolean=} vars Object with vars for evaluation
         * @return {Mbn}
         * @throws {MbnErr} syntax error, operation error
         */
        Mbn.calc = function (exp, vars) {
            return pfFun(pfCalcEval, [exp, vars], true);
        };
        /**Check expression, get names of used vars
         * @param {string} exp Expression
         * @param {boolean=} omitOptional Omit vars available as constans or results
         * @return {Array|boolean}
         */
        Mbn.check = function (exp, omitOptional) {
            return pfFun(pfCalcCheck, [exp, omitOptional], true);
        }
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
