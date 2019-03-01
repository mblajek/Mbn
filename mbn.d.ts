// TypeScript type definitions for Mbn

type MbnArg = number | boolean | null | undefined | string | Mbn;

type MbnOneArgFn = "set" | "abs" | "inva" | "invm" | "ceil" | "floor" | "sqrt" | "round" | "sgn" | "intp";

type MbnTwoArgFn = "min" | "max" | "add" | "sub" | "mul" | "div" | "mod" | "pow";

type MbnPT = number;

type MbnST = "." | ",";

type MbnTT = boolean;

type MbnET = boolean | null;

type MbnFT = boolean;

interface MbnDispOpt {
    MbnP?: MbnPT,
    MbnS?: MbnST,
    MbnT?: MbnTT,
    MbnF?: MbnFT
}

interface MbnOpt extends MbnDispOpt{
    MbnE?: MbnET
}

interface Mbn {
    set(b: MbnArg): Mbn;

    toString(): string;

    format(f?: boolean | MbnDispOpt): string;

    toNumber(): number;

    cmp(b: MbnArg, d?: MbnArg): -1 | 0 | 1;

    add(b: MbnArg, m?: boolean): Mbn;

    sub(b: MbnArg, m?: boolean): Mbn;

    mul(b: MbnArg, m?: boolean): Mbn;

    div(b: MbnArg, m?: boolean): Mbn;

    mod(b: MbnArg, m?: boolean): Mbn;

    split(ar?: MbnArg[] | MbnArg): Mbn[];

    isInt(): boolean;

    floor(m?: boolean): Mbn;

    round(m?: boolean): Mbn;

    abs(m?: boolean): Mbn;

    inva(m?: boolean): Mbn;

    invm(m?: boolean): Mbn;

    ceil(m?: boolean): Mbn;

    intp(m?: boolean): Mbn;

    eq(b: MbnArg, d?: MbnArg): boolean;

    min(b: MbnArg, m?: boolean): Mbn;

    max(b: MbnArg, m?: boolean): Mbn;

    sqrt(m?: boolean): Mbn;

    sgn(m?: boolean): Mbn;

    pow(b: MbnArg, m?: boolean): Mbn;

    fact(m? : boolean): Mbn;
}


interface MbnConstructor {
    new(n?: MbnArg, v?: boolean | Record<string, MbnArg>): Mbn;

    reduce(fn: MbnOneArgFn, arr: MbnArg[]): Mbn[];

    reduce(fn: MbnTwoArgFn, arr: MbnArg[]): Mbn;

    reduce(fn: MbnTwoArgFn, arr: MbnArg[], b: MbnArg | MbnArg[]): Mbn[];

    reduce(fn: MbnTwoArgFn, arr: MbnArg, b: MbnArg[]): Mbn[];

    def(n: string | null, v?: string): Mbn;

    calc(exp: string, vars?: Record<string, MbnArg> ): Mbn;

    prototype: Mbn;
}

interface MbnConstructorParent extends MbnConstructor {
    extend(opt?: number | MbnOpt): MbnConstructor;
}

export var Mbn: MbnConstructorParent;
