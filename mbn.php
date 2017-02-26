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
 * @param {string} f
 * @param {string} m
 * @param {*=} v
 */
class MbnErr extends Exception {

   function __construct($f = '', $m = '', $v = null) {
      parent::__construct('Mbn' . $f . ' error: ' . $m . (($v !== null) ? (": " . $v) : ""));
   }

}

/**
 * Class Mbn
 */
class Mbn {

   //version of MultiByteNumber library
   const MbnV = '1.8';

   //default precision
   protected static $MbnP = 2;
   //default separator
   protected static $MbnS = '.';
   //default truncate
   protected static $MbnT = false;
   protected static $MbnX;
   private $d = array();
   private $s = 1;

   /**
    * Private function, carries digits bigger than 9, and removes leading zeros
    * @param {Mbn} a
    */
   private static function mbnCarry($a) {
      $r = &$a->d;
      $i = count($r) - 1;
      while ($i >= 0) {
         $di = $r[$i];
         while ($di < 0) {
            $di += 10;
            $r[$i - 1] --;
         }
         $dd = $di % 10;
         $ci = ($di - $dd) / 10;
         $r[$i] = $dd;
         if ($ci) {
            if ($i) {
               $r[--$i] += $ci;
            } else {
               array_unshift($r, $ci);
            }
         } else {
            $i--;
         }
      }
      while (count($r) > static::$MbnP + 1 && $r[0] === 0) {
         array_shift($r);
      }
      while (count($r) < static::$MbnP + 1) {
         array_unshift($r, 0);
      }
      if (count($r) === static::$MbnP + 1) {
         for ($i = 0; $i <= static::$MbnP; $i++) {
            if ($r[$i] !== 0) {
               break;
            }
         }
         $a->s *= ($i <= static::$MbnP) ? 1 : 0;
      }
   }

   /**
    * Private function, if m is true, sets value of a to b and return a, otherwise returns b
    * @param {Mbn} a
    * @param {Mbn} b
    * @param {boolean} m
    */
   private static function mbnSetReturn($a, $b, $m) {
      if ($m === true) {
         $a->d = &$b->d;
         $a->s = $b->s;
         return $a;
      }
      return $b;
   }

   /**
    * Private function, removes last digit and rounds next-to-last depending on it
    * @param {Mbn} a
    */
   private static function mbnRoundLast($a) {
      $r = &$a->d;
      if (count($r) < 2) {
         array_unshift($r, 0);
      }
      $r[count($r) - 2] += (array_pop($r) >= 5) ? 1 : 0;
      static::mbnCarry($a);
   }

   /**
    * Private function, sets value of a to string value n
    * @param {Mbn} a
    * @param {string} nd
    */
   private function fromString($nd) {
      $n = preg_replace('/\\s+$/', '', preg_replace('/^\\s*([+=-]?)\\s*/', '$1', $nd));
      if ($n[0] === '-' || $n[0] === '+') {
         $this->s = ($n[0] === '-') ? -1 : 1;
         $n = substr($n, 1);
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
         $n = '0' . $n;
      }
      $c = '';
      $nl = strlen($n);
      for ($i = 0; $i <= $ln + static::$MbnP; $i++) {
         $c = ($i < $nl) ? (ord($n[$i]) - 48) : 0;
         if ($c >= 0 && $c <= 9) {
            $this->d[] = $c;
         } else {
            throw new MbnErr('', 'invalid format', $nd);
         }
      }
      static::mbnRoundLast($this);
   }

   /**
    * Private function, returns string from number, with MbnP + 1 digits
    * @param {number} x
    */
   private function mbnFromNumber($x) {
      if (!is_finite($x)) {
         throw new MbnErr('', 'invalid value', $x);
      }
      $this->s = 1;
      $this->d = array();
      if ($x < 0) {
         $x = -$x;
         $this->s = -1;
      }
      $xi = intval($x);
      $xf = $x - $xi;
      do {
         $d = $xi % 10;
         $xi -= $d;
         $xi /= 10;
         array_unshift($this->d, $d);
      } while ($xi > 0);
      for ($n = 0; $n <= static::$MbnP; $n++) {
         $xf *= 10;
         $xff = intval($xf);
         $xffi = ($xff === 10) ? 9 : $xff;
         $this->d[] = $xffi;
         $xf -= $xffi;
      }
      static::mbnRoundLast($this);
   }

   /**
    * Returns if object is not Mbn
    */
   private static function isNotMbn($a) {
      if (!isset(static::$MbnX)) {
         static::$MbnX = get_class(new static());
      }
      return (!is_object($a) || (get_class($a) !== static::$MbnX));
   }

   /**
    * Constructor of Mbn object
    * @export
    * @constructor
    * @param {*=} n
    * @param {*=} v
    */
   public function __construct($n = 0) {
      if (is_float($n) || is_int($n)) {
         $this->mbnFromNumber($n);
      } elseif (is_string($n)) {
         $this->fromString($n);
      } elseif (is_object($n)) {
         if (static::isNotMbn($n)) {
            $this->fromString($n->toString(static::$MbnS));
         } else {
            $this->set($n);
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
      return array(
          'MbnV' => static::MbnV,
          'MbnP' => static::$MbnP,
          'MbnS' => static::$MbnS,
          'MbnT' => static::$MbnT
      );
   }

   /**
    * sets value to b
    * @param {*} b
    */
   public function set($b) {
      if (static::isNotMbn($b)) {
         $this->set(new static($b));
      } else {
         $this->d = $b->d;
         $this->s = $b->s;
      }
      return $this;
   }

   /**
    * Returns string value of Mbn number, with seprator MbnS or s if is set
    * @param {string=} s
    */
   protected function toString() {
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
      $r = (($this->s < 0) ? '-' : '') . implode(array_slice($this->d, 0, $l), '');
      if (static::$MbnP !== 0 && $l0 >= $l) {
         $r .= static::$MbnS . implode(array_slice($this->d, $l, $l0 + 1 - $l), '');
      }
      return $r;
   }

   public function __toString() {
      return $this->toString();
   }

   /**
    * Returns number value of Mbn number
    */
   public function toNumber() {
      $v = str_replace(',', '.', $this->toString());
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
         if (static::isNotMbn($b)) {
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
    * @param {boolean=} m
    */
   public function add($b, $m = false) {
      if (static::isNotMbn($b)) {
         $b = new static($b);
      }
      $r = new static($b);
      if ($this->s === 0) {
         //r.set(b);
      } else if ($b->s === 0) {
         $r->set($this);
      } else if ($b->s === $this->s) {
         $ld = count($this->d) - count($b->d);
         if ($ld < 0) {
            //r.set(b);
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
         static::mbnCarry($r);
      } else {
         $r->s = -$r->s;
         $r->sub($this, true);
         $r->s = -$r->s;
      }
      return static::mbnSetReturn($this, $r, $m);
   }

   /**
    * Substract b from value
    * @param {*} b
    * @param {boolean=} m
    */
   public function sub($b, $m = false) {
      if (static::isNotMbn($b)) {
         $b = new static($b);
      }
      $r = new static($b);
      if ($this->s === 0) {
         //r.set(b);
         $r->s = -$r->s;
      } else if ($b->s === 0) {
         $r->set($this);
      } else if ($b->s === $this->s) {
         $ld = count($this->d) - count($b->d);
         $cmp = $this->cmp($b) * $this->s;
         if ($cmp === 0) {
            $r = new static('0');
         } else {
            if ($cmp === -1) {
               //r.set(b);
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
            static::mbnCarry($r);
         }
      } else {
         $r->s = -$r->s;
         $r->add($this, true);
      }
      return static::mbnSetReturn($this, $r, $m);
   }

   /**
    * Multiple value by b
    * @param {*} b
    * @param {boolean=} m
    */
   function mul($b, $m = false) {
      if (static::isNotMbn($b)) {
         $b = new static($b);
      }
      $r = new static($b);
      $r->d = array();
      $tc = count($this->d);
      $bc = count($b->d);
      for ($i = 0; $i < $tc; $i++) {
         for ($j = 0; $j < $bc; $j++) {
            $ipj = $i + $j;
            $r->d[$ipj] = $this->d[$i] * $b->d[$j] + (isset($r->d[$ipj]) ? $r->d[$ipj] : 0);
         }
      }
      $r->s = $this->s * $b->s;
      static::mbnCarry($r);
      if (static::$MbnP >= 1) {
         if (static::$MbnP > 1) {
            $r->d = array_slice($r->d, 0, 1 - static::$MbnP);
         }
         static::mbnRoundLast($r);
      }
      return static::mbnSetReturn($this, $r, $m);
   }

   /**
    * Divide value by b
    * @param {*} b
    * @param {boolean=} m
    */
   function div($b, $m = false) {
      if (static::isNotMbn($b)) {
         $b = new static($b);
      }
      if ($b->s === 0) {
         throw new MbnErr('.div', 'division by zero');
      }
      if ($this->s === 0) {
         return static::mbnSetReturn($this, new static($this), $m);
      }
      $x = $this->d;
      $y = $b->d;
      $p = 0;
      $ra = array(
          0);
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
      static::mbnRoundLast($r);
      return static::mbnSetReturn($this, $r, $m);
   }

   /**
    * Modulo from divide value by b
    * @param {*} b
    * @param {boolean=} m
    */
   function mod($b, $m = false) {
      $ba = static::isNotMbn($b) ? (new static($b))->abs() : $b->abs();
      $r = $this->sub($this->div($ba)->int()->mul($ba));
      if (($r->s + $this->s) === 0) {
         $r = $ba->sub($r->abs());
         $r->s = $this->s;
      }
      return static::mbnSetReturn($this, $r, $m);
   }

   /**
    * Split value to array of values, with same ratios as in given array
    * @param {array} ar
    */
   function split($ar = 2) {
      $arr = array();
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
         $asum = new static(0);
         $n = count($ar);
         for ($i = 0; $i < $n; $i++) {
            $arr[] = new static($ar[$i]);
            $asum->add($arr[$i], true);
         }
      }
      if (count($ar) === 0) {
         return array();
      }
      $a = new static($this);
      $brr = array();
      $n--;
      for ($i = 0; $i < $n; $i++) {
         $b = $a->mul($arr[$i])->div($asum);
         $asum->sub($arr[$i], true);
         $a->sub($b, true);
         $brr[] = $b;
      }
      $brr[] = $a;
      return $brr;
   }

   /**
    * Returns true if the number is integer
    */
   function isInt() {
      $ct = count($this->d);
      for ($l = $ct - static::$MbnP; $l < $ct; $l++) {
         if ($this->d[$l] !== 0) {
            return false;
         }
      }
      return true;
   }

   /**
    * Calculates n-th power of number, n must be integer
    * @param {number} n
    * @param {boolean=} m
    */
   function pow($n, $m = false) {
      $nm = new static($n);
      if (!$nm->isInt()) {
         throw new MbnErr();
      }
      $nn = $nm->toNumber();
      $r = new static(1);
      for ($i = 0; $i < $nn; $i++) {
         $r->mul($this, true);
      }
      for ($i = 0; $i > $nn; $i--) {
         $r->div($this, true);
      }
      return static::mbnSetReturn($this, $r, $m);
   }

   /**
    * Returns bigest integer value not greater than number
    * @param {boolean=} m
    */
   function floor($m = false) {
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
         static::mbnCarry($r);
      }
      return $r;
   }

   /**
    * Rounds number to closest integer value
    * @param {boolean=} m
    */
   function round($m = false) {
      $r = ($m === true) ? $this : new static($this);
      if (static::$MbnP !== 0) {
         $ct = count($r->d);
         $l = $ct - static::$MbnP;
         $r->d[$l - 1] += ($r->d[$l] >= 5) ? 1 : 0;
         while ($l < $ct) {
            $r->d[$l++] = 0;
         }
         static::mbnCarry($r);
      }
      return $r;
   }
   /*
    * Returns absolute value from number
    * @param {boolean=} m
    */

   function abs($m = false) {
      $r = ($m === true) ? $this : new static($this);
      $r->s *= $r->s;
      return $r;
   }

   /**
    * returns additional inverse of number
    * @param {boolean=} m
    */
   function inva($m = false) {
      $r = ($m === true) ? $this : new static($this);
      $r->s = -$r->s;
      return $r;
   }

   /**
    * returns multiplication inverse of number
    * @param {boolean=} m
    */
   function invm($m = false) {
      $r = (new static(1))->div($this);
      return static::mbnSetReturn($this, $r, $m);
   }

   /**
    * Returns lowest integer value not lower than number
    * @param {boolean=} m
    */
   function ceil($m = false) {
      $r = ($m === true) ? $this : new static($this);
      return $r->inva(true)->floor(true)->inva(true);
   }

   /**
    * Returns integer part of number
    * @param {boolean=} m
    */
   function int($m = false) {
   $r =($m === true) ? $this: new static($this);
   return($r->s >= 0) ?  $r->floor(true) :  $r->ceil(true);


   }

/**
 * returns if number equals to b, or if d is set, difference is lower or equals d
 * @param {*} b
 * @param {*} d
 */
function eq($b, $d = 0) {
   return $this->cmp($b, $d) === 0;
}

/**
 * returns minimum from value and b
 * @param {*} b
 * @param {boolean=} m
 */
function min($b, $m = false) {
   return static::mbnSetReturn($this, new Mbn((($this->cmp($b)) <= 0) ? $this : $b), $m);
}

/**
 * returns maximum from value and b
 * @param {*} b
 * @param {boolean=} m
 */
function max($b, $m = false) {
   return static::mbnSetReturn($this, new Mbn((($this->cmp($b)) >= 0) ? $this : $b), $m);
}

/**
 * calculates square root of number
 * @param {boolean=} m
 */
function sqrt($m = false) {
   $t = new static($this);
   $t->d[] = 0;
   $t->d[] = 0;
   $rb = new static($t);
   $r = new static($t);
   $mbn2 = new static('2');
   if ($r->s === -1) {
      throw new MbnErr();
   } else if ($r->s === 1) {
      do {
         $rb->set($r);
         $r->add($t->div($r), true)->div($mbn2, true);
      } while (!$rb->eq($r));
   }
   static::mbnRoundLast($r);
   return static::mbnSetReturn($this, $r, $m);
}

/**
 * returns sign from value
 * @param {boolean=} m
 */
function sgn($m = false) {
   return static::mbnSetReturn($this, new Mbn($this->s), $m);
}

}