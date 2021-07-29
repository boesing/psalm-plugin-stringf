<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\Psalm;

use InvalidArgumentException;
use Psalm\Type\Union;

use function count;
use function reset;
use function sprintf;

final class FloatVariableParser
{
    private Union $variable;

    private function __construct(string $variableName, Union $variableType)
    {
        $this->variable = $variableType;
        if (! $variableType->isFloat()) {
            throw new InvalidArgumentException(sprintf(
                'Cannot parse float from variable "%s" of type: %s',
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
        return (string) $this->toSingleFloat();
    }

    private function toSingleFloat(): float
    {
        $floats = $this->variable->getLiteralFloats();
        if (count($floats) === 1) {
            return reset($floats)->value;
        }

        throw new InvalidArgumentException('Cannot parse on-single float value');
    }
}
