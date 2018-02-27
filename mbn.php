<?php

/**
 * MultiByteNumber
 * Mikołaj Błajek
 * mblajek_mbn(at)mailplus.pl
 */

/**
 * Common error message object
 * @export
 * @constructor
 * @param {string} fn
 * @param {string} msg
 * @param {*=} val
 */
class MbnErr extends Exception {

   public function __construct($fn, $msg, $val = null) {
      $ret = 'Mbn' . $fn . ' error: ' . $msg;
      if ($val !== null) {
         $val = is_array($val) ? implode(",", $val) : strval($val);
         $ret .= ': ' . ((strlen($val) > 10) ? (substr($val, 0, 8) . '..') : $val);
      }
      parent::__construct($ret);
   }

}

/**
 * Class Mbn
 */
class Mbn {

   //version of MultiByteNumber library
   protected static $MbnV = '1.30';
   //default precision
   protected static $MbnP = 2;
   //default separator
   protected static $MbnS = '.';
   //default truncate
   protected static $MbnT = false;
   //default truncate
   protected static $MbnE = true;
   //default truncate
   protected static $MbnF = false;
   private $d = [];
   private $s = 1;

   /**
    * Private function, carries digits bigger than 9, and removes leading zeros
    * @param {Mbn} a
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
    * Private function, if m is true, sets value of a to b and return a, otherwise returns b
    * @param {Mbn} a
    * @param {Mbn} b
    * @param {boolean} m
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
    * @param {Mbn} a
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
    * Private function, sets value of Mbn to string value n
    * @param {Mbn} a
    * @param {string} ns
    */
   private function fromString($ns, $v) {
      $np = [];
      preg_match('/([+=-]?)\\s*(.*)/', trim($ns), $np);
      $n0 = $np[1];
      $n = $np[2];
      if ($n0 === '-') {
         $this->s = -1;
      } elseif ($n0 === '=' && static::$MbnE) {
         $this->set(static::calc($n, $v));
         return;
      }
      $ln = strpos($n, '.');
      if ($ln === false) {
         $ln = strpos($n, ',');
      }
      if ($ln === false) {
         $ln = strlen($n);
      } else {
         $n = substr($n, 0, $ln) . substr($n, $ln + 1);
      }
      if ($ln === 0) {
         $ln = 1;
         $n = '0' . (($n !== '') ? $n : $np[2]);
      }
      $c = '';
      $nl = strlen($n);
      $l = max($ln + static::$MbnP, $nl);
      for ($i = 0; $i <= $l; $i++) {
         $c = ($i < $nl) ? (ord($n[$i]) - 48) : 0;
         if ($c >= 0 && $c <= 9) {
            if ($i <= $ln + static::$MbnP) {
               $this->d[] = $c;
            }
         } elseif ($c === -16 && ($i + 1) < $ln) {
            continue;
         } else {
            throw new MbnErr('', 'invalid format', $ns);
         }
      }
      $this->mbnRoundLast();
   }

   /**
    * Private function, sets value of Mbn to number value n
    * @param {number} nn
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
    * Private function, returns string value from Mbn a
    * @param {string} s
    * @param {boolean} f
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
    * @param {*=} n
    * @param {*=} v
    */
   public function __construct($n = 0, $v = null) {
      if (is_float($n) || is_int($n)) {
         $this->mbnFromNumber($n);
      } elseif (is_string($n)) {
         $this->fromString($n, $v);
      } elseif (is_object($n)) {
         if (!($n instanceof static)) {
            $this->fromString(strval($n), $v);
         } else {
            $this->set($n->toString());
         }
      } elseif (is_bool($n) || is_null($n)) {
         $n = $this->mbnFromNumber(intval($n));
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
    * sets value to b
    * @param {*} b
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
    * Returns string value of Mbn number
    */
   protected function toString() {
      return $this->mbnToString(static::$MbnS, static::$MbnF);
   }

   /**
    * Returns string value with thousand grouping
    * @param {boolean=} f
    */
   public function format($f = true) {
      return $this->mbnToString(static::$MbnS, $f);
   }

   public function __toString() {
      return $this->toString();
   }

   /**
    * Returns number value of Mbn number
    */
   public function toNumber() {
      $v = $this->mbnToString('.', false);
      return ($this->isInt()) ? intval($v) : floatval($v);
   }

   /**
    * Compare Mbn number to b, if is bigger than b, returns 1, if is lower, returns -1, if equals returns 0
    * @param {*=} b
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
    * Add b to Mbn number
    * @param {*} b
    * @param {boolean=} m Modify original variable
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
    * @param {*} b
    * @param {boolean=} m Modify original variable
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
    * @param {*} b
    * @param {boolean=} m Modify original variable
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
    * @param {*} b
    * @param {boolean=} m Modify original variable
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
    * Modulo from divide value by b
    * @param {*} b
    * @param {boolean=} m Modify original variable
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
    * Split value to array of values, with same ratios as in given array
    * @param {array} ar
    */
   public function split($ar = 2) {
      $arr = [];
      if (!is_array($ar)) {
         $mbn1 = new static(1);
         $asum = new static($ar);
         if (!$asum->isInt() || $asum->s < 0) {
            throw new MbnErr('.split', 'only natural number of parts supported');
         }
         $n = $asum->toNumber();
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
    * Returns true if the number is integer
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
    * @param {boolean=} m Modify original variable
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
    * Rounds number to closest integer value
    * @param {boolean=} m Modify original variable
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
    * Returns absolute value from number
    * @param {boolean=} m Modify original variable
    */
   public function abs($m = false) {
      $r = ($m === true) ? $this : new static($this);
      $r->s *= $r->s;
      return $r;
   }

   /**
    * returns additional inverse of number
    * @param {boolean=} m Modify original variable
    */
   public function inva($m = false) {
      $r = ($m === true) ? $this : new static($this);
      $r->s = -$r->s;
      return $r;
   }

   /**
    * returns multiplication inverse of number
    * @param {boolean=} m Modify original variable
    */
   public function invm($m = false) {
      $r = (new static(1))->div($this);
      return $this->mbnSetReturn($r, $m);
   }

   /**
    * Returns lowest integer value not lower than number
    * @param {boolean=} m Modify original variable
    */
   public function ceil($m = false) {
      $r = ($m === true) ? $this : new static($this);
      return $r->inva(true)->floor(true)->inva(true);
   }

   /**
    * Returns integer part of number
    * @param {boolean=} m Modify original variable
    */
   public function intp($m = false) {
      $r = ($m === true) ? $this : new static($this);
      return($r->s >= 0) ? $r->floor(true) : $r->ceil(true);
   }

   /**
    * returns if number equals to b, or if d is set, difference is lower or equals d
    * @param {*} b
    * @param {*} d
    */
   public function eq($b, $d = 0) {
      return $this->cmp($b, $d) === 0;
   }

   /**
    * returns minimum from value and b
    * @param {*} b
    * @param {boolean=} m Modify original variable
    */
   public function min($b, $m = false) {
      return $this->mbnSetReturn(new static((($this->cmp($b)) <= 0) ? $this : $b), $m);
   }

   /**
    * returns maximum from value and b
    * @param {*} b
    * @param {boolean=} m Modify original variable
    */
   public function max($b, $m = false) {
      return $this->mbnSetReturn(new static((($this->cmp($b)) >= 0) ? $this : $b), $m);
   }

   /**
    * calculates square root of number
    * @param {boolean=} m Modify original variable
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
    * returns sign from value
    * @param {boolean=} m Modify original variable
    */
   public function sgn($m = false) {
      return $this->mbnSetReturn(new static($this->s), $m);
   }

   /**
    * Calculates n-th power of number, n must be integer
    * @param {number} nd
    * @param {boolean=} m Modify original variable
    */
   public function pow($nd, $m = false) {
      $n = new static($nd);
      if (!$n->isInt()) {
         throw new MbnErr('.pow', 'only integer exponents supported', $n);
      }
      $ns = $n->s;
      $n->s *= $n->s;

      $mbn1 = new static(1);
      $mbn2 = new static(2);
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
      while ($n->s === 1) {
         if ($n->d[count($n->d) - static::$MbnP - 1] % 2) {
            $r->mul($rx, true);
            $n->sub($mbn1, true);
            $cdd += $dd;
         }
         $n->div($mbn2, true)->intp(true);
         if ($n->s !== 1) {
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
       'sqrt' => 1, 'round' => 1, 'sgn' => 1, 'intp' => 1, 'add' => 2, 'mul' => 2, 'min' => 2, 'max' => 2, 'pow' => 2];

   /**
    * run function on each element, returns single value for 2 argument function,
    * and array, for 1 argument
    * @param {string} fn
    * @param {Array} arr
    * @param {*=} b
    */
   public static function reduce($fn, $arr, $b = null) {
      if (!isset(static::$fnReduce[$fn])) {
         throw new MbnErr('.reduce', 'invalid function name', $fn);
      }
      if (!is_array($arr)) {
         throw new MbnErr('.reduce', 'argument is not array', $arr);
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
               $e->{$fn}(($bmode === 2) ? (new static($b[$k])) : $bv, true);
            }
            $r[$k] = ($mode === 1) ? $e->{$fn}(true) : $e;
         }
         unset($v);
      }
      return $r;
   }

   protected static $MbnConst = [
       '' => ['PI' => '3.1415926535897932384626433832795028841972', 'E' => '2.7182818284590452353602874713526624977573']
   ];

   //$cnRx = ;
   /**
    * Sets and reads constant
    * @param {string|null} n
    * @param {*=} v
    */
   public static function def($n, $v = null) {
      $mc = &static::$MbnConst;
      $mx = get_class(new static());
      if ($n === null) {
         return (isset($mc[$mx][$v]) || isset($mc[''][$v]));
      }
      if (preg_match('/^[A-Z]\\w*$/', $n) !== 1) {
         throw new MbnErr('.def', 'incorrect name', $n);
      }
      if ($v === null) {
         if (!isset($mc[$mx])) {
            $mc[$mx] = [];
         }
         if (!isset($mc[$mx][$n])) {
            if (isset($mc[''][$n])) {
               $mc[$mx][$n] = new static($mc[''][$n]);
            } elseif ($n === 'MbnP') {
               $mc[$mx][$n] = new static(static::$MbnP);
            } else {
               throw new MbnErr('.def', 'undefined constant', $n);
            }
         }
         return new static($mc[$mx][$n]);
      } else {
         if (isset($mc[$mx][$n]) || isset($mc[''][$n])) {
            throw new MbnErr('.def', 'constant allready set', $n);
         } else {
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
       'num' => ['rx' => '/^([0-9\., ]+)\s*/', 'next' => 'endBopPr', 'end' => true],
       'name' => ['rx' => '/^([A-Za-z_]\w*)\s*/'], 'fn' => ['next' => 'po', 'end' => false],
       'vr' => ['next' => 'endBop', 'end' => true],
       'bop' => ['rx' => '/^([-+\*\/#^&|])\s*/', 'next' => 'uopVal', 'end' => false],
       'uop' => ['rx' => '/^([-+])\s*/', 'next' => 'uopVal', 'end' => false],
       'po' => ['rx' => '/^(\()\s*/', 'next' => 'uopVal', 'end' => false],
       'pc' => ['rx' => '/^(\))\s*/', 'next' => 'endBop', 'end' => true],
       'pr' => ['rx' => '/^(%)\s*/', 'next' => 'endBop', 'end' => true]
   ];

   /**
    * eval expression
    * @param {string} expr
    * @param {*=} vars
    */
   public static function calc($exp, $vars = null) {
      $expr = preg_replace('/^\s+/', '', $exp);
      $vnames = [];
      if ($vars !== null) {
         foreach ($vars as $k => &$v) {
            $vnames[$k] = new static($v);
         }
         unset($v);
      }
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
               $rpns[] = new static($tok);
               break;
            case 'name':
               if (isset(static::$fnEval[$tok]) && static::$fnEval[$tok] !== false) {
                  $t = 'fn';
                  $rpno [] = [static::$funPrx, true, $tok];
               } elseif (isset($vnames[$tok])) {
                  $t = 'vr';
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
