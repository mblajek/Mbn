<?php /* Mbn v1.52.1 / 06.09.2025 | https://mbn.li | Copyright (c) 2016-2025 Mikołaj Błajek | https://mbn.li/LICENSE */
namespace Mbn;
class Mbn {
    //version of Mbn library
    protected static $MbnV = '1.52.1';
    //default precision
    protected static $MbnP = 2;
    //default separator
    protected static $MbnS = '.';
    //default truncation
    protected static $MbnT = false;
    //default evaluating
    protected static $MbnE = null;
    //default formatting
    protected static $MbnF = false;
    //default digit limit
    protected static $MbnL = 1000;
    private $d = [];
    private $s = 1;

    /**
     * fill options with default parameters and check
     * @param array $opt params by reference
     * @param int $MbnDP default precision
     * @param string $MbnDS default separator
     * @param boolean $MbnDT default truncation
     * @param boolean|null $MbnDE default evaluating
     * @param boolean $MbnDF default formatting
     * @param int $MbnDL default digit limit
     * @param string $fname name of function for exception
     * @return array checked ad filled class options
     * @throws MbnErr invalid options
     */
    private static function prepareOpt($opt, $MbnDP, $MbnDS, $MbnDT, $MbnDE, $MbnDF, $MbnDL, $fname) {
        $MbnP = $MbnDP;
        $MbnS = $MbnDS;
        $MbnT = $MbnDT;
        $MbnE = $MbnDE;
        $MbnF = $MbnDF;
        $MbnL = $MbnDL;
        if (array_key_exists('MbnP', $opt)) {
            $MbnP = $opt['MbnP'];
            if (!(is_int($MbnP) || is_float($MbnP)) || $MbnP < 0 || is_infinite($MbnP) || (float)(int)$MbnP !== (float)$MbnP) {
                throw new MbnErr($fname . 'invalid_precision', $MbnP);
            }
        }
        if (array_key_exists('MbnS', $opt)) {
            $MbnS = $opt['MbnS'];
            if ($MbnS !== '.' && $MbnS !== ',') {
                throw new MbnErr($fname . 'invalid_separator', $MbnS);
            }
        }
        if (array_key_exists('MbnT', $opt)) {
            $MbnT = $opt['MbnT'];
            if ($MbnT !== true && $MbnT !== false) {
                throw new MbnErr($fname . 'invalid_truncation', $MbnT);
            }
        }
        if (array_key_exists('MbnE', $opt)) {
            $MbnE = $opt['MbnE'];
            if ($MbnE !== true && $MbnE !== false && $MbnE !== null) {
                throw new MbnErr($fname . 'invalid_evaluation', $MbnE);
            }
        }
        if (array_key_exists('MbnF', $opt)) {
            $MbnF = $opt['MbnF'];
            if ($MbnF !== true && $MbnF !== false) {
                throw new MbnErr($fname . 'invalid_formatting', $MbnF);
            }
        }
        if (array_key_exists('MbnL', $opt)) {
            $MbnL = $opt['MbnL'];
            if (!(is_int($MbnL) || is_float($MbnL)) || $MbnL < 0 || is_infinite($MbnL) || (float)(int)$MbnL !== (float)$MbnL) {
                throw new MbnErr($fname . 'invalid_limit', $MbnL);
            }
        }
        return ['MbnV' => static::$MbnV, 'MbnP' => $MbnP, 'MbnS' => $MbnS, 'MbnT' => $MbnT, 'MbnE' => $MbnE, 'MbnF' => $MbnF, 'MbnL' => $MbnL];
    }

    /**
     * Private function, carries digits bigger than 9, and removes leading zeros
     * @throws MbnErr exceeded MbnL digits limit
     */
    private function mbnCarry() {
        $ad = &$this->d;
        $adlm1 = count($ad) - 1;
        $i = $adlm1;
        while ($i >= 0) {
            $adi = $ad[$i];
            while ($adi < 0) {
                $adi += 10;
                $ad[$i - 1]--;
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
            $adlm1--;
        }
        while ($adlm1 < static::$MbnP) {
            array_unshift($ad, 0);
            $adlm1++;
        }
        if ($adlm1 === static::$MbnP) {
            for ($i = 0; $i <= $adlm1 && $ad[$i] === 0; $i++) {
            }
            $this->s *= ($i <= $adlm1) ? 1 : 0;
        } elseif ($adlm1 - static::$MbnP > static::$MbnL) {
            throw new MbnErr('limit_exceeded', static::$MbnL);
        }

    }

    /**
     * Private function, if m is true, sets value to b and return value, otherwise returns b
     * @param Mbn $b
     * @param boolean $m
     * @return Mbn
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
     * @throws MbnErr exceeded MbnL digits limit
     */
    private function mbnRoundLast() {
        $ad = &$this->d;
        $adl = count($ad);
        if ($adl < 2) {
            array_unshift($ad, 0);
            $adl++;
        }
        if (array_pop($ad) >= 5) {
            $ad[$adl - 2]++;
        }
        $this->mbnCarry();
    }

    /**
     * Private function, sets value from string
     * @param string $ns String or formula
     * @param array|boolean|null $v Variables, default null
     * @throws MbnErr invalid format, calc error
     */
    private function fromString($ns, $v = null) {
        $np = [];
        preg_match('/^\\s*(=)?[\\s=]*([-+])?\\s*((?:[\\s\\S]*\\S)?)/', $ns, $np);
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
        $cs = 0;
        for ($i = 0; $i <= $l; $i++) {
            $c = ($i < $nl) ? (ord($n[$i]) - 48) : 0;
            if ($c >= 0 && $c <= 9) {
                if ($i <= $al + static::$MbnP) {
                    $this->d[] = $c;
                }
            } elseif (!(($i === $ln && $nl !== 1) || ($c === -16 && $i > $cs && ($i + 1) < $ln))) {
                if ($v === true || is_array($v) || ($v !== false && (static::$MbnE === true || (static::$MbnE === null && $np[1] === '=')))) {
                    $this->set(static::mbnCalc($ns, $v, null));
                    return;
                }
                throw new MbnErr('invalid_format', $ns);
            } else if ($c === -16) {
                $cs = $i + 1;
            }
        }
        $this->mbnRoundLast();
    }

    /**
     * Private function, sets value from number
     * @param int $ni
     */
    private function mbnFromInt($ni) {
        if ($ni > 0) {
            $ni = -$ni;
        } else {
            $this->s = ($ni < 0) ? -1 : 0;
        }
        do {
            $c = -($ni % 10);
            $ni = ($ni + $c) / 10;
            array_unshift($this->d, $c);
        } while ($ni !== 0);
        for ($n = 0; $n < static::$MbnP; $n++) {
            $this->d[] = 0;
        }
    }

    /**
     * Private function, sets value from number
     * @param float $nn
     * @throws MbnErr infinite value
     */
    private function mbnFromNumber($nn) {
        if (!is_finite($nn)) {
            if (is_nan($nn)) {
                throw new MbnErr('invalid_argument', $nn);
            }
            throw new MbnErr('limit_exceeded', static::$MbnL);
        }
        if ($nn < 0) {
            $nn = -$nn;
            $this->s = -1;
        }
        $ni = floor($nn);
        $nf = $nn - $ni;
        do {
            $c = round(fmod($ni, 10));
            $ni = round(($ni - $c) / 10);
            array_unshift($this->d, (int)$c);
        } while ($ni > 0);
        for ($n = 0; $n <= static::$MbnP; $n++) {
            $nf *= 10;
            $c = min((int)$nf, 9);
            $this->d[] = $c;
            $nf -= $c;
        }
        $this->mbnRoundLast();
    }

    /**
     * Private function, returns string value
     * @param int $p target precision
     * @param string $s target separator
     * @param boolean $t truncation
     * @param boolean $f formatting
     * @return string
     */
    private function mbnToString($p, $s, $t = false, $f = false) {
        $v = $this;
        $li = count($this->d) - static::$MbnP;
        if ($p < static::$MbnP) {
            $b = new static($this);
            $bl = count($b->d);
            if ($p < static::$MbnP - 1) {
                $b->d = array_slice($b->d, 0, $bl - static::$MbnP + $p + 1);
            }
            $b->mbnRoundLast();
            $bl = count($b->d);
            if ($bl - $p > $li) {
                $b->d = array_slice($b->d, $bl - $p - $li);
            }
            $v = $b;
        }
        $di = array_slice($v->d, 0, $li);
        if ($f) {
            $dl = count($di);
            for ($i = 0; 3 * $i < $dl - 3; $i++) {
                array_splice($di, -3 - 4 * $i, 0, ' ');
            }
        }
        $df = array_slice($v->d, $li);
        if ($p > static::$MbnP && !$t) {
            for ($i = 0; $i < $p - static::$MbnP; $i++) {
                $df[] = 0;
            }
        }
        if ($t) {
            for ($i = count($df) - 1; $i >= 0; $i--) {
                if ($df[$i] !== 0) {
                    break;
                }
            }
            $df = array_slice($df, 0, $i + 1);
        }
        $r = (($this->s < 0) ? '-' : '') . implode('', $di);
        if (!empty($df)) {
            $r .= $s . implode('', $df);
        }
        return $r;
    }

    /**
     * Constructor of Mbn object
     * @export
     * @constructor
     * @param mixed $n Value, default 0
     * @param array|boolean $v Array with vars for evaluation, default null
     * @throws MbnErr invalid argument, invalid format, calc error
     */
    public function __construct($n = 0, $v = null) {
        if (is_int($n)) {
            $this->mbnFromInt($n);
        } elseif (is_float($n)) {
            $this->mbnFromNumber($n);
        } elseif (is_string($n) || (is_object($n) && method_exists($n, '__toString'))) {
            if ($n instanceof static && $n::$MbnP === static::$MbnP) {
                $this->set($n);
                return;
            }
            $this->fromString((string)$n, $v);
        } elseif (is_bool($n) || $n === null) {
            $this->mbnFromNumber((int)$n);
        } else {
            throw new MbnErr('invalid_argument', $n);
        }
    }

    /**
     * Returns properties of Mbn class
     * @return array properties
     * @throws MbnErr invalid options
     */
    public static function prop() {
        return static::prepareOpt(['MbnV' => static::$MbnV, 'MbnP' => static::$MbnP, 'MbnS' => static::$MbnS, 'MbnT' => static::$MbnT,
           'MbnE' => static::$MbnE, 'MbnF' => static::$MbnF, 'MbnL' => static::$MbnL], 0, 0, 0, 0, 0, 0, 'extend.');
    }

    /**
     * Sets value from b
     * @param mixed $b
     * @return Mbn
     * @throws MbnErr invalid argument format
     */
    public function set($b) {
        if (!($b instanceof static && $b::$MbnP === static::$MbnP)) {
            $this->mbnSetReturn(new static($b), true);
        } else {
            $this->d = $b->d;
            $this->s = $b->s;
        }
        return $this;
    }

    /**
     * Returns string value
     * @return string
     */
    public function toString() {
        return $this->mbnToString(static::$MbnP, static::$MbnS, static::$MbnT, static::$MbnF);
    }

    /**
     * Returns reformatted string value
     * @param bool|array $opt thousand grouping or object with params, default true
     * @return string
     * @throws MbnErr invalid options
     */
    public function format($opt = true) {
        if (!is_array($opt)) {
            $opt = ['MbnF' => $opt];
        }
        $opt = static::prepareOpt($opt, static::$MbnP, static::$MbnS, static::$MbnT, static::$MbnE, static::$MbnF, static::$MbnL, 'format.');
        return $this->mbnToString($opt['MbnP'], $opt['MbnS'], $opt['MbnT'], $opt['MbnF']);
    }

    /**
     * Returns string value
     * @return string
     */
    public function __toString() {
        return $this->toString();
    }

    /**
     * Returns int value for MbnP = 0, otherwise float value
     * @return int|float
     */
    public function toNumber() {
        $v = $this->mbnToString(static::$MbnP, '.');
        return (static::$MbnP === 0) ? (int)$v : (float)$v;
    }

    /**
     * Compare value with b, a.cmp(b)<=0 means a<=b
     * @param mixed $b
     * @param mixed $d Maximum difference treated as equality, default 0
     * @return int 1 if value > b, -1 if value < b, otherwise 0
     * @throws MbnErr negative maximal difference
     * @throws MbnErr invalid argument format
     */
    public function cmp($b, $d = 0) {
        if ($d !== 0) {
            $dm = new static($d);
        }
        if (!($b instanceof static && $b::$MbnP === static::$MbnP)) {
            $b = new static($b);
        }
        if ($d === 0 || $dm->s === 0) {
            if ($this->s !== $b->s) {
                return ($this->s > $b->s) ? 1 : -1;
            }
            if ($this->s === 0) {
                return 0;
            }
            $bl = count($b->d);
            $ld = count($this->d) - $bl;
            if ($ld !== 0) {
                return ($ld > 0) ? $this->s : -$this->s;
            }
            for ($i = 0; $i < $bl; $i++) {
                if ($this->d[$i] !== $b->d[$i]) {
                    return ($this->d[$i] > $b->d[$i]) ? $this->s : -$this->s;
                }
            }
            return 0;
        }
        if ($dm->s === -1) {
            throw new MbnErr('cmp.negative_diff', $dm);
        }
        if ($this->sub($b)->abs()->cmp($dm) <= 0) {
            return 0;
        }
        return $this->cmp($b);
    }

    /**
     * Add b to value
     * @param mixed $b
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     * @throws MbnErr invalid argument format
     */
    public function add($b, $m = false) {
        if (!($b instanceof static && $b::$MbnP === static::$MbnP)) {
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
                foreach ($r->d as $i => &$di) {
                    if ($i >= $ld) {
                        $di += $b->d[$i - $ld];
                    }
                }
                unset($di);
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
     * Subtract b from value
     * @param mixed $b
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     * @throws MbnErr invalid argument format
     */
    public function sub($b, $m = false) {
        if (!($b instanceof static && $b::$MbnP === static::$MbnP)) {
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
                foreach ($r->d as $i => &$di) {
                    if ($i >= $ld) {
                        $di -= $b->d[$i - $ld];
                    }
                }
                unset($di);
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
     * @param mixed $b
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     * @throws MbnErr invalid argument format
     */
    public function mul($b, $m = false) {
        if (!($b instanceof static && $b::$MbnP === static::$MbnP)) {
            $b = new static($b);
        }
        $r = new static($b);
        $r->d = [];
        foreach ($this->d as $i => $tdi) {
            foreach ($b->d as $j => $bdi) {
                $ipj = $i + $j;
                $r->d[$ipj] = $tdi * $bdi + (isset($r->d[$ipj]) ? $r->d[$ipj] : 0);
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
     * @param mixed $b
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     * @throws MbnErr division by zero
     * @throws MbnErr invalid argument format
     */
    public function div($b, $m = false) {
        if (!($b instanceof static && $b::$MbnP === static::$MbnP)) {
            $b = new static($b);
        }
        if ($b->s === 0) {
            throw new MbnErr('div.zero_divisor');
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
                        $x[$i + $ld - 1]--;
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
     * @param mixed $b
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     * @throws MbnErr division by zero
     * @throws MbnErr invalid argument format
     */
    public function mod($b, $m = false) {
        $ba = ($b instanceof static && $b::$MbnP === static::$MbnP) ? $b->abs() : (new static($b))->abs();
        $r = $this->sub($this->div($ba)->intp()->mul($ba));
        if (($r->s * $this->s) === -1) {
            $r = $ba->sub($r->abs());
            $r->s = $this->s;
        }
        return $this->mbnSetReturn($r, $m);
    }

    /**
     * Split value to array of values, with same ratios as in given array, or to given number of parts, default 2
     * @param array|mixed $ar Ratios array or number of parts, default 2
     * @return Mbn[]
     * @throws MbnErr negative ratio, non-positive or not integer number of parts
     * @throws MbnErr invalid argument format
     */
    public function split($ar = 2) {
        $arr = [];
        if (!is_array($ar)) {
            $mbn1 = new static(1);
            $asum = new static($ar);
            if (!$asum->isInt()) {
                throw new MbnErr('split.invalid_part_count', $ar);
            }
            $n = (int)$asum->toNumber();
            for ($i = 0; $i < $n; $i++) {
                $arr[] = [$i, $mbn1];
            }
            $brr = $arr;
        } else {
            $mulp = (new static(10))->pow(static::$MbnP);
            $asum = new static(0);
            $n = count($ar);
            $sgns = [false, false, false];
            foreach ($ar as $k => &$v) {
                $ai = (new static($v))->mul($mulp);
                $sgns[$ai->s + 1] = true;
                $asum->add($ai, true);
                $arr[$k] = [$k, $ai];
            }
            unset($v);
            $brr = $arr;
            if ($sgns[0] && $sgns[2]) {
                usort($arr, static function ($a, $b) use ($asum) {
                    return $asum->s * $a[1]->cmp($b[1]);
                });
            }
        }
        if ($n <= 0) {
            throw new MbnErr('split.invalid_part_count', $n);
        }
        if ($asum->s === 0) {
            throw new MbnErr('split.zero_part_sum');
        }
        $a = new static($this);
        foreach ($arr as $va) {
            list($idx, $v) = $va;
            if ($v->s === 0) {
                $brr[$idx] = $v;
            } else {
                $b = $a->mul($v)->div($asum);
                $asum->sub($v, true);
                $a->sub($b, true);
                $brr[$idx] = $b;
            }
        }
        return $brr;
    }

    /**
     * Returns if the number is integer
     * @return boolean
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
     * Returns greatest integer value not greater than number
     * @param boolean $m Modify original variable, default false
     * @return Mbn
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
                $r->d[$ct - static::$MbnP - 1]++;
            }
            $r->mbnCarry();
        }
        return $r;
    }

    /**
     * Rounds number to closest integer value (half-up)
     * @param boolean $m Modify original variable, default false
     * @return Mbn
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
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     */
    public function abs($m = false) {
        $r = ($m === true) ? $this : new static($this);
        $r->s *= $r->s;
        return $r;
    }

    /**
     * Returns additive inverse of value
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     */
    public function inva($m = false) {
        $r = ($m === true) ? $this : new static($this);
        $r->s = -$r->s;
        return $r;
    }

    /**
     * Returns multiplicative inverse of value
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     * @throws MbnErr division by zero
     */
    public function invm($m = false) {
        $r = (new static(1))->div($this);
        return $this->mbnSetReturn($r, $m);
    }

    /**
     * Returns lowest integer value not lower than number
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     */
    public function ceil($m = false) {
        $r = ($m === true) ? $this : new static($this);
        return $r->inva(true)->floor(true)->inva(true);
    }

    /**
     * Returns integer part of number
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     */
    public function intp($m = false) {
        $r = ($m === true) ? $this : new static($this);
        return ($r->s >= 0) ? $r->floor(true) : $r->ceil(true);
    }

    /**
     * Returns if value equals b
     * @param mixed $b
     * @param mixed $d Maximum difference treated as equality, default 0
     * @return boolean
     * @throws MbnErr negative maximal difference
     * @throws MbnErr invalid argument format
     */
    public function eq($b, $d = 0) {
        return $this->cmp($b, $d) === 0;
    }

    /**
     * Returns minimum from value and b
     * @param mixed $b
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     * @throws MbnErr invalid argument format
     */
    public function min($b, $m = false) {
        return $this->mbnSetReturn(new static(($this->cmp($b) <= 0) ? $this : $b), $m);
    }

    /**
     * Returns maximum from value and b
     * @param mixed $b
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     * @throws MbnErr invalid argument format
     */
    public function max($b, $m = false) {
        return $this->mbnSetReturn(new static(($this->cmp($b) >= 0) ? $this : $b), $m);
    }

    /**
     * Returns square root of value
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     * @throws MbnErr square root of negative number
     */
    public function sqrt($m = false) {
        $t = $this->mul(100);
        $r = new static($t);

        if ($r->s === -1) {
            throw new MbnErr('sqrt.negative_value', $this);
        }
        if ($r->s === 1) {
            $mbn2 = new static(2);
            $diff = null;
            $cnt = 0;
            do {
                $lastDiff = $diff;
                $diff = $r->add(0)->sub($r->add($t->div($r), true)->div($mbn2, true), true);
                $cnt += ($lastDiff && $diff->s * $lastDiff->s === -1) ? 1 : 0;
            } while ($diff->s !== 0 && $cnt < 4);
            $r->mbnRoundLast();
        }
        return $this->mbnSetReturn($r, $m);
    }

    /**
     * Returns sign from value, 1 - positive, -1 - negative, otherwise 0
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     */
    public function sgn($m = false) {
        return $this->mbnSetReturn(new static($this->s), $m);
    }

    /**
     * Returns value to the power of b, b must be integer
     * @param mixed $b
     * @param boolean $m Modify original variable, default false
     * @return Mbn
     * @throws MbnErr not integer exponent
     * @throws MbnErr invalid argument format
     */
    public function pow($b, $m = false) {
        $n = new static($b);
        if (!$n->isInt()) {
            throw new MbnErr('pow.unsupported_exponent', $n);
        }
        $ns = $n->s;
        $n->s *= $n->s;
        $ni = (int)$n->toNumber();
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
            $ni = (int)($ni / 2);
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

    /**
     * Returns factorial, value must be non-negative integer
     * @param boolean= m Modify original variable, default false
     * @return Mbn
     * @throws {MbnErr} value is not non-negative integer
     */
    public function fact($m = false) {
        if (!$this->isInt() || $this->cmp(0) === -1) {
            throw new MbnErr('fact.invalid_value', $this);
        }
        $n = $this->sub(1);
        $r = $this->max(1);
        while ($n->s === 1) {
            $r->mul($n, true);
            $n->sub(1, true);
        }
        return $this->mbnSetReturn($r, $m);
    }

    protected static $fnReduce = ['set' => 0, 'abs' => 1, 'inva' => 1, 'invm' => 1, 'ceil' => 1, 'floor' => 1,
       'sqrt' => 1, 'round' => 1, 'sgn' => 1, 'intp' => 1, 'fact' => 1,
       'min' => 2, 'max' => 2, 'add' => 2, 'sub' => 2, 'mul' => 2, 'div' => 2, 'mod' => 2, 'pow' => 2];

    /**
     * Runs function on each element, returns:
     * single value for 2 argument function (arr[0].fn(arr[1]).fn(arr[2]), ..)
     * array of products for 1 argument function [arr[0].fn(), arr[1].fn(), ..]
     * array of products for 2 argument function and when b is same size array or single value
     * [arr[0].fn(b[0]), arr[1].fn(b[1]), ..] or [arr[0].fn(b), arr[1].fn(b), ..]
     * @param string $fn
     * @param array|mixed $arr first argument
     * @param array|mixed $b second argument, defauult null
     * @return Mbn|Mbn[]
     * @throws MbnErr invalid function name, wrong number of arguments, different array sizes
     * @throws MbnErr invalid argument format
     */
    public static function reduce($fn, $arr, $b = null) {
        $inv = false;
        if (!is_string($fn) || !isset(static::$fnReduce[$fn])) {
            throw new MbnErr('reduce.invalid_function', $fn);
        }
        if (!is_array($arr)) {
            if (!is_array($b)) {
                throw new MbnErr('reduce.no_array', $arr);
            }
            $inv = $b;
            $b = $arr;
            $arr = $inv;
        }
        $mode = static::$fnReduce[$fn];
        $bmode = (func_num_args() === 3) ? (is_array($b) ? 2 : 1) : 0;
        if ($mode !== 2 && $bmode !== 0) {
            throw new MbnErr('reduce.invalid_argument_count');
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
                if (count($arr) !== count($b)) {
                    throw new MbnErr('reduce.different_lengths', ['v' => count($arr), 'w' => count($b)], true);
                }
                throw new MbnErr('reduce.different_keys', ['v' => implode(',', array_keys($arr)), 'w' => implode(',', array_keys($b))], true);
            }
            $bv = ($bmode === 1) ? (new static($b)) : null;
            foreach ($arr as $k => &$v) {
                $e = new static($v);
                if ($bmode !== 0) {
                    $bi = ($bmode === 2) ? (new static($b[$k])) : $bv;
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
     * @param string|null $n Constant name, must start with letter or _
     * @param mixed v$ Constant value to set
     * @return Mbn|boolean
     * @throws MbnErr undefined constant, constant already set, incorrect name
     * @throws MbnErr invalid argument format
     */
    public static function def($n, $v = null) {
        $check = ($n === null);
        if (preg_match('/^[A-Za-z_]\\w*/', $check ? $v : $n) !== 1) {
            throw new MbnErr('def.invalid_name', $check ? $v : $n);
        }
        $res = new static();
        $mc = &static::$MbnConst;
        $mx = get_class($res);
        if ($check) {
            return (isset($mc[$mx][$v]) || isset($mc[''][$v]));
        }
        if ($v === null) {
            if (!isset($mc[$mx])) {
                $mc[$mx] = [];
            }
            if (!isset($mc[$mx][$n])) {
                if (!isset($mc[''][$n])) {
                    throw new MbnErr('def.undefined', $n);
                }
                $mc[$mx][$n] = ($n === 'eps') ? ((new static(10))->pow(-static::$MbnP)) : (new static($mc[''][$n]));
            }
            return $res->set($mc[$mx][$n]);
        }
        if (isset($mc[$mx][$n]) || isset($mc[''][$n])) {
            throw new MbnErr('def.already_set', ['v' => $n, 'w' => new static(isset($mc[$mx][$n]) ? $mc[$mx][$n] : $mc[''][$n])], true);
        }
        $mc[$mx][$n] = $res->set($v)->add(0);
        return $res;
    }

    protected static $fnEval = [
       'abs' => true, 'inva' => false, 'ceil' => true, 'floor' => true, 'fact' => true,
       'sqrt' => true, 'round' => true, 'sgn' => true, 'int' => 'intp', 'div_100' => 'div_100'];
    protected static $states = [
       'endBop' => ['bop', 'pc', 'fs'],
       'uopVal' => ['num', 'name', 'uop', 'po'],
       'fn' => ['po']
    ];
    protected static $ops = [
       '|' => [1, true, 'max'],
       '&' => [2, true, 'min'],
       '+' => [3, true, 'add'],
       '-' => [3, true, 'sub'],
       '*' => [4, true, 'mul'],
       '#' => [4, true, 'mod'],
       '/' => [4, true, 'div'],
       '^' => [5, false, 'pow'],
       '%' => [7, true, 'div_100'],
       '!' => [7, true, 'fact'],
       'inva' => [6, true, 'inva'],
       'fn' => [7, true]
    ];
    protected static $rxs = [
       'num' => ['rx' => '/^([0-9\., ]+)\\s*/', 'next' => 'endBop'],
       'name' => ['rx' => '/^([A-Za-z_]\\w*)\\s*/'],
       'fn' => ['next' => 'fn'],
       'vr' => ['next' => 'endBop'],
       'bop' => ['rx' => '/^([-+\\*\\/#^&|])\\s*/', 'next' => 'uopVal'],
       'uop' => ['rx' => '/^([-+])\s*/', 'next' => 'uopVal'],
       'po' => ['rx' => '/^(\\()\\s*/', 'next' => 'uopVal'],
       'pc' => ['rx' => '/^(\\))\\s*/', 'next' => 'endBop'],
       'fs' => ['rx' => '/^([%!])\\s*/', 'next' => 'endBop']
    ];

    /**
     * Evaluate expression
     * @param string $exp Expression
     * @param array|bool $vars Array with vars for evaluation, default null
     * @return Mbn
     * @throws MbnErr syntax error, operation error
     * @throws MbnErr invalid argument format
     */
    public static function calc($exp, $vars = null) {
        return new static($exp, is_array($vars) ? $vars : true);
    }

    /**
     * Check expression, get names of used vars
     * @param string $exp Expression
     * @param bool $omitOptional Omit vars available as constans or results
     * @return array|boolean
     */
    public static function check($exp, $omitOptional = false) {
        try {
            return array_keys(static::mbnCalc($exp, false, $omitOptional === true));
        } catch (MbnErr $e) {
            return false;
        }
    }

    /**
     * Evaluate expression
     * @param string $exp Expression
     * @param array|boolean|null $vars Array with vars for evaluation
     * @param bool|null $checkOmitOptional Omit vars available as constans or results
     * @return Mbn|string[]
     * @throws MbnErr syntax error, operation error
     * @throws MbnErr invalid argument format
     */
    private static function mbnCalc($exp, $vars, $checkOmitOptional) {
        $expr = (string)$exp;
        $varsUsed = [];
        if (!is_array($vars)) {
            $vars = [];
        }
        $results = ['r0' => new static()];
        while (preg_match('/{+/', $expr, $mtch) === 1) {
            $mtch = $mtch[0];
            $comStart = strpos($expr, $mtch);
            $comEnd = strpos($expr, str_replace('{', '}', $mtch), $comStart);
            $expr = substr($expr, 0, $comStart) . (($comEnd === false)
                  ? '' : ("\t" . substr($expr, $comEnd + strlen($mtch))));
        }
        foreach (explode(';', $expr) as $i => $expr) {
            $expr = preg_replace('/^[\\s=]+/', '', $expr);
            $results['r' . ($i + 1)] = $results['r0'] = (($expr === "") ? $results['r0']
               : self::mbnCalcSingle($expr, $vars, $results, $varsUsed, $checkOmitOptional));
            for ($j = 0; $j <= $i; $j++) {
                $results['r0' . ($j + 1)] = $results['r' . ($i - $j + 1)];
            }
        }
        return ($checkOmitOptional === null) ? $results['r0'] : $varsUsed;
    }

    /**
     * Evaluate expression
     * @param string $expr Expression
     * @param array $vars Array with vars for evaluation
     * @param array $results Array with vars for evaluation
     * @param bool|null $checkOmitOptional Omit vars available as constans or results
     * @return Mbn|string[]
     * @throws MbnErr syntax error, operation error
     * @throws MbnErr invalid argument format
     */
    private static function mbnCalcSingle($expr, $vars, $results, &$varsUsed, $checkOmitOptional) {
        $state = 'uopVal';
        $rpns = [];
        $rpno = [];
        $t = null;

        while ($expr !== '') {
            $mtch = [];
            foreach (static::$states[$state] as $t) {
                if (preg_match(static::$rxs[$t]['rx'], $expr, $mtch)) {
                    break;
                }
            }
            if (count($mtch)) {
                $tok = $mtch[1];
                $expr = substr($expr, strlen($mtch[0]));
                $expr = ($expr === false) ? '' : $expr;
            } elseif ($state === 'endBop' && !preg_match(static::$rxs['num']['rx'], $expr)) {
                $tok = '*';
                $t = 'bop';
            } else {
                throw new MbnErr('calc.unexpected', $expr);
            }
            switch ($t) {
                case 'num':
                    $rpns[] = new static($tok, false);
                    break;
                case 'name':
                    $t = 'vr';
                    if (isset(static::$fnEval[$tok]) && static::$fnEval[$tok] !== false) {
                        $t = 'fn';
                        $rpno [] = array_merge(static::$ops['fn'], [$tok]);
                    } elseif ($checkOmitOptional !== null) {
                        if (empty($varsUsed[$tok]) && (!$checkOmitOptional || (!array_key_exists($tok, $results) && !Mbn::def(null, $tok)))) {
                            $varsUsed[$tok] = true;
                        }
                    } elseif (array_key_exists($tok, $vars)) {
                        if (!isset($varsUsed[$tok])) {
                            $varsUsed[$tok] = new static($vars[$tok]);
                        }
                        $rpns [] = new static($varsUsed[$tok]);
                    } elseif (array_key_exists($tok, $results)) {
                        $rpns [] = new static($results[$tok]);
                    } elseif (static::def(null, $tok)) {
                        $rpns [] = static::def($tok);
                    } else {
                        throw new MbnErr('calc.undefined', $tok);
                    }
                    break;
                case 'fs':
                case 'bop':
                    $op = static::$ops[$tok];
                    while (($rolp = array_pop($rpno)) !== null) {
                        if ($rolp === '(' || ($rolp[0] <= $op[0] - ($op[1] ? 1 : 0))) {
                            $rpno[] = $rolp;
                            break;
                        }
                        $rpns[] = $rolp[2];
                    }
                    $rpno[] = $op;
                    break;
                case 'uop':
                    if ($tok === '-') {
                        $rpno [] = static::$ops['inva'];
                    }
                    break;
                case 'po':
                    $rpno [] = $tok;
                    break;
                case 'pc':
                    while (($rolp = array_pop($rpno)) !== '(') {
                        if ($rolp === null) {
                            throw new MbnErr('calc.unexpected', ')');
                        }
                        $rpns[] = $rolp[2];
                    }
                    break;
                default:
            }
            $state = static::$rxs[$t]['next'];
        }
        while (($rolp = array_pop($rpno)) !== null) {
            if ($rolp === '(') {
                throw new MbnErr('calc.unexpected', '(');
            }
            $rpns[] = $rolp[2];
        }
        if ($state !== 'endBop') {
            throw new MbnErr('calc.unexpected', 'END');
        }

        if ($checkOmitOptional !== null) {
            return $varsUsed;
        }

        $rpn = [];
        foreach ($rpns as &$tn) {
            if ($tn instanceof static && $tn::$MbnP === static::$MbnP) {
                $rpn[] = &$tn;
            } elseif (isset(static::$fnEval[$tn])) {
                if (is_string(static::$fnEval[$tn])) {
                    $tn = static::$fnEval[$tn];
                    if (strpos($tn, '_') !== false) {
                        $tn = explode('_', $tn);
                        $rpn[count($rpn) - 1]->{$tn[0]}($tn[1], true);
                        continue;
                    }
                }
                $rpn[count($rpn) - 1]->{$tn}(true);
            } else {
                $rpn[count($rpn) - 2]->{$tn}(array_pop($rpn), true);
            }
        }
        return $rpn[0];
    }
}
