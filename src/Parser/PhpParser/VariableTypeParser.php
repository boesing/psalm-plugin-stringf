<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use InvalidArgumentException;
use PhpParser\Node\Expr;
use Psalm\Context;
use Psalm\Type\Union;

use function sprintf;

final class VariableTypeParser
{
    private Expr\Variable $expr;

    private function __construct(Expr\Variable $expr)
    {
        $this->expr = $expr;
    }

    public static function parse(Expr\Variable $expr, Context $context): Union
    {
        return (new self($expr))->fromContext($context);
    }

    private function fromContext(Context $context): Union
    {
        $name = $this->expr->name;
        if ($name instanceof Expr) {
            throw new InvalidArgumentException('Cannot detect type from expression variable');
        }

        $variableName = sprintf('$%s', $name);
        $variable     = $context->vars_in_scope[$variableName] ?? null;
        if ($variable === null) {
            throw new InvalidArgumentException(sprintf(
                'Variable "%s" is not available in provided scope.',
                $variableName
            ));
        }

        return $variable;
    }
}
