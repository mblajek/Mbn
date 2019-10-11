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
         already_set: "constant already set: %v%",
         invalid_name: "invalid name for constant: %v%"
      },
      div: {
         zero_divisor: "division by zero"
      },
      extend: {
         invalid_precision: "invalid_precision (non-negative integer): %v%",
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
         different_lengths: "arrays have different lengths: %v%",
         different_keys: "arrays have different keys: %v%"
      },
      split: {
         invalid_part_count: "only positive integer number of parts supported: %v%",
         zero_part_sum: "cannot split value when sum of parts is zero"
      },
      sqrt: {
         negative_value: "square root of negative value: %v%"
      }
   };
   var hasOwnProperty = {}.hasOwnProperty;
   var errTranslation = null;
   /**
    * Common error message object
    * @export
    * @constructor
    * @param {string} key error code
    * @param {*=} val incorrect value to message
    */
   var MbnErr = function (key, val) {
      if (arguments.length === 2) {
         val = (val instanceof Array) ? ("[" + String(val) + "]") : String(val);
         val = ((val.length > 20) ? (val.slice(0, 18) + "..") : val);
      } else {
         val = null;
      }
      this.errorKey = "mbn." + key;
      this.errorValue = val;

      var msg = null;
      if (typeof errTranslation === "function") {
         try {
            msg = errTranslation(this.errorKey, this.errorValue)
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
         for (var i = 0; i < keyArrLength; i++) {
            var word = keyArr[i];
            var nextSubMessages = subMessages[word];
            if (typeof nextSubMessages === "object" && nextSubMessages.hasOwnProperty("_")) {
               nextSubMessages = subMessages[nextSubMessages._];
            }
            subMessages = nextSubMessages;
         }
         msg += " error: " + subMessages;
      }
      this.message = msg.replace("%v%", val);
   };
   MbnErr.prototype.toString = function () {
      return this.message;
   };
   MbnErr.translate = function (translation) {
      errTranslation = translation;
   };

   //version of Mbn library
   var MbnV = "1.46";
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
      if (opt.hasOwnProperty("MbnP")) {
         MbnP = opt.MbnP;
         if (typeof MbnP !== "number" || MbnP < 0 || !isFinite(MbnP) || Math.round(MbnP) !== MbnP) {
            throw new MbnErr(fname + "invalid_precision", MbnP);
         }
      }
      if (opt.hasOwnProperty("MbnS")) {
         MbnS = opt.MbnS;
         if (MbnS !== "." && MbnS !== ",") {
            throw new MbnErr(fname + "invalid_separator", MbnS);
         }
      }
      if (opt.hasOwnProperty("MbnT")) {
         MbnT = opt.MbnT;
         if (MbnT !== true && MbnT !== false) {
            throw new MbnErr(fname + "invalid_truncation", MbnT);
         }
      }
      if (opt.hasOwnProperty("MbnE")) {
         MbnE = opt.MbnE;
         if (MbnE !== true && MbnE !== false && MbnE !== null) {
            throw new MbnErr(fname + "invalid_evaluating", MbnE);
         }
      }
      if (opt.hasOwnProperty("MbnF")) {
         MbnF = opt.MbnF;
         if (MbnF !== true && MbnF !== false) {
            throw new MbnErr(fname + "invalid_formatting", MbnF);
         }
      }
      if (opt.hasOwnProperty("MbnL")) {
         MbnL = opt.MbnL;
         if (typeof MbnL !== "number" || MbnL <= 0 || !isFinite(MbnP) || Math.round(MbnL) !== MbnL) {
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
       * Private function, if m is true, sets value of a to b and return a, otherwise returns b
       * @param {Mbn} a
       * @param {Mbn} b
       * @param {boolean=} m
       * @return {Mbn}
       */
      var mbnSetReturn = function (a, b, m) {
         if (m === true) {
            a._d = b._d;
            a._s = b._s;
            return a;
         }
         return b;
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

      var wsRx2 = /^\s*(=)?[\s=]*([+\-])?\s*((.*\S)?)/;
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
         var c;
         for (var i = 0; i <= l; i++) {
            c = (i < nl) ? (n.charCodeAt(i) - 48) : 0;
            if (c >= 0 && c <= 9) {
               if (i <= al + MbnP) {
                  a._d.push(c);
               }
            } else if ((i !== ln || nl === 1) && (c !== -16 || (i + 1) >= ln)) {
               if (v !== false && ((v instanceof Object) || v === true || MbnE === true || (MbnE !== false && np[1] === "="))) {
                  a.set(mbnCalc(ns, v));
                  return;
               }
               throw new MbnErr("invalid_format", ns);
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
            throw new MbnErr("limit_exceeded", nn);
         }
         if (nn < 0) {
            nn = -nn;
            a._s = -1;
         }
         var ni = Math.floor(nn);
         var nf = nn - ni;
         var nfi, c, i;
         do {
            c = ni % 10;
            ni -= c;
            ni /= 10;
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
       * @param {number} p Target precision
       * @param {string} s Separator
       * @param {boolean} t Trim zeros
       * @param {boolean} f Format thousands
       * @return {string}
       */
      var mbnToString = function (a, p, s, t, f) {
         var v = a, li = a._d.length - MbnP;
         if (p < MbnP) {
            var b = new Mbn(a);
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
         if (f === true) {
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

      /**
       * Constructor of Mbn object
       * @export
       * @constructor
       * @param {*=} n Value, default 0
       * @param {Object|boolean=} v Object with vars for evaluation
       * @throws {MbnErr} invalid argument, invalid format, calc error
       */
      var Mbn = function (n, v) {
         if (!(this instanceof Mbn)) {
            return new Mbn(n, v);
         }
         this._s = 1;
         this._d = [];
         switch (typeof n) {
            case "undefined":
               n = false;
            case "boolean":
               n = Number(n);
            case "number":
               mbnFromNumber(this, n);
               return;
            case "object":
               if (n instanceof Mbn) {
                  this.set(n);
                  return;
               } else if (n instanceof Array) {
                  throw new MbnErr("invalid_argument", n);
               }
               n = (n !== null) ? n.toString() : "0";
            case "string":
               mbnFromString(this, n, v);
               break;
            default:
               throw new MbnErr("invalid_argument", n);
         }
      };

      /**
       * Returns properties of Mbn class
       * @return {Object} properties
       */
      Mbn.prop = function () {
         return {MbnV: MbnV, MbnP: MbnP, MbnS: MbnS, MbnT: MbnT, MbnE: MbnE, MbnF: MbnF, MbnL: MbnL};
      };

      /**
       * Sets value from b
       * @param {*} b
       * @return {Mbn}
       * @throws {MbnErr} invalid argument format
       */
      Mbn.prototype.set = function (b) {
         if (!(b instanceof Mbn)) {
            mbnSetReturn(this, new Mbn(b), true);
         } else {
            this._d = b._d.slice();
            this._s = b._s;
         }
         return this;
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
            opt = {MbnF: opt === true || opt === undefined};
         }
         opt = prepareOpt(opt, MbnP, MbnS, MbnT, MbnE, MbnF, MbnL, "format.");
         return mbnToString(this, opt.MbnP, opt.MbnS, opt.MbnT, opt.MbnF);
      };

      /**
       * Returns number value
       * @return {number}
       */
      Mbn.prototype.toNumber = function () {
         return Number(mbnToString(this, MbnP, ".", false, false));
      };

      /**
       * Compare value with b, a.cmp(b)<=0 means a<=b
       * @param {*=} b
       * @param {*=} d Maximum difference treated as equality, default 0
       * @return {number} 1 if value > b, -1 if value < b, otherwise 0
       * @throws {MbnErr} negative maximal difference
       * @throws {MbnErr} invalid argument format
       */
      Mbn.prototype.cmp = function (b, d) {
         var dm;
         if (!(b instanceof Mbn)) {
            b = new Mbn(b);
         }
         if (d === undefined || (dm = new Mbn(d))._s === 0) {
            if (this._s !== b._s) {
               return (this._s > b._s) ? 1 : -1;
            }
            if (this._s === 0) {
               return 0;
            }
            var bl = b._d.length;
            var ld = this._d.length - bl;
            if (ld !== 0) {
               return (ld > 0) ? this._s : -this._s;
            }
            for (var i = 0; i < bl; i++) {
               if (this._d[i] !== b._d[i]) {
                  return (this._d[i] > b._d[i]) ? this._s : -this._s;
               }
            }
            return 0;
         }
         if (dm._s === -1) {
            throw new MbnErr("cmp.negative_diff", dm);
         }
         if (this.sub(b).abs().cmp(dm) <= 0) {
            return 0;
         }
         return this.cmp(b);
      };

      /**
       * Add b to value
       * @param {*} b
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       * @throws {MbnErr} invalid argument format
       */
      Mbn.prototype.add = function (b, m) {
         if (!(b instanceof Mbn)) {
            b = new Mbn(b);
         }
         var r = new Mbn(b);
         if (this._s !== 0) {
            if (b._s === 0) {
               r.set(this);
            } else if (b._s === this._s) {
               var ld = this._d.length - b._d.length;
               if (ld < 0) {
                  b = this;
                  ld = -ld;
               } else {
                  r.set(this);
               }
               for (var i = 0; i < r._d.length; i++) {
                  if (i >= ld) {
                     r._d[i] += b._d[i - ld];
                  }
               }
               mbnCarry(r);
            } else {
               r._s = -r._s;
               r.sub(this, true);
               r._s = -r._s;
            }
         }
         return mbnSetReturn(this, r, m);
      };

      /**
       * Subtract b from value
       * @param {*} b
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       * @throws {MbnErr} invalid argument format
       */
      Mbn.prototype.sub = function (b, m) {
         if (!(b instanceof Mbn)) {
            b = new Mbn(b);
         }
         var r = new Mbn(b);
         if (this._s === 0) {
            r._s = -r._s;
         } else if (b._s === 0) {
            r.set(this);
         } else if (b._s === this._s) {
            var ld = this._d.length - b._d.length;
            var cmp = this.cmp(b) * this._s;
            if (cmp === 0) {
               r = new Mbn(0);
            } else {
               if (cmp === -1) {
                  b = this;
                  ld = -ld;
               } else {
                  r.set(this);
               }
               for (var i = 0; i < r._d.length; i++) {
                  if (i >= ld) {
                     r._d[i] -= b._d[i - ld];
                  }
               }
               r._s = cmp * this._s;
               mbnCarry(r);
            }
         } else {
            r._s = -r._s;
            r.add(this, true);
         }
         return mbnSetReturn(this, r, m);
      };

      /**
       * Multiple value by b
       * @param {*} b
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       * @throws {MbnErr} invalid argument format
       */
      Mbn.prototype.mul = function (b, m) {
         if (!(b instanceof Mbn)) {
            b = new Mbn(b);
         }
         var r = new Mbn(b);
         r._d = [];
         for (var i = 0; i < this._d.length; i++) {
            for (var j = 0; j < b._d.length; j++) {
               r._d[i + j] = this._d[i] * b._d[j] + (r._d[i + j] || 0);
            }
         }
         r._s = this._s * b._s;
         mbnCarry(r);
         if (MbnP >= 1) {
            if (MbnP > 1) {
               r._d = r._d.slice(0, 1 - MbnP);
            }
            mbnRoundLast(r);
         }
         return mbnSetReturn(this, r, m);
      };

      /**
       * Divide value by b
       * @param {*} b
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       * @throws {MbnErr} division by zero
       * @throws {MbnErr} invalid argument format
       */
      Mbn.prototype.div = function (b, m) {
         if (!(b instanceof Mbn)) {
            b = new Mbn(b);
         }
         if (b._s === 0) {
            throw new MbnErr("div.zero_divisor");
         }
         if (this._s === 0) {
            return mbnSetReturn(this, new Mbn(this), m);
         }
         var x = this._d.slice();
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
         var r = new Mbn(b);
         r._s *= this._s;
         r._d = ra;
         mbnRoundLast(r);
         return mbnSetReturn(this, r, m);
      };

      /**
       * Modulo, remainder of division value by b, keep sign of value
       * @param {*} b
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       * @throws (MbnErr) division by zero
       * @throws {MbnErr} invalid argument format
       */
      Mbn.prototype.mod = function (b, m) {
         var ba = (b instanceof Mbn) ? b.abs() : (new Mbn(b)).abs();
         var r = this.sub(this.div(ba).intp().mul(ba));
         if ((r._s * this._s) === -1) {
            r = ba.sub(r.abs());
            r._s = this._s;
         }
         return mbnSetReturn(this, r, m);
      };

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
            var mbn1 = new Mbn(1);
            asum = new Mbn(ar);
            if (!asum.isInt()) {
               throw new MbnErr("split.invalid_part_count", ar);
            }
            n = asum.toNumber();
            for (i = 0; i < n; i++) {
               arr.push(mbn1);
            }
         } else {
            var mulp = (new Mbn(10)).pow(MbnP);
            asum = new Mbn(0);
            n = ar.length;
            var sgns = [false, false, false];
            for (i = 0; i < n; i++) {
               var ai = (new Mbn(ar[i])).mul(mulp);
               ai._i = i;
               sgns[ai._s + 1] = true;
               arr.push(ai);
               asum.add(ai, true);
            }
            if (sgns[0] && sgns[2]) {
               arr.sort(function (a, b) {
                  return asum._s * a.cmp(b);
               });
            }
         }
         if (n <= 0) {
            throw new MbnErr("split.invalid_part_count", n);
         }
         if (asum._s === 0) {
            throw new MbnErr("split.zero_part_sum");
         }
         var a = new Mbn(this);
         var brr = [];
         brr.length = n;
         var idx;
         for (i = 0; i < n; i++) {
            idx = arr[i].hasOwnProperty("_i") ? arr[i]._i : i;
            if (arr[i]._s === 0) {
               brr[idx] = arr[i];
            } else {
               var b = a.mul(arr[i]).div(asum);
               asum.sub(arr[i], true);
               a.sub(b, true);
               brr[idx] = b;
            }
         }
         return brr;
      };

      /**
       * Returns if the number is integer
       * @return {boolean}
       */
      Mbn.prototype.isInt = function () {
         for (var l = this._d.length - MbnP; l < this._d.length; l++) {
            if (this._d[l] !== 0) {
               return false;
            }
         }
         return true;
      };

      /**
       * Returns greatest integer value not greater than number
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       */
      Mbn.prototype.floor = function (m) {
         var r = (m === true) ? this : new Mbn(this);
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

      /**
       * Rounds number to closest integer value (half-up)
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       */
      Mbn.prototype.round = function (m) {
         var r = (m === true) ? this : new Mbn(this);
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

      /**
       * Returns absolute value
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       */
      Mbn.prototype.abs = function (m) {
         var r = (m === true) ? this : new Mbn(this);
         r._s *= r._s;
         return r;
      };

      /**
       * Returns additive inverse of value
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       */
      Mbn.prototype.inva = function (m) {
         var r = (m === true) ? this : new Mbn(this);
         r._s = -r._s;
         return r;
      };

      /**
       * Returns multiplicative inverse of value
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       * @throws {MbnErr} division by zero
       */
      Mbn.prototype.invm = function (m) {
         return mbnSetReturn(this, (new Mbn(1)).div(this), m);
      };

      /**
       * Returns lowest integer value not lower than value
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       */
      Mbn.prototype.ceil = function (m) {
         var r = (m === true) ? this : new Mbn(this);
         return r.inva(true).floor(true).inva(true);
      };

      /**
       * Returns integer part of number
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       */
      Mbn.prototype.intp = function (m) {
         var r = (m === true) ? this : new Mbn(this);
         return (r._s >= 0) ? r.floor(true) : r.ceil(true);
      };

      /**
       * Returns if value equals b
       * @param {*} b
       * @param {boolean=} d Maximum difference treated as equality, default 0
       * @return {boolean}
       * @throws {MbnErr} negative maximal difference
       * @throws {MbnErr} invalid argument format
       */
      Mbn.prototype.eq = function (b, d) {
         return this.cmp(b, d) === 0;
      };

      /**
       * Returns minimum from value and b
       * @param {*} b
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       * @throws {MbnErr} invalid argument format
       */
      Mbn.prototype.min = function (b, m) {
         return mbnSetReturn(this, new Mbn((this.cmp(b) <= 0) ? this : b), m);
      };

      /**
       * Returns maximum from value and b
       * @param {*} b
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       * @throws {MbnErr} invalid argument format
       */
      Mbn.prototype.max = function (b, m) {
         return mbnSetReturn(this, new Mbn((this.cmp(b) >= 0) ? this : b), m);
      };

      /**
       * Returns square root of value
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       * @throws {MbnErr} square root of negative number
       */
      Mbn.prototype.sqrt = function (m) {
         var t = this.mul(100);
         var rb = new Mbn(t);
         var r = new Mbn(t);
         var mbn2 = new Mbn(2);
         if (r._s === -1) {
            throw new MbnErr("sqrt.negative_value", this);
         }
         if (r._s === 1) {
            do {
               rb.set(r);
               r.add(t.div(r), true).div(mbn2, true);
            } while (!rb.eq(r));
         }
         mbnRoundLast(r);
         return mbnSetReturn(this, r, m);
      };

      /**
       * Returns sign from value, 1 - positive, -1 - negative, otherwise 0
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       */
      Mbn.prototype.sgn = function (m) {
         return mbnSetReturn(this, new Mbn(this._s), m);
      };

      /**
       * Returns value to the power of b, b must be integer
       * @param {*} b
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       * @throws {MbnErr} not integer exponent
       * @throws {MbnErr} invalid argument format
       */
      Mbn.prototype.pow = function (b, m) {
         var n = new Mbn(b);
         if (!n.isInt()) {
            throw new MbnErr("pow.unsupported_exponent", n);
         }
         var ns = n._s;
         n._s *= n._s;
         var ni = n.toNumber();
         var mbn1 = new Mbn(1);
         var rx = new Mbn(this);
         if (ns === -1 && rx.abs().cmp(mbn1) === -1) {
            rx.invm(true);
            ns = -ns;
         }
         var dd = 0;
         var cdd = 0;
         var r = new Mbn(mbn1);
         while (!rx.isInt()) {
            rx._d.push(0);
            mbnCarry(rx);
            dd++;
         }
         while (true) {
            if (ni % 2 === 1) {
               r.mul(rx, true);
               cdd += dd;
            }
            ni = Math.floor(ni / 2);
            if (ni === 0) {
               break;
            }
            rx.mul(rx, true);
            dd *= 2;
         }
         if (cdd >= 1) {
            if (cdd > 1) {
               r._d = r._d.slice(0, 1 - cdd);
            }
            mbnRoundLast(r);
         }
         if (ns === -1) {
            r.invm(true);
         }
         return mbnSetReturn(this, r, m);
      };

      /**
       * Returns factorial, value must be non-negative integer
       * @param {boolean=} m Modify original variable, default false
       * @return {Mbn}
       * @throws {MbnErr} value is not non-negative integer
       */
      Mbn.prototype.fact = function (m) {
         if (!this.isInt() || this._s === -1) {
            throw new MbnErr("fact.invalid_value", this);
         }
         var n = this.sub(1), r = new Mbn(this);
         while (n._s === 1) {
            r.mul(n, true);
            n.sub(1, true);
         }
         return mbnSetReturn(this, r, m);
      };

      var fnReduce = {
         set: 0, abs: 1, inva: 1, invm: 1, ceil: 1, floor: 1, sqrt: 1, round: 1, sgn: 1, intp: 1,
         min: 2, max: 2, add: 2, sub: 2, mul: 2, div: 2, mod: 2, pow: 2
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
         var inv = false;
         if (!fnReduce.hasOwnProperty(fn)) {
            throw new MbnErr("reduce.invalid_function", fn);
         }
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
         var mode = fnReduce[fn];
         var bmode = (arguments.length === 3) ? ((b instanceof Array) ? 2 : 1) : 0;
         if (mode !== 2 && bmode !== 0) {
            throw new MbnErr("reduce.invalid_argument_count");
         }
         if (mode === 2 && bmode === 0) {
            r = new Mbn((arrl > 0) ? arr[0] : 0);
            for (i = 1; i < arrl; i++) {
               r[fn](arr[i], true);
            }
         } else {
            r = [];
            if (bmode === 2 && arrl !== b.length) {
               throw new MbnErr("reduce.different_lengths", "(" + arrl + " " + b.length + ")");
            }
            var bv = (bmode === 1) ? (new Mbn(b)) : null;
            for (i = 0; i < arrl; i++) {
               var e = new Mbn(arr[i]);
               if (bmode !== 0) {
                  var bi = (bmode === 2) ? (new Mbn(b[i])) : bv;
                  e.set((inv === false) ? e[fn](bi) : bi[fn](e));
               }
               r.push((mode === 1) ? e[fn](true) : e);
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
         if (n === null) {
            return hasOwnProperty.call(MbnConst, v);
         }
         if (!cnRx.test(n)) {
            throw new MbnErr("def.invalid_name", n);
         }
         if (v === undefined) {
            if (!hasOwnProperty.call(MbnConst, n)) {
               throw new MbnErr("def.undefined", n);
            }
            if (!(MbnConst[n] instanceof Mbn)) {
               MbnConst[n] = (n === "eps") ? ((new Mbn(10)).pow(-MbnP)) : (new Mbn(MbnConst[n]));
            }
            return new Mbn(MbnConst[n]);
         }
         if (hasOwnProperty.call(MbnConst, n)) {
            throw new MbnErr("def.already_set", n + "=" + new Mbn(MbnConst[n]));
         }
         v = new Mbn(v);
         MbnConst[n] = v;
         return new Mbn(v);
      };

      var fnEval = {
         abs: true, inva: false, ceil: true, floor: true, fact: true,
         sqrt: true, round: true, sgn: true, int: "intp", div_100: "div_100"
      };
      var states = {
         endBop: ["bop", "pc", "fs"],
         uopVal: ["num", "name", "uop", "po"]
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
         fn: {next: ["po"]},
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
       * @param {boolean} omitConsts don't list already defined constants
       * @return {Array|boolean}
       */
      Mbn.check = function (exp, omitConsts) {
         try {
            var varName, vars = mbnCalc(exp, false), varNames = [];
            var hasOwnProperty = varNames.hasOwnProperty;
            for (varName in vars) {
               if (hasOwnProperty.call(vars, varName)) {
                  varNames[vars[varName]] = varName;
               }
            }
            if (omitConsts === true) {
               var pos = 0;
               for (var i = 0; i < varNames.length; i++) {
                  if (!Mbn.def(null, varNames[i])) {
                     varNames[pos++] = varNames[i];
                  }
               }
               varNames.length = pos;
            }
            return varNames;
         } catch (e) {
            return false;
         }
      };
      /**
       * Evaluate expression
       * @param {string} exp Expression
       * @param {Object|boolean=} vars Object with vars for evaluation
       * @return {Mbn|Object}
       * @throws {MbnErr} syntax error, operation error
       */
      var mbnCalc = function (exp, vars) {
         var onlyCheck = vars === false;
         if (!(vars instanceof Object)) {
            vars = {};
         }
         var expr = String(exp).replace(wsRx3, "");
         var varsUsed = {};
         var varsUsedSize = 0;
         var state = states.uopVal;
         var rpns = [];
         var rpno = [];
         var stateLength, t, tok, mtch, i, rolp;

         while (expr !== "") {
            mtch = null;
            stateLength = state.length;
            for (i = 0; i < stateLength && mtch === null; i++) {
               t = state[i];
               mtch = expr.match(rxs[t].rx);
            }
            if (mtch === null) {
               if (state === states.endBop) {
                  tok = "*";
                  t = "bop";
               } else {
                  throw new MbnErr("calc.unexpected", expr);
               }
            } else {
               tok = mtch[1];
               expr = expr.slice(mtch[0].length);
            }
            switch (t) {
               case "num":
                  rpns.push(new Mbn(tok, false));
                  break;
               case "name":
                  t = "vr";
                  if (hasOwnProperty.call(fnEval, tok) && fnEval[tok] !== false) {
                     t = "fn";
                     rpno.push(ops.fn.concat([tok]));
                  } else if (onlyCheck) {
                     if (!hasOwnProperty.call(varsUsed, tok)) {
                        varsUsed[tok] = varsUsedSize++;
                     }
                  } else if (hasOwnProperty.call(vars, tok)) {
                     if (!hasOwnProperty.call(varsUsed, tok)) {
                        varsUsed[tok] = new Mbn(vars[tok]);
                     }
                     rpns.push(new Mbn(varsUsed[tok]));
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

         if (onlyCheck) {
            return varsUsed;
         }

         var rpn = [];
         var rpnsl = rpns.length;
         var tn;

         for (i = 0; i < rpnsl; i++) {
            tn = rpns[i];
            if (tn instanceof Mbn) {
               rpn.push(tn);
            } else if (fnEval.hasOwnProperty(tn)) {
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
