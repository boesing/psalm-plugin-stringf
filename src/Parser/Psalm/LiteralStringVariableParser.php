<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\Psalm;

use InvalidArgumentException;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Union;

use function sprintf;

final class LiteralStringVariableParser
{
    private function __construct()
    {
    }

    public static function parse(string $variableName, Union $variableType): string
    {
        if (! $variableType->isSingleStringLiteral()) {
            throw new InvalidArgumentException(sprintf(
                'Cannot parse literal string from variable "%s" of type: %s',
                $variableName,
                (string) $variableType
            ));
        }

        return self::string($variableType->getSingleStringLiteral());
    }

    public static function string(TLiteralString $literalString): string
    {
        return $literalString->value;
    }
}
