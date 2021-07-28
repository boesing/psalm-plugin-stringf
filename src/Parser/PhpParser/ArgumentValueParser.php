<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use InvalidArgumentException;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\String_;
use Psalm\Context;

use function sprintf;

final class ArgumentValueParser
{
    private const UNPARSABLE_ARGUMENT_VALUE = <<<'EOT'
    Provided argument contains an unparsable value of type "%s".
    EOT;


    private Expr $expr;
    private Context $context;

    private function __construct(Expr $expr, Context $context)
    {
        $this->expr    = $expr;
        $this->context = $context;
    }

    public static function create(Expr $expr, Context $context): self
    {
        return new self($expr, $context);
    }

    public function toString(): string
    {
        return $this->parse($this->expr, $this->context);
    }

    private function parse(Expr $expr, Context $context): string
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        if ($expr instanceof Expr\Variable) {
            return LiteralStringVariableInContextParser::parse($expr, $context);
        }

        throw new InvalidArgumentException(sprintf(self::UNPARSABLE_ARGUMENT_VALUE, $expr->getType()));
    }
}
