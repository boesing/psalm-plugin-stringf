<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\Psalm;

use InvalidArgumentException;
use Psalm\Type\Union;

use function sprintf;

final class LiteralIntVariableParser
{
    private Union $variable;

    private function __construct(string $variableName, Union $variableType)
    {
        $this->variable = $variableType;
        if (! $variableType->isSingleIntLiteral()) {
            throw new InvalidArgumentException(sprintf(
                'Cannot parse literal int from variable "%s" of type: %s',
                $variableName,
                (string) $variableType
            ));
        }
    }

    public static function stringify(string $variableName, Union $variableType): string
    {
        return (new self($variableName, $variableType))->toString();
    }

    private function toString(): string
    {
        $literal = $this->variable->getSingleIntLiteral();

        return (string) $literal->value;
    }
}
