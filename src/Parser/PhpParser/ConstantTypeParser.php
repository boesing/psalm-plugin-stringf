<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use InvalidArgumentException;
use PhpParser\Node\Expr;
use Psalm\Type;
use Psalm\Type\Union;

use function sprintf;

final class ConstantTypeParser
{
    private const BOOLEAN_FALSE = ['false'];

    private Expr\ConstFetch $expr;

    private function __construct(Expr\ConstFetch $expr)
    {
        $this->expr = $expr;
    }

    public static function parse(Expr\ConstFetch $expr): Union
    {
        return (new self($expr))->toType();
    }

    private function toType(): Union
    {
        $resolvedName = $this->expr->name->getParts()[0] ?? null;
        if ($resolvedName === null) {
            throw new InvalidArgumentException('Provided constant does not contain resolved name.');
        }

        if ($resolvedName === 'false') {
            return Type::getFalse();
        }

        if ($resolvedName === 'true') {
            return Type::getTrue();
        }

        throw new InvalidArgumentException(sprintf('Cannot convert constant "%s" to a type.', $resolvedName));
    }
}
