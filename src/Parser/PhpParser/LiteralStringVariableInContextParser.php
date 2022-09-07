<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use Boesing\PsalmPluginStringf\Parser\Psalm\LiteralStringVariableParser;
use InvalidArgumentException;
use PhpParser\Node\Expr;
use Psalm\Context;

use function sprintf;

final class LiteralStringVariableInContextParser
{
    private Expr\Variable $variable;

    private function __construct(Expr\Variable $variable)
    {
        $this->variable = $variable;
    }

    public static function parse(Expr\Variable $variable, Context $context): string
    {
        return (new self($variable))->toString($context);
    }

    private function toString(Context $context): string
    {
        $name = $this->variable->name;
        if ($name instanceof Expr) {
            return ArgumentValueParser::create($name, $context)->toString();
        }

        $variableName = sprintf('$%s', $name);
        if (! isset($context->vars_in_scope[$variableName])) {
            throw new InvalidArgumentException(sprintf('Variable "%s" is not known in scope.', $variableName));
        }

        return LiteralStringVariableParser::parse(
            $variableName,
            $context->vars_in_scope[$variableName],
        );
    }
}
