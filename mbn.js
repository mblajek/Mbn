/**
 * MultiByteNumber
 * Mikołaj Błajek
 * mblajek_mbn(at)mailplus.pl
 */

"use strict";
var Mbn = (function () {
   /**
    * Common error message object
    * @export
    * @constructor
    * @param {string} fn
    * @param {string} msg
    * @param {*=} val
    */
   var MbnErr = function (fn, msg, val) {
      this.toString = function () {
         var ret = "Mbn" + fn + " error: " + msg;
         if (val !== undefined) {
            val = String(val);
            ret += ": " + ((val.length > 10) ? (val.slice(0, 8) + '..') : val);
         }
         return ret;
      };
      this.message = String(this);
   };

   //version of MultiByteNumber library
   var MbnV = "1.25";
   //default precision
   var MbnDP = 2;
   //default separator
   var MbnDS = ".";
   //default truncate
   var MbnDT = false;

   /**
    * Function returns function, which is constructor of Mbn objects
    * with precision p. and separator s
    * @export
    * @param {*=} opt
    */
   var MbnCr = function (opt) {
      if (typeof opt !== "object") {
         opt = (opt !== undefined) ? {MbnP: Number(opt)} : {};
      }
      //actual precision for Mbn class
      var MbnP = (opt.MbnP === undefined) ? MbnDP : Number(opt.MbnP);
      if (!isFinite(MbnP) || Math.round(MbnP) !== MbnP || MbnP < 0) {
         throw new MbnErr(".extend", "invalid precision", MbnP);
      }

      //actual separator for Mbn class
      var MbnS = (opt.MbnS === undefined) ? MbnDS : opt.MbnS;
      if (MbnS !== "." && MbnS !== ",") {
         throw new MbnErr(".extend", "invalid separator", MbnS);
      }

      //actual truncate for Mbn class
      var MbnT = (opt.MbnT === undefined) ? MbnDT : opt.MbnT;
      if (MbnT !== true && MbnT !== false) {
         throw new MbnErr(".extend", "invalid truncate", MbnT);
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
            i = 0;
            while (i <= adlm1 && ad[i] === 0) {
               i++;
            }
            a._s *= (i <= adlm1) ? 1 : 0;
         }
      };

      /**
       * Private function, if m is true, sets value of a to b and return a, otherwise returns b
       * @param {Mbn} a
       * @param {Mbn} b
       * @param {boolean=} m Modify original variable
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

      var wsRx1 = /^\s+|\s+$/g;
      var wsRx2 = /([+=-]?)\s*(.*)/;
      /**
       * Private function, sets value of a to string value n
       * @param {Mbn} a
       * @param {string} ns
       * @param {*=} v
       */
      var mbnFromString = function (a, ns, v) {
         var np = ns.replace(wsRx1, "").match(wsRx2);
         var n0 = np[1];
         var n = np[2];
         if (n0 === "-") {
            a._s = -1;
         } else if (n0 === "=" && typeof Mbn.calc === "function") {
            a.set(Mbn.calc(n, v));
            return;
         }
         var ln = ((n.indexOf(".") + 1) || (n.indexOf(",") + 1)) - 1;
         if (ln === -1) {
            ln = n.length;
         } else {
            n = n.slice(0, ln) + n.slice(ln + 1);
         }
         if (ln === 0) {
            ln = 1;
            n = "0" + ((n !== "") ? n : np[2]);
         }
         var c;
         var nl = n.length;
         for (var i = 0; i <= ln + MbnP; i++) {
            c = (i < nl) ? (n.charCodeAt(i) - 48) : 0;
            if (c >= 0 && c <= 9) {
               a._d.push(c);
            } else if (c === -16 && (i + 1) < ln) {
               continue;
            } else {
               throw new MbnErr("", "invalid format", ns);
            }
         }
         mbnRoundLast(a);
      };

      /**
       * Private function, returns string from number, with MbnP + 1 digits
       * @param {Mbn} a
       * @param {number} nn
       */
      var mbnFromNumber = function (a, nn) {
         if (!isFinite(nn)) {
            throw new MbnErr("", "invalid value", nn);
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
       * Constructor of Mbn object
       * @export
       * @constructor
       * @param {*=} n
       * @param {*=} v
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
               }
               n = (n !== null) ? n.toString() : "0";
            case "string":
               mbnFromString(this, n, v);
               break;
            default:
               throw new MbnErr("", "invalid argument", n);
         }
      };

      /**
       * Returns properties of Mbn class
       */
      Mbn.prop = function () {
         return {MbnV: MbnV, MbnP: MbnP, MbnS: MbnS, MbnT: MbnT, MbnE: (typeof Mbn.calc === "function")};
      };

      /**
       * sets value to b
       * @param {*} b
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
       * Returns string value of Mbn number
       */
      Mbn.prototype.toString = function () {
         var l = this._d.length - MbnP;
         var l0;
         if (MbnT) {
            l0 = l - 1;
            for (var i = l; i < this._d.length; i++) {
               if (this._d[i] !== 0) {
                  l0 = i;
               }
            }
         } else {
            l0 = l + MbnP;
         }
         var r = ((this._s < 0) ? "-" : "") + this._d.slice(0, l).join("");
         if (MbnP !== 0 && l0 >= l) {
            r += MbnS + this._d.slice(l, l0 + 1).join("");
         }
         return r;
      };

      /**
       * Returns string value with thousand grouping
       */
      Mbn.prototype.format = function () {
         var sa = this.toString().replace("-", "").split(MbnS);
         sa[0] = ("  " + sa[0]).substring((sa[0].length + 2) % 3).replace(/(...)/g, " $1").replace(/^ +/, "");
         return ((this._s < 0) ? "-" : "") + sa.join(MbnS);
      };

      /**
       * Returns number value of Mbn number
       */
      Mbn.prototype.toNumber = function () {
         return Number(String(this).replace(",", "."));
      };

      /**
       * Compare Mbn number to b, if is bigger than b, returns 1, if is lower, returns -1, if equals returns 0
       * @param {*=} b
       * @param {*=} d
       */
      Mbn.prototype.cmp = function (b, d) {
         var dm;
         if (d === undefined || (dm = new Mbn(d))._s === 0) {
            if (!(b instanceof Mbn)) {
               b = new Mbn(b);
            }
            if (this._s !== b._s) {
               return (this._s > b._s) ? 1 : -1;
            }
            if (this._s === 0) {
               return 0;
            }
            var ld = this._d.length - b._d.length;
            if (ld !== 0) {
               return (ld > 0) ? this._s : -this._s;
            }
            for (var i = 0; i < this._d.length; i++) {
               if (this._d[i] !== b._d[i]) {
                  return (this._d[i] > b._d[i]) ? this._s : -this._s;
               }
            }
            return 0;
         } else {
            if (dm._s === -1) {
               throw new MbnErr(".cmp", "negative maximal difference", dm);
            }
            if (this.sub(b).abs().cmp(dm) <= 0) {
               return 0;
            } else {
               return this.cmp(b);
            }
         }
      };

      /**
       * Add b to Mbn number
       * @param {*} b
       * @param {boolean=} m Modify original variable
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
       * Substract b from value
       * @param {*} b
       * @param {boolean=} m Modify original variable
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
               r = new Mbn('0');
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
       * @param {boolean=} m Modify original variable
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
       * Devide value by b
       * @param {*} b
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.div = function (b, m) {
         if (!(b instanceof Mbn)) {
            b = new Mbn(b);
         }
         if (b._s === 0) {
            throw new MbnErr(".div", "division by zero");
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
         var xl;
         var yl;
         do {
            while ((x[(xl = x.length) - 1] + y[(yl = y.length) - 1]) === 0) {
               x.pop();
               y.pop();
            }
            var xge = (xl >= yl);
            if (xl === yl) {
               for (var i = 0; i < xl; i++) {
                  if (x[i] !== y[i]) {
                     xge = x[i] > y[i];
                     break;
                  }
               }
            }
            if (xge) {
               ra[p]++;
               var ld = xl - yl;
               for (var i = yl - 1; i >= 0; i--) {
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
       * Modulo from divide value by b
       * @param {*} b
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.mod = function (b, m) {
         var ba = (b instanceof Mbn) ? b.abs() : (new Mbn(b)).abs();
         var r = this.sub(this.div(ba).intp().mul(ba));
         if ((r._s + this._s) === 0) {
            r = ba.sub(r.abs());
            r._s = this._s;
         }
         return mbnSetReturn(this, r, m);
      };

      /**
       * Split value to array of values, with same ratios as in given array
       * @param {*=} ar
       */
      Mbn.prototype.split = function (ar) {
         var arr = [];
         var asum;
         var n;
         if (ar === undefined) {
            ar = 2;
         }
         if (!(ar instanceof Array)) {
            var mbn1 = new Mbn(1);
            asum = new Mbn(ar);
            if (!asum.isInt() || asum._s < 0) {
               throw new MbnErr(".split", "only natural number of parts supported");
            }
            n = asum.toNumber();
            for (var i = 0; i < n; i++) {
               arr.push(mbn1);
            }
         } else {
            var mulp = new Mbn(1);
            for (var i = 0; i < MbnP; i++) {
               mulp._d.push(0);
            }
            asum = new Mbn(0);
            n = ar.length;
            for (var i = 0; i < n; i++) {
               var ai = (new Mbn(ar[i])).mul(mulp);
               if (ai._s === -1) {
                  throw new MbnErr('.split', 'only non-negative ratio values supported');
               }
               arr.push(ai);
               asum.add(ai, true);
            }
         }
         if (arr.length === 0) {
            return [];
         }
         var a = new Mbn(this);
         var brr = [];
         for (var i = 0; i < n; i++) {
            if (arr[i]._s === 0) {
               brr.push(arr[i]);
            } else {
               var b = a.mul(arr[i]).div(asum);
               asum.sub(arr[i], true);
               a.sub(b, true);
               brr.push(b);
            }
         }
         return brr;
      };

      /**
       * Returns true if the number is integer
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
       * Returns bigest integer value not greater than number
       * @param {boolean=} m Modify original variable
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
       * Rounds number to closest integer value
       * @param {boolean=} m Modify original variable
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
       * Returns absolute value from number
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.abs = function (m) {
         var r = (m === true) ? this : new Mbn(this);
         r._s *= r._s;
         return r;
      };

      /**
       * returns additional inverse of number
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.inva = function (m) {
         var r = (m === true) ? this : new Mbn(this);
         r._s = -r._s;
         return r;
      };

      /**
       * returns multiplication inverse of number
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.invm = function (m) {
         return mbnSetReturn(this, (new Mbn(1)).div(this), m);
      };

      /**
       * Returns lowest integer value not lower than number
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.ceil = function (m) {
         var r = (m === true) ? this : new Mbn(this);
         return r.inva(true).floor(true).inva(true);
      };

      /**
       * Returns integer part of number
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.intp = function (m) {
         var r = (m === true) ? this : new Mbn(this);
         return (r._s >= 0) ? r.floor(true) : r.ceil(true);
      };

      /**
       * returns if number equals to b, or if d is set, difference is lower or equals d
       * @param {*} b
       * @param {boolean=} d
       */
      Mbn.prototype.eq = function (b, d) {
         return this.cmp(b, d) === 0;
      };

      /**
       * returns minimum from value and b
       * @param {*} b
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.min = function (b, m) {
         return mbnSetReturn(this, new Mbn(((this.cmp(b)) <= 0) ? this : b), m);
      };

      /**
       * returns maximum from value and b
       * @param {*} b
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.max = function (b, m) {
         return mbnSetReturn(this, new Mbn(((this.cmp(b)) >= 0) ? this : b), m);
      };

      /**
       * calculates square root of number
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.sqrt = function (m) {
         var t = new Mbn(this);
         t._d.push(0);
         t._d.push(0);
         var rb = new Mbn(t);
         var r = new Mbn(t);
         var mbn2 = new Mbn(2);
         if (r._s === -1) {
            throw new MbnErr(".sqrt", "square root of negative number", this);
         } else if (r._s === 1) {
            do {
               rb.set(r);
               r.add(t.div(r), true).div(mbn2, true);
            } while (!rb.eq(r));
         }
         mbnRoundLast(r);
         return mbnSetReturn(this, r, m);
      };

      /**
       * returns sign from value
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.sgn = function (m) {
         return mbnSetReturn(this, new Mbn(this._s), m);
      };

      //SLIM_EXCLUDE_START

      /**
       * Calculates n-th power of number, n must be integer
       * @param {*} nd
       * @param {boolean=} m Modify original variable
       */
      Mbn.prototype.pow = function (nd, m) {
         var n = new Mbn(nd);
         if (!n.isInt()) {
            throw new MbnErr(".pow", "only integer exponents supported", n);
         }
         var ns = n._s;
         n._s *= n._s;
         var mbn1 = new Mbn(1);
         var mbn2 = new Mbn(2);
         if (ns === -1 && this.abs().cmp(mbn1) === -1) {
            this.invm(true);
            ns = -ns;
         }
         var rx = new Mbn(this);
         var dd = 0;
         var cdd = 0;
         var r = new Mbn(mbn1);
         while (!rx.isInt()) {
            rx._d.push(0);
            mbnCarry(rx);
            dd++;
         }
         while (n._s === 1) {
            if (n._d[n._d.length - MbnP - 1] % 2) {
               r.mul(rx, true);
               n.sub(mbn1, true);
               cdd += dd;
            }
            n.div(mbn2, true).intp(true);
            if (n._s !== 1) {
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

      var fnReduce = {set: 0, abs: 1, inva: 1, invm: 1, ceil: 1, floor: 1,
         sqrt: 1, round: 1, sgn: 1, intp: 1, add: 2, mul: 2, min: 2, max: 2};
      /**
       * run function on each element, returns single value for 2 argument function,
       * and array, for 1 argument
       * @param {string} fn
       * @param {Array} arr
       */
      Mbn.reduce = function (fn, arr) {
         if (!fnReduce.hasOwnProperty(fn)) {
            throw new MbnErr(".reduce", "invalid function name", fn);
         }
         if (!(arr instanceof Array)) {
            throw new MbnErr(".reduce", "argument is not array", arr);
         }
         var r;
         var arrl = arr.length;
         var mode = fnReduce[fn];
         if (mode === 2) {
            r = new Mbn((arrl > 0) ? arr[0] : 0);
            for (var i = 1; i < arrl; i++) {
               r[fn](arr[i], true);
            }
         } else {
            r = [];
            for (var i = 0; i < arrl; i++) {
               var e = new Mbn(arr[i]);
               r.push((mode === 1) ? e[fn](true) : e);
            }
         }
         return r;
      };

      var MbnConst = {
         PI: "3.1415926535897932384626433832795028841972",
         E: "2.7182818284590452353602874713526624977573",
         MbnP: MbnP
      };

      var cnRx = /^[A-Z]\w*$/;
      /**
       * Sets and reads constant
       * @param {string|null} n
       * @param {*=} v
       */
      Mbn.def = function (n, v) {
         if (n === null) {
            return MbnConst.hasOwnProperty(v);
         }
         if (!cnRx.test(n)) {
            throw new MbnErr(".def", "incorrect name", n);
         }
         if (v === undefined) {
            if (MbnConst.hasOwnProperty(n)) {
               v = MbnConst[n];
               if (!(v instanceof Mbn)) {
                  v = new Mbn(v);
                  MbnConst[n] = v;
               }
               return new Mbn(v);
            } else {
               throw new MbnErr(".def", "undefined constant", n);
            }
         } else {
            if (MbnConst.hasOwnProperty(n)) {
               throw new MbnErr(".def", "constant allready set", n);
            } else {
               v = new Mbn(v);
               MbnConst[n] = v;
               return new Mbn(v);
            }
         }
      };

      var fnEval = {abs: true, inva: false, ceil: true, floor: true, sqrt: true, round: true, sgn: true, int: "intp"};
      var endBop = ["bop", "pc"];
      var uopVal = ["num", "name", "uop", "po"];
      var bops = {
         "|": [1, true, "max"],
         "&": [2, true, "min"],
         "+": [3, true, "add"],
         "-": [3, true, "sub"],
         "*": [4, true, "mul"],
         "#": [4, true, "mod"],
         "/": [4, true, "div"],
         "^": [5, false, "pow"]
      };
      var funPrx = 4;
      var rxs = {
         num: {rx: /^([0-9\., ]+)\s*/, next: ["bop", "pc", "pr"], end: true},
         name: {rx: /^([A-Za-z_]\w*)\s*/},
         fn: {next: ["po"], end: false},
         vr: {next: endBop, end: true},
         bop: {rx: /^([-+\*\/#^&|])\s*/, next: uopVal, end: false},
         uop: {rx: /^([-+])\s*/, next: uopVal, end: false},
         po: {rx: /^(\()\s*/, next: uopVal, end: false},
         pc: {rx: /^(\))\s*/, next: endBop, end: true},
         pr: {rx: /^(%)\s*/, next: endBop, end: true}
      };

      var wsRx3 = /^\s+/;
      /**
       * calc expression
       * @param {string} exp
       * @param {*=} vars
       */
      Mbn.calc = function (exp, vars) {
         var expr = exp.replace(wsRx3, "");
         var vnames = {};
         if (vars !== undefined) {
            for (var i in vars) {
               if (vars.hasOwnProperty(i)) {
                  vnames[i] = new Mbn(vars[i]);
               }
            }
         }
         var larr = uopVal;
         var larl = larr.length;
         var lare = false;
         var rpns = [];
         var rpno = [];
         var neg = false;
         var t = null;
         var tok;
         var mtch;
         var invaUop = [funPrx, true, "inva"];

         while (expr.length > 0) {
            mtch = null;
            for (var i = 0; i < larl && mtch === null; i++) {
               t = larr[i];
               mtch = expr.match(rxs[t].rx);
            }
            if (mtch === null) {
               if (larr[0] === "bop") {
                  tok = "*";
                  t = "bop";
               } else {
                  throw new MbnErr(".calc", "unexpected", expr);
               }
            } else {
               tok = mtch[1];
               expr = expr.slice(mtch[0].length);
            }
            if (t !== "uop" && neg) {
               rpno.push(invaUop);
               neg = false;
            }
            switch (t) {
               case "num":
                  rpns.push(new Mbn(tok));
                  break;
               case "name":
                  if (fnEval.hasOwnProperty(tok) && fnEval[tok] !== false) {
                     t = "fn";
                     rpno.push([funPrx, true, tok]);
                  } else if (vnames.hasOwnProperty(tok)) {
                     t = "vr";
                     rpns.push(new Mbn(vnames[tok]));
                  } else if (Mbn.def(null, tok)) {
                     t = "vr";
                     rpns.push(Mbn.def(tok));
                  } else {
                     throw new MbnErr(".calc", "undefined", tok);
                  }
                  break;
               case "bop":
                  var bop = bops[tok];
                  var rolm;
                  while ((rolm = rpno.length - 1) !== -1) {
                     var rolp = rpno[rolm];
                     if (rolp !== "(" && (rolp[0] > bop[0] - (bop[1] ? 1 : 0))) {
                        rpns.push(rpno.pop()[2]);
                     } else {
                        break;
                     }
                  }
                  rpno.push(bop);
                  break;
               case "uop":
                  if (tok === "-") {
                     neg = !neg;
                  }
                  break;
               case "po":
                  rpno.push(tok);
                  break;
               case "pc":
                  var rolm;
                  while ((rolm = rpno.length - 1) !== -1) {
                     var rolp = rpno[rolm];
                     if (rolp !== "(") {
                        rpns.push(rpno.pop()[2]);
                     } else {
                        rpno.pop();
                        break;
                     }
                  }
                  if (rolm === -1) {
                     throw new MbnErr(".calc", "unexpected", ")");
                  } else {
                     rolm = rpno.length - 1;
                     if (rolm !== -1 && rpno[rolm][2] === funPrx) {
                        rpns.push(rpno.pop()[2]);
                     }
                  }
                  break;
               case "pr":
                  rpns[rpns.length - 1].div(100, true);
                  break;
               default:
            }

            larr = rxs[t].next;
            larl = larr.length;
            lare = rxs[t].end;
         }
         while (rpno.length !== 0) {
            var v = rpno.pop();
            if (v !== "(") {
               rpns.push(v[2]);
            } else {
               throw new MbnErr(".calc", "unexpected", "(");
            }
         }
         if (!lare) {
            throw new MbnErr(".calc", "unexpected", "END");
         }

         var rpn = [];

         var rpnsl = rpns.length;

         for (var i = 0; i < rpnsl; i++) {
            var tn = rpns[i];
            if (tn instanceof Mbn) {
               rpn.push(tn);
            } else if (fnEval.hasOwnProperty(tn)) {
               if (typeof fnEval[tn] === "string") {
                  tn = fnEval[tn];
               }
               rpn[rpn.length - 1][tn](true);
            } else {
               var pp = rpn.pop();
               rpn[rpn.length - 1][tn](pp, true);
            }
         }
         return rpn[0];
      };
      //SLIM_EXCLUDE_END
      return Mbn;
   };
   var Mbn = MbnCr();
   Mbn.extend = MbnCr;
   return Mbn;
})();
