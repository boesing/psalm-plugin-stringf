<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\PrettyPrinter\Standard;
use PhpParser\PrettyPrinterAbstract;

use function assert;
use function substr;

final class ArgumentValueParser
{
    private Expr $expr;
    private static ?PrettyPrinterAbstract $prettyPrinter;

    private function __construct(Expr $expr)
    {
        $this->expr            = $expr;
        self::$prettyPrinter ??= new Standard();
    }

    public static function create(Arg $templateArgument): self
    {
        return new self($templateArgument->value);
    }

    public function toString(): string
    {
        assert(self::$prettyPrinter !== null);

        return substr(self::$prettyPrinter->prettyPrintExpr($this->expr), 1, -1);
    }
}
