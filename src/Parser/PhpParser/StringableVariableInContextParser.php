<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use Boesing\PsalmPluginStringf\Parser\Psalm\FloatVariableParser;
use Boesing\PsalmPluginStringf\Parser\Psalm\LiteralIntVariableParser;
use Boesing\PsalmPluginStringf\Parser\Psalm\LiteralStringVariableParser;
use InvalidArgumentException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Variable;
use Psalm\Context;

use function sprintf;

final class StringableVariableInContextParser
{
    private Variable $variable;

    private function __construct(Variable $variable)
    {
        $this->variable = $variable;
    }

    public static function parse(Variable $variable, Context $context): string
    {
        return (new self($variable))->toString($context);
    }

    private function toString(Context $context): string
    {
        $name = $this->variable->name;
        if ($name instanceof Expr) {
            return ArgumentValueParser::create($name, $context)->stringify();
        }

        $variableName = sprintf('$%s', $name);
        if (! isset($context->vars_in_scope[$variableName])) {
            throw new InvalidArgumentException(sprintf('Variable "%s" is not known in scope.', $variableName));
        }

        $variable = $context->vars_in_scope[$variableName];
        if ($variable->isSingleStringLiteral()) {
            return LiteralStringVariableParser::parse($variableName, $variable);
        }

        if ($variable->isSingleIntLiteral()) {
            return LiteralIntVariableParser::stringify($variableName, $variable);
        }

        if ($variable->isSingleFloatLiteral()) {
            return FloatVariableParser::stringify($variable);
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot extract string from variable "%s" with type "%s"',
            $variableName,
            (string) $variable
        ));
    }
}
