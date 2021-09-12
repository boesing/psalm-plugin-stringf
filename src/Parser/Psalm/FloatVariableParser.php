<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\Psalm;

use LogicException;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Union;
use Webmozart\Assert\Assert;

use function assert;

final class FloatVariableParser
{
    private TFloat $variable;

    private function __construct(Union $variableType)
    {
        Assert::true($variableType->isFloat());
        $this->variable = $this->extract($variableType);
    }

    public static function stringify(Union $variableType): string
    {
        return (new self($variableType))->toString();
    }

    private function toString(): string
    {
        return (string) $this->toSingleFloat();
    }

    private function toSingleFloat(): float
    {
        if (! $this->variable instanceof TLiteralFloat) {
            throw new LogicException('Variable is not a literal float.');
        }

        return $this->variable->value;
    }

    private function extract(Union $variableType): TFloat
    {
        if ($variableType->isSingleFloatLiteral()) {
            return $variableType->getSingleFloatLiteral();
        }

        $atomicTypes = $variableType->getAtomicTypes();
        assert(isset($atomicTypes['float']));
        $type = $atomicTypes['float'];
        assert($type instanceof TFloat);

        return $type;
    }
}
