<?php

class MbnErr extends Exception {

   /**
    * Common error message object
    * @export
    * @constructor
    * @param {string} $fn Function name
    * @param {string} $msg Message
    * @param {*=} $val Incorrect value to message
    */
   public function __construct($fn, $msg, $val = null) {
      $ret = 'Mbn' . $fn . ' error: ' . $msg;
      if ($val !== null) {
         if (is_array($val)) {
            $val = '[' . implode(",", $val) . ']';
         }
         $ret .= ': ' . ((strlen($val) > 20) ? (substr($val, 0, 18) . '..') : $val);
      }
      parent::__construct($ret);
   }

}

/**
 * Class Mbn
 */
class Mbn {

   //version of Mbn library
   protected static $MbnV = '1.38';
   //default precision
   protected static $MbnP = 2;
   //default separator
   protected static $MbnS = '.';
   //default truncate
   protected static $MbnT = false;
   //default truncate
   protected static $MbnE = null;
   //default truncate
   protected static $MbnF = false;
   private $d = [];
   private $s = 1;

   /**
    * Private function, carries digits bigger than 9, and removes leading zeros
    */
   private function mbnCarry() {
      $ad = &$this->d;
      $adlm1 = count($ad) - 1;
      $i = $adlm1;
      while ($i >= 0) {
         $adi = $ad[$i];
         while ($adi < 0) {
            $adi += 10;
            $ad[$i - 1] --;
         }
         $adid = $adi % 10;
         $adic = ($adi - $adid) / 10;
         $ad[$i] = $adid;
         if ($adic !== 0) {
            if ($i !== 0) {
               $ad[--$i] += $adic;
            } else {
               array_unshift($ad, $adic);
               $adlm1++;
            }
         } else {
            $i--;
         }
      }
      while ($adlm1 > static::$MbnP && $ad[0] === 0) {
         array_shift($ad);
         $adlm1 --;
      }
      while ($adlm1 < static::$MbnP) {
         array_unshift($ad, 0);
         $adlm1 ++;
      }
      if ($adlm1 === static::$MbnP) {
         $i = 0;
         while ($i <= $adlm1 && $ad[$i] === 0) {
            $i++;
         }
         $this->s *= ($i <= $adlm1) ? 1 : 0;
      }
   }

   /**
    * Private function, if m is true, sets value to b and return value, otherwise returns b
    * @param {Mbn} $b
    * @param {boolean} $m
    */
   private function mbnSetReturn($b, $m) {
      if ($m === true) {
         $this->d = &$b->d;
         $this->s = $b->s;
         return $this;
      }
      return $b;
   }

   /**
    * Private function, removes last digit and rounds next-to-last depending on it
    */
   private function mbnRoundLast() {
      $ad = &$this->d;
      $adl = count($ad);
      if ($adl < 2) {
         array_unshift($ad, 0);
         $adl++;
      }
      if (array_pop($ad) >= 5) {
         $ad[$adl - 2] ++;
      }
      $this->mbnCarry();
   }

   /**
    * Private function, sets value from string
    * @param {string} $ns
    * @param {array|boolean=} $v
    */
   private function fromString($ns, $v = null) {
      $np = [];
      preg_match('/^\s*(=)?[\s=]*(\+|-)?\s*((.*\S)?)/', $ns, $np);
      $n = $np[3];
      if ($np[2] === '-') {
         $this->s = -1;
      }
      $ln = strpos($n, '.');
      if ($ln === false) {
         $ln = strpos($n, ',');
      }
      $nl = strlen($n);
      $al = $nl;
      if ($ln === false) {
         $ln = $nl;
      } else {
         $al = $ln + 1;
      }
      $l = max($al + static::$MbnP, $nl);
      for ($i = 0; $i <= $l; $i++) {
         $c = ($i < $nl) ? (ord($n[$i]) - 48) : 0;
         if ($c >= 0 && $c <= 9) {
            if ($i <= $al + static::$MbnP) {
               $this->d[] = $c;
            }
         } elseif (($i !== $ln || $nl === 1) && ($c !== -16 || ($i + 1) >= $ln)) {
            if ($v !== false && (is_array($v) || $v === true || static::$MbnE === true || (static::$MbnE !== false && $np[1] === "="))) {
               $this->set(static::mbnCalc($ns, $v));
               return;
            }
            throw new MbnErr('', 'invalid format', $ns);
         }
      }
      $this->mbnRoundLast();
   }

   /**
    * Private function, sets value from number
    * @param {number} $nn
    */
   private function mbnFromNumber($nn) {
      if (!is_finite($nn)) {
         throw new MbnErr('', 'invalid value', $nn);
      }
      if ($nn < 0) {
         $nn = -$nn;
         $this->s = -1;
      }
      $ni = intval($nn);
      $nf = $nn - $ni;
      do {
         $c = $ni % 10;
         $ni -= $c;
         $ni /= 10;
         array_unshift($this->d, $c);
      } while ($ni > 0);
      for ($n = 0; $n <= static::$MbnP; $n++) {
         $nf *= 10;
         $nfi = intval($nf);
         $c = ($nfi === 10) ? 9 : $nfi;
         $this->d[] = $c;
         $nf -= $c;
      }
      $this->mbnRoundLast();
   }

   /**
    * Private function, returns string value
    * @param {string} $s Separator
    * @param {boolean} $f Format thousands
    */
   private function mbnToString($s, $f) {
      $l = count($this->d) - static::$MbnP;
      if (static::$MbnT) {
         $l0 = $l - 1;
         $cd = count($this->d);
         for ($i = $l; $i < $cd; $i++) {
            if ($this->d[$i] !== 0) {
               $l0 = $i;
            }
         }
      } else {
         $l0 = $l + static::$MbnP;
      }
      $d = array_slice($this->d, 0, $l);
      if ($f === true) {
         for ($i = 3; $i < count($d); $i += 4) {
            array_splice($d, -$i, 0, ' ');
         }
      }
      $r = (($this->s < 0) ? '-' : '') . implode($d, '');
      if (static::$MbnP !== 0 && $l0 >= $l) {
         $r .= $s . implode(array_slice($this->d, $l, $l0 + 1 - $l), '');
      }
      return $r;
   }

   /**
    * Constructor of Mbn object
    * @export
    * @constructor
    * @param {*=} $n Value
    * @param {array|boolean=} v Array with vars for evaluation
    */
   public function __construct($n = 0, $v = null) {
      if (is_float($n) || is_int($n)) {
         $this->mbnFromNumber($n);
      } elseif (is_object($n) || is_string($n)) {
         if ($n instanceof static) {
            $this->set($n);
            return;
         }
         $this->fromString($n, $v);
      } elseif (is_bool($n) || is_null($n)) {
         $this->mbnFromNumber(intval($n));
      } else {
         throw new MbnErr('', 'invalid argument', $n);
      }
   }

   /**
    * Returns properties of Mbn class
    */
   public static function prop() {
      return ['MbnV' => static::$MbnV, 'MbnP' => static::$MbnP, 'MbnS' => static::$MbnS,
          'MbnT' => static::$MbnT, 'MbnE' => static::$MbnE, 'MbnF' => static::$MbnF];
   }

   /**
    * Sets value from b
    * @param {*} $b
    */
   public function set($b) {
      if (!($b instanceof static)) {
         $this->mbnSetReturn(new static($b), true);
      } else {
         $this->d = $b->d;
         $this->s = $b->s;
      }
      return $this;
   }

   /**
    * Returns string value
    */
   protected function toString() {
      return $this->mbnToString(static::$MbnS, static::$MbnF);
   }

   /**
    * Returns string value with or without thousand grouping
    * @param {boolean=} $f Thousand grouping, dafault true
    */
   public function format($f = true) {
      return $this->mbnToString(static::$MbnS, $f);
   }

   public function __toString() {
      return $this->toString();
   }

   /**
    * Returns int value for MbnP = 0, otherwise float value
    */
   public function toNumber() {
      $v = $this->mbnToString('.', false);
      return (static::$MbnP === 0) ? intval($v) : floatval($v);
   }

   /**
    * Compare value with b, returns 1 if value > b, returns -1 if value < b, otherwise 0
    * @param {*=} $b
    * @param {*=} $d Maximum difference treated as equality, default 0
    */
   public function cmp($b, $d = 0) {
      if ($d !== 0) {
         $dm = new static($d);
      }
      if ($d === 0 || $dm->s === 0) {
         if (!($b instanceof static)) {
            $b = new static($b);
         }
         if ($this->s !== $b->s) {
            return ($this->s > $b->s) ? 1 : -1;
         }
         if ($this->s === 0) {
            return 0;
         }
         $dl = count($this->d);
         $ld = $dl - count($b->d);
         if ($ld !== 0) {
            return ($ld > 0) ? $this->s : -$this->s;
         }
         for ($i = 0; $i < $dl; $i++) {
            if ($this->d[$i] !== $b->d[$i]) {
               return ($this->d[$i] > $b->d[$i]) ? $this->s : -$this->s;
            }
         }
         return 0;
      } else {
         if ($dm->s === -1) {
            throw new MbnErr('.cmp', 'negative maximal difference', $dm);
         }
         if ($this->sub($b)->abs()->cmp($dm) <= 0) {
            return 0;
         } else {
            return $this->cmp($b);
         }
      }
   }

   /**
    * Add b to value
    * @param {*} $b
    * @param {boolean=} $m Modify original variable, default false
    */
   public function add($b, $m = false) {
      if (!($b instanceof static)) {
         $b = new static($b);
      }
      $r = new static($b);
      if ($this->s !== 0) {
         if ($b->s === 0) {
            $r->set($this);
         } else if ($b->s === $this->s) {
            $ld = count($this->d) - count($b->d);
            if ($ld < 0) {
               $b = $this;
               $ld = -$ld;
            } else {
               $r->set($this);
            }
            $rl = count($r->d);
            for ($i = 0; $i < $rl; $i++) {
               if ($i >= $ld) {
                  $r->d[$i] += $b->d[$i - $ld];
               }
            }
            $r->mbnCarry();
         } else {
            $r->s = -$r->s;
            $r->sub($this, true);
            $r->s = -$r->s;
         }
      }
      return $this->mbnSetReturn($r, $m);
   }

   /**
    * Substract b from value
    * @param {*} $b
    * @param {boolean=} $m Modify original variable, default false
    */
   public function sub($b, $m = false) {
      if (!($b instanceof static)) {
         $b = new static($b);
      }
      $r = new static($b);
      if ($this->s === 0) {
         $r->s = -$r->s;
      } else if ($b->s === 0) {
         $r->set($this);
      } else if ($b->s === $this->s) {
         $ld = count($this->d) - count($b->d);
         $cmp = $this->cmp($b) * $this->s;
         if ($cmp === 0) {
            $r = new static(0);
         } else {
            if ($cmp === -1) {
               $b = $this;
               $ld = -$ld;
            } else {
               $r->set($this);
            }
            $rl = count($r->d);
            for ($i = 0; $i < $rl; $i++) {
               if ($i >= $ld) {
                  $r->d[$i] -= $b->d[$i - $ld];
               }
            }
            $r->s = $cmp * $this->s;
            $r->mbnCarry();
         }
      } else {
         $r->s = -$r->s;
         $r->add($this, true);
      }
      return $this->mbnSetReturn($r, $m);
   }

   /**
    * Multiple value by b
    * @param {*} $b
    * @param {boolean=} $m Modify original variable, default false
    */
   public function mul($b, $m = false) {
      if (!($b instanceof static)) {
         $b = new static($b);
      }
      $r = new static($b);
      $r->d = [];
      $tc = count($this->d);
      $bc = count($b->d);
      for ($i = 0; $i < $tc; $i++) {
         for ($j = 0; $j < $bc; $j++) {
            $ipj = $i + $j;
            $r->d[$ipj] = $this->d[$i] * $b->d[$j] + (isset($r->d[$ipj]) ? $r->d[$ipj] : 0);
         }
      }
      $r->s = $this->s * $b->s;
      $r->mbnCarry();
      if (static::$MbnP >= 1) {
         if (static::$MbnP > 1) {
            $r->d = array_slice($r->d, 0, 1 - static::$MbnP);
         }
         $r->mbnRoundLast();
      }
      return $this->mbnSetReturn($r, $m);
   }

   /**
    * Divide value by b
    * @param {*} $b
    * @param {boolean=} $m Modify original variable, default false
    */
   public function div($b, $m = false) {
      if (!($b instanceof static)) {
         $b = new static($b);
      }
      if ($b->s === 0) {
         throw new MbnErr('.div', 'division by zero');
      }
      if ($this->s === 0) {
         return $this->mbnSetReturn(new static($this), $m);
      }
      $x = $this->d;
      $y = $b->d;
      $p = 0;
      $ra = [0];
      while ($y[0] === 0) {
         array_shift($y);
      }
      while ($x[0] === 0) {
         array_shift($x);
      }
      $mp = static::$MbnP + 1;
      while (count($y) < count($x)) {
         $y[] = 0;
         $mp++;
      }
      do {
         while ($x[($xl = count($x)) - 1] + $y[($yl = count($y)) - 1] === 0) {
            array_pop($x);
            array_pop($y);
         }
         $xge = $xl >= $yl;
         if ($xl === $yl) {
            for ($i = 0; $i < $xl; $i++) {
               if ($x[$i] !== $y[$i]) {
                  $xge = $x[$i] > $y[$i];
                  break;
               }
            }
         }
         if ($xge) {
            $ra[$p] = 1 + (isset($ra[$p]) ? $ra[$p] : 0);
            $ld = $xl - $yl;
            for ($i = $yl - 1; $i >= 0; $i--) {
               if ($x[$i + $ld] < $y[$i]) {
                  $x[$i + $ld - 1] --;
                  $x[$i + $ld] += 10 - $y[$i];
               } else {
                  $x[$i + $ld] -= $y[$i];
               }
            }
         } else {
            $x[] = 0;
            $p++;
            $ra[$p] = 0;
         }
         while (isset($x[0]) && $x[0] === 0) {
            array_shift($x);
         }
      } while (count($x) !== 0 && $p <= $mp);
      while ($p <= $mp) {
         $ra[++$p] = 0;
      }
      array_pop($ra);
      $r = new static($b);
      $r->s *= $this->s;
      $r->d = $ra;
      $r->mbnRoundLast();
      return $this->mbnSetReturn($r, $m);
   }

   /**
    * Modulo, remainder of division value by b, keep sign of value
    * @param {*} $b
    * @param {boolean=} $m Modify original variable, default false
    */
   public function mod($b, $m = false) {
      $ba = ($b instanceof static) ? $b->abs() : (new static($b))->abs();
      $r = $this->sub($this->div($ba)->intp()->mul($ba));
      if (($r->s * $this->s) === -1) {
         $r = $ba->sub($r->abs());
         $r->s = $this->s;
      }
      return $this->mbnSetReturn($r, $m);
   }

   /**
    * Split value to array of values, with same ratios as in given array, or to given number of parts, default 2
    * @param {array} $ar Ratios array or number of parts, default 2
    */
   public function split($ar = 2) {
      $arr = [];
      if (!is_array($ar)) {
         $mbn1 = new static(1);
         $asum = new static($ar);
         if (!$asum->isInt() || $asum->s < 0) {
            throw new MbnErr('.split', 'only natural number of parts supported');
         }
         $n = intval($asum->toNumber());
         for ($i = 0; $i < $n; $i++) {
            $arr[] = $mbn1;
         }
      } else {
         $mulp = new static(1);
         for ($i = 0; $i < static::$MbnP; $i++) {
            $mulp->d[] = 0;
         }
         $asum = new static(0);
         $n = count($ar);
         foreach ($ar as $k => &$v) {
            $ai = (new static($v))->mul($mulp);
            if ($ai->s === -1) {
               throw new MbnErr('.split', 'only non-negative ratio values supported');
            }
            $asum->add($ai, true);
            $arr[$k] = $ai;
         }
         unset($v);
      }
      if ($n === 0) {
         throw new MbnErr('.split', 'cannot split to zero parts');
      }
      $a = new static($this);
      $brr = [];
      foreach ($arr as $k => &$v) {
         if ($v->s === 0) {
            $brr[$k] = $v;
         } else {
            $b = $a->mul($v)->div($asum);
            $asum->sub($v, true);
            $a->sub($b, true);
            $brr[$k] = $b;
         }
      }
      unset($v);
      return $brr;
   }

   /**
    * Returns if the number is integer
    */
   public function isInt() {
      $ct = count($this->d);
      for ($l = $ct - static::$MbnP; $l < $ct; $l++) {
         if ($this->d[$l] !== 0) {
            return false;
         }
      }
      return true;
   }

   /**
    * Returns bigest integer value not greater than number
    * @param {boolean=} $m Modify original variable, default false
    */
   public function floor($m = false) {
      $r = ($m === true) ? $this : new static($this);
      if (static::$MbnP !== 0) {
         $ds = 0;
         $ct = count($r->d);
         for ($l = $ct - static::$MbnP; $l < $ct; $l++) {
            $ds += $r->d[$l];
            $r->d[$l] = 0;
         }
         if ($r->s === -1 && $ds > 0) {
            $r->d[$ct - static::$MbnP - 1] ++;
         }
         $r->mbnCarry();
      }
      return $r;
   }

   /**
    * Rounds number to closest integer value (half-up)
    * @param {boolean=} $m Modify original variable, default false
    */
   public function round($m = false) {
      $r = ($m === true) ? $this : new static($this);
      if (static::$MbnP !== 0) {
         $ct = count($r->d);
         $l = $ct - static::$MbnP;
         $r->d[$l - 1] += ($r->d[$l] >= 5) ? 1 : 0;
         while ($l < $ct) {
            $r->d[$l++] = 0;
         }
         $r->mbnCarry();
      }
      return $r;
   }

   /**
    * Returns absolute value
    * @param {boolean=} $m Modify original variable, default false
    */
   public function abs($m = false) {
      $r = ($m === true) ? $this : new static($this);
      $r->s *= $r->s;
      return $r;
   }

   /**
    * Returns additive inverse of value
    * @param {boolean=} $m Modify original variable, default false
    */
   public function inva($m = false) {
      $r = ($m === true) ? $this : new static($this);
      $r->s = -$r->s;
      return $r;
   }

   /**
    * Returns multiplicative inverse of value
    * @param {boolean=} $m Modify original variable, default false
    */
   public function invm($m = false) {
      $r = (new static(1))->div($this);
      return $this->mbnSetReturn($r, $m);
   }

   /**
    * Returns lowest integer value not lower than number
    * @param {boolean=} $m Modify original variable, default false
    */
   public function ceil($m = false) {
      $r = ($m === true) ? $this : new static($this);
      return $r->inva(true)->floor(true)->inva(true);
   }

   /**
    * Returns integer part of number
    * @param {boolean=} $m Modify original variable, default false
    */
   public function intp($m = false) {
      $r = ($m === true) ? $this : new static($this);
      return($r->s >= 0) ? $r->floor(true) : $r->ceil(true);
   }

   /**
    * Returns if value equals b
    * @param {*} $b
    * @param {*} $d Maximum difference treated as equality, default 0
    */
   public function eq($b, $d = 0) {
      return $this->cmp($b, $d) === 0;
   }

   /**
    * Returns minimum from value and b
    * @param {*} $b
    * @param {boolean=} $m Modify original variable, default false
    */
   public function min($b, $m = false) {
      return $this->mbnSetReturn(new static((($this->cmp($b)) <= 0) ? $this : $b), $m);
   }

   /**
    * Returns maximum from value and b
    * @param {*} $b
    * @param {boolean=} $m Modify original variable, default false
    */
   public function max($b, $m = false) {
      return $this->mbnSetReturn(new static((($this->cmp($b)) >= 0) ? $this : $b), $m);
   }

   /**
    * Returns square root of value
    * @param {boolean=} $m Modify original variable, default false
    */
   public function sqrt($m = false) {
      $t = new static($this);
      $t->d[] = 0;
      $t->d[] = 0;
      $rb = new static($t);
      $r = new static($t);
      $mbn2 = new static('2');
      if ($r->s === -1) {
         throw new MbnErr('.sqrt', 'square root of negative number', $this);
      } else if ($r->s === 1) {
         do {
            $rb->set($r);
            $r->add($t->div($r), true)->div($mbn2, true);
         } while (!$rb->eq($r));
      }
      $r->mbnRoundLast();
      return $this->mbnSetReturn($r, $m);
   }

   /**
    * Returns sign from value, 1 - positive, -1 - negative, otherwise 0
    * @param {boolean=} $m Modify original variable, default false
    */
   public function sgn($m = false) {
      return $this->mbnSetReturn(new static($this->s), $m);
   }

   /**
    * Returns value to the power of b, b must be integer
    * @param {number} $b
    * @param {boolean=} $m Modify original variable, default false
    */
   public function pow($b, $m = false) {
      $n = new static($b);
      if (!$n->isInt()) {
         throw new MbnErr('.pow', 'only integer exponents supported', $n);
      }
      $ns = $n->s;
      $n->s *= $n->s;
      $ni = intval($n->toNumber());
      $mbn1 = new static(1);
      $rx = new static($this);
      if ($ns === -1 && $this->abs()->cmp($mbn1) === -1) {
         $rx->invm(true);
         $ns = -$ns;
      }
      $dd = 0;
      $cdd = 0;
      $r = new static($mbn1);
      while (!$rx->isInt()) {
         $rx->d[] = 0;
         $rx->mbnCarry();
         $dd++;
      }
      while (true) {
         if ($ni % 2 === 1) {
            $r->mul($rx, true);
            $cdd += $dd;
         }
         $ni = intval($ni / 2);
         if ($ni === 0) {
            break;
         }
         $rx->mul($rx, true);
         $dd *= 2;
      }
      if ($cdd >= 1) {
         if ($cdd > 1) {
            $r->d = array_slice($r->d, 0, 1 - $cdd);
         }
         $r->mbnRoundLast();
      }
      if ($ns === -1) {
         $r->invm(true);
      }
      return $this->mbnSetReturn($r, $m);
   }

   protected static $fnReduce = ['set' => 0, 'abs' => 1, 'inva' => 1, 'invm' => 1, 'ceil' => 1, 'floor' => 1,
       'sqrt' => 1, 'round' => 1, 'sgn' => 1, 'intp' => 1,
       'min' => 2, 'max' => 2, 'add' => 2, 'sub' => 2, 'mul' => 2, 'div' => 2, 'mod' => 2, 'pow' => 2];

   /**
    * Runs function on each element, returns:
    * single value for 2 argument function (arr[0].fn(arr[1]).fn(arr[2]), ..)
    * array of products for 1 argument function [arr[0].fn(), arr[1].fn(), ..]
    * array of products for 2 argument function and when b is same size array or single value
    * [arr[0].fn(b[0]), arr[1].fn(b[1]), ..] or [arr[0].fn(b), arr[1].fn(b), ..]
    * @param {string} $fn
    * @param {*} $arr
    * @param {*=} $b
    */
   public static function reduce($fn, $arr, $b = null) {
      $inv = false;
      if (!is_string($fn) || !isset(static::$fnReduce[$fn])) {
         throw new MbnErr('.reduce', 'invalid function name', $fn);
      }
      if (!is_array($arr)) {
         if (!is_array($b)) {
            throw new MbnErr('.reduce', 'argument is not array', $arr);
         }
         $inv = $b;
         $b = $arr;
         $arr = $inv;
      }
      $mode = static::$fnReduce[$fn];
      $bmode = (($b !== null) ? (is_array($b) ? 2 : 1) : 0);
      if ($mode !== 2 && $bmode !== 0) {
         throw new MbnErr('.reduce', 'two agruments can be used with two-argument functions');
      }
      if ($mode === 2 && $bmode === 0) {
         $r = new static(0);
         $fst = true;
         foreach ($arr as $k => &$v) {
            if ($fst) {
               $r->set($v);
               $fst = false;
            } else {
               $r->{$fn}($v, true);
            }
         }
         unset($v);
      } else {
         $r = [];
         if ($bmode === 2 && array_keys($arr) !== array_keys($b)) {
            throw new MbnErr('.reduce', 'arrays have different length', $b);
         }
         $bv = ($bmode === 1) ? (new static($b)) : null;
         foreach ($arr as $k => &$v) {
            $e = new static($v);
            if ($bmode !== 0) {
               $bi = (($bmode === 2) ? (new static($b[$k])) : $bv);
               $e->set(($inv === false) ? $e->{$fn}($bi) : $bi->{$fn}($e));
            }
            $r[$k] = ($mode === 1) ? $e->{$fn}(true) : $e;
         }
         unset($v);
      }
      return $r;
   }

   protected static $MbnConst = [
       '' => ['PI' => '3.1415926535897932384626433832795028841972',
           'E' => '2.7182818284590452353602874713526624977573',
           'eps' => true]
   ];

   /**
    * Sets and reads constant
    * @param {string|null} $n Constant name, must start with upper-case letter
    * @param {*=} v$ Constant value to set
    */
   public static function def($n, $v = null) {
      $mc = &static::$MbnConst;
      $mx = get_class(new static());
      if ($n === null) {
         return (isset($mc[$mx][$v]) || isset($mc[''][$v]));
      }
      if ($v === null) {
         if (!isset($mc[$mx])) {
            $mc[$mx] = [];
         }
         if (!isset($mc[$mx][$n])) {
            if (!isset($mc[''][$n])) {
               throw new MbnErr('.def', 'undefined constant', $n);
            }
            $mc[$mx][$n] = ($n === "eps") ? ((new static(10))->pow(-static::$MbnP)) : (new static($mc[''][$n]));
         }
         return new static($mc[$mx][$n]);
      } else {
         if (isset($mc[$mx][$n]) || isset($mc[''][$n])) {
            throw new MbnErr('.def', 'constant allready set', $n);
         } else {
            if (preg_match('/^[A-Z]\\w*$/', $n) !== 1) {
               throw new MbnErr('.def', 'incorrect name', $n);
            }
            $v = new static($v);
            $mc[$mx][$n] = $v;
            return new static($v);
         }
      }
   }

   protected static $fnEval = ['abs' => true, 'inva' => false, 'ceil' => true, 'floor' => true,
       'sqrt' => true, 'round' => true, 'sgn' => true, 'int' => 'intp'];
   protected static $states = [
       'endBopPr' => ['bop', 'pc', 'pr'],
       'endBop' => ['bop', 'pc'],
       'uopVal' => ['num', 'name', 'uop', 'po'],
       'po' => ['po']
   ];
   protected static $endBop = ['bop', 'pc'];
   protected static $uopVal = ['num', 'name', 'uop', 'po'];
   protected static $bops = [
       '|' => [1, true, 'max'],
       '&' => [2, true, 'min'],
       '+' => [3, true, 'add'],
       '-' => [3, true, 'sub'],
       '*' => [4, true, 'mul'],
       '#' => [4, true, 'mod'],
       '/' => [4, true, 'div'],
       '^' => [5, false, 'pow']
   ];
   protected static $funPrx = 4;
   protected static $rxs = [
       'num' => ['rx' => '/^([0-9\., ]+)\\s*/', 'next' => 'endBopPr', 'end' => true],
       'name' => ['rx' => '/^([A-Za-z_]\\w*)\\s*/'], 'fn' => ['next' => 'po', 'end' => false],
       'vr' => ['next' => 'endBop', 'end' => true],
       'bop' => ['rx' => '/^([-+\\*\\/#^&|])\\s*/', 'next' => 'uopVal', 'end' => false],
       'uop' => ['rx' => '/^([-+])\s*/', 'next' => 'uopVal', 'end' => false],
       'po' => ['rx' => '/^(\\()\\s*/', 'next' => 'uopVal', 'end' => false],
       'pc' => ['rx' => '/^(\\))\\s*/', 'next' => 'endBop', 'end' => true],
       'pr' => ['rx' => '/^(%)\\s*/', 'next' => 'endBop', 'end' => true]
   ];

   /**
    * Evaluate expression
    * @param {string} $exp Evaluation formula
    * @param {array|boolean=} $vars Object with vars for evaluation
    */
   public static function calc($exp, $vars = null) {
      if ($vars !== true && !is_array($vars)) {
         $vars = [];
      }
      return new static($exp, $vars);
   }

   private static function mbnCalc($exp, $vars = null) {
      $expr = preg_replace('/^[\\s=]+/', '', $exp);
      if (!is_array($vars)) {
         $vars = [];
      }
      $vnames = [];
      $larr = &static::$states['uopVal'];
      $larl = count($larr);
      $lare = false;
      $rpns = [];
      $rpno = [];
      $neg = false;
      $t = null;
      $invaUop = [static::$funPrx, true, 'inva'];

      while (strlen($expr) > 0) {
         $mtch = [];
         foreach ($larr as $t) {
            if (preg_match(static::$rxs[$t]['rx'], $expr, $mtch) == 1) {
               break;
            }
         }
         if (empty($mtch)) {
            if ($larr[0] === 'bop') {
               $tok = '*';
               $t = 'bop';
            } else {
               throw new MbnErr('.calc', 'unexpected', $expr);
            }
         } else {
            $tok = $mtch[1];
            $expr = substr($expr, strlen($mtch[0]));
         }
         if ($t !== 'uop' && $neg) {
            $rpno[] = &$invaUop;
            $neg = false;
         }
         switch ($t) {
            case 'num':
               $rpns[] = new static($tok, false);
               break;
            case 'name':
               if (isset(static::$fnEval[$tok]) && static::$fnEval[$tok] !== false) {
                  $t = 'fn';
                  $rpno [] = [static::$funPrx, true, $tok];
               } elseif (isset($vars[$tok]) || array_key_exists($tok, $vars)) {
                  $t = 'vr';
                  if (!isset($vnames[$tok])) {
                     $vnames[$tok] = new static($vars[$tok]);
                  }
                  $rpns [] = new static($vnames[$tok]);
               } elseif (static::def(null, $tok)) {
                  $t = 'vr';
                  $rpns [] = static::def($tok);
               } else {
                  throw new MbnErr('.calc', 'undefined', $tok);
               }
               break;
            case 'bop':
               $bop = static::$bops[$tok];
               while (($rolm = count($rpno) - 1) !== -1) {
                  $rolp = $rpno[$rolm];
                  if ($rolp !== '(' && ($rolp[0] > $bop[0] - ($bop[1] ? 1 : 0))) {
                     $rpns[] = array_pop($rpno)[2];
                  } else {
                     break;
                  }
               }
               $rpno[] = $bop;
               break;
            case 'uop':
               if ($tok === '-') {
                  $neg = !$neg;
               }
               break;
            case 'po':
               $rpno [] = $tok;
               break;
            case 'pc':
               while (($rolm = count($rpno) - 1) !== -1) {
                  $rolp = $rpno[$rolm];
                  if ($rolp !== '(') {
                     $rpns[] = array_pop($rpno)[2];
                  } else {
                     array_pop($rpno);
                     break;
                  }
               }
               if ($rolm === -1) {
                  throw new MbnErr('.calc', 'unexpected', ')');
               } else {
                  $rolm = count($rpno) - 1;
                  if ($rolm !== -1 && $rpno[$rolm][2] === static::$funPrx) {
                     $rpns[] = array_pop($rpno)[2];
                  }
               }
               break;
            case 'pr':
               $rpns[count($rpns) - 1]->div(100, true);
               break;
            default:
         }

         $larr = &static::$states[static::$rxs[$t]['next']];
         $larl = count($larr);
         $lare = static::$rxs[$t]['end'];
      }
      while (count($rpno) !== 0) {
         $v = array_pop($rpno);
         if ($v !== '(') {
            $rpns[] = $v[2];
         } else {
            throw new MbnErr('.calc', 'unexpected', '(');
         }
      }
      if (!$lare) {
         throw new MbnErr('.calc', 'unexpected', 'END');
      }

      $rpn = [];

      foreach ($rpns as &$tn) {
         if ($tn instanceof static) {
            $rpn[] = $tn;
         } elseif (isset(static::$fnEval[$tn])) {
            if (is_string(static::$fnEval[$tn])) {
               $tn = static::$fnEval[$tn];
            }
            $rpn[count($rpn) - 1]->{$tn}(true);
         } else {
            $pp = array_pop($rpn);
            $rpn[count($rpn) - 1]->{$tn}($pp, true);
         }
      }
      return $rpn[0];
   }

}
