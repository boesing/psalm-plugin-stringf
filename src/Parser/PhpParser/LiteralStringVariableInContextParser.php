<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use Boesing\PsalmPluginStringf\Parser\Psalm\LiteralStringVariableParser;
use InvalidArgumentException;
use PhpParser\Node\Expr;
use Psalm\Context;
use Psalm\StatementsSource;

use function sprintf;

final class LiteralStringVariableInContextParser
{
    private Expr\Variable $variable;

    private function __construct(Expr\Variable $variable)
    {
        $this->variable = $variable;
    }

    public static function parse(Expr\Variable $variable, Context $context, StatementsSource $statementsSource): string
    {
        return (new self($variable))->toString($context, $statementsSource);
    }

    private function toString(Context $context, StatementsSource $statementsSource): string
    {
        $name = $this->variable->name;
        if ($name instanceof Expr) {
            return ArgumentValueParser::create($name, $context, $statementsSource)->toString();
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
