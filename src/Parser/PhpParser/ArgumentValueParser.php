<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use InvalidArgumentException;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\DNumber;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use Psalm\Context;
use Psalm\Type;
use Psalm\Type\Union;

use function sprintf;

final class ArgumentValueParser
{
    private const UNPARSABLE_ARGUMENT_VALUE = <<<'EOT'
    Provided argument contains an unparsable value of type "%s".
    EOT;


    private Expr $expr;
    private Context $context;

    private function __construct(Expr $expr, Context $context)
    {
        $this->expr    = $expr;
        $this->context = $context;
    }

    public static function create(Expr $expr, Context $context): self
    {
        return new self($expr, $context);
    }

    public function toString(): string
    {
        return $this->parse($this->expr, $this->context, false);
    }

    public function toType(): Union
    {
        if ($this->expr instanceof String_) {
            return new Union([Type::getString($this->expr->value)->getSingleStringLiteral()]);
        }

        if ($this->expr instanceof Expr\Variable) {
            return VariableTypeParser::parse($this->expr, $this->context);
        }

        if ($this->expr instanceof DNumber) {
            return Type::getFloat($this->expr->value);
        }

        if ($this->expr instanceof LNumber) {
            return Type::getInt(false, $this->expr->value);
        }

        if ($this->expr instanceof Expr\ConstFetch) {
            return ConstantTypeParser::parse($this->expr);
        }

        throw new InvalidArgumentException(sprintf(
            'Cannot detect type from expression of type "%s"',
            $this->expr->getType()
        ));
    }

    /**
     * Should return a string value which would also be used when casting the value to string.
     */
    public function stringify(): string
    {
        return $this->parse($this->expr, $this->context, true);
    }

    private function parse(Expr $expr, Context $context, bool $cast): string
    {
        if ($expr instanceof String_) {
            return $expr->value;
        }

        if ($expr instanceof Expr\Variable) {
            return $cast
                ? StringableVariableInContextParser::parse($expr, $context)
                : LiteralStringVariableInContextParser::parse($expr, $context);
        }

        throw new InvalidArgumentException(sprintf(self::UNPARSABLE_ARGUMENT_VALUE, $expr->getType()));
    }
}
