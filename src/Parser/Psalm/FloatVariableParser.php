<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\Psalm;

use InvalidArgumentException;
use LogicException;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Union;
use Webmozart\Assert\Assert;

use function assert;
use function count;
use function reset;

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
        if (self::isSingleFloatLiteral($variableType)) {
            return self::getSingleFloatLiteral($variableType);
        }

        $atomicTypes = $variableType->getAtomicTypes();
        assert(isset($atomicTypes['float']));
        $type = $atomicTypes['float'];
        assert($type instanceof TFloat);

        return $type;
    }

    /**
     * Can be removed when https://github.com/vimeo/psalm/pull/6252 will be merged.
     */
    public static function isSingleFloatLiteral(Union $type): bool
    {
        return $type->getLiteralFloats() !== [] && $type->isSingle();
    }

    /**
     * Can be removed when https://github.com/vimeo/psalm/pull/6252 will be merged.
     */
    public static function getSingleFloatLiteral(Union $variableType): TLiteralFloat
    {
        if (! self::isSingleFloatLiteral($variableType)) {
            throw new InvalidArgumentException('Not a single float literal');
        }

        $literals = $variableType->getLiteralFloats();
        assert(count($literals) > 0);

        return reset($literals);
    }
}
