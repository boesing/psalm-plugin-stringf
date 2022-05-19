<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use InvalidArgumentException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use Psalm\Context;

use function assert;
use function get_class;
use function sprintf;

final class VariableFromConstInContextParser
{
    /** @var ClassConstFetch|ConstFetch */
    private Expr $expr;

    private Context $context;

    /**
     * @param ClassConstFetch|ConstFetch $expr
     */
    private function __construct(Expr $expr, Context $context)
    {
        $this->expr    = $expr;
        $this->context = $context;
    }

    /**
     * @param ClassConstFetch|ConstFetch $expr
     */
    public static function parse(
        Expr $expr,
        Context $context
    ): string {
        return (new self($expr, $context))->toString();
    }

    private function toString(): string
    {
        if ($this->expr instanceof ClassConstFetch) {
            return $this->parseClassConstant($this->expr, $this->context);
        }

        return $this->parseConstant($this->expr, $this->context);
    }

    private function parseClassConstant(ClassConstFetch $expr, Context $context): string
    {
        if (! $expr->class instanceof Name) {
            throw new InvalidArgumentException(sprintf(
                'Expected an instance of "%s" as ClassConstFetch::$class property, got: %s',
                Name::class,
                get_class($expr->class)
            ));
        }

        if (! $expr->name instanceof Identifier) {
            throw new InvalidArgumentException(sprintf(
                'Expected an instance of "%s" as ClassConstFetch::$name property, got: %s',
                Identifier::class,
                get_class($expr->name)
            ));
        }

        $className = $expr->class->toString();

        if ($expr->class->toLowerString() === 'self') {
            assert($context->self !== null);
            $className = $context->self;
        }

        $constant = sprintf('%s::%s', $className, $expr->name->toString());

        if (isset($context->vars_in_scope[$constant])) {
            return $context->vars_in_scope[$constant]->getId();
        }

        throw new InvalidArgumentException(sprintf('Could not find class constant "%s" in scope.', $constant));
    }

    private function parseConstant(ConstFetch $expr, Context $context): string
    {
        $constant = (string) $expr->name;

        if (isset($context->vars_in_scope[$constant])) {
            return $context->vars_in_scope[$constant]->getId();
        }

        throw new InvalidArgumentException(sprintf('Could not find constant "%s" in scope.', $constant));
    }
}
