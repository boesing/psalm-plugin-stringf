<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use InvalidArgumentException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use Psalm\Context;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\StatementsSource;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;
use ReflectionClass;
use SplObjectStorage;

use function assert;
use function get_class;
use function sprintf;

final class VariableFromConstInContextParser
{
    /** @var ClassConstFetch|ConstFetch */
    private Expr $expr;

    private Context $context;

    private StatementsSource $statementsSource;

    /**
     * @param ClassConstFetch|ConstFetch $expr
     */
    private function __construct(Expr $expr, Context $context, StatementsSource $statementsSource)
    {
        $this->expr             = $expr;
        $this->context          = $context;
        $this->statementsSource = $statementsSource;
    }

    /**
     * @param ClassConstFetch|ConstFetch $expr
     */
    public static function parse(
        Expr $expr,
        Context $context,
        StatementsSource $statementsSource
    ): string {
        return (new self($expr, $context, $statementsSource))->toString();
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
                get_class($expr->class),
            ));
        }

        if (! $expr->name instanceof Identifier) {
            throw new InvalidArgumentException(sprintf(
                'Expected an instance of "%s" as ClassConstFetch::$name property, got: %s',
                Identifier::class,
                get_class($expr->name),
            ));
        }

        $className = $expr->class->toString();

        if ($expr->class->toLowerString() === 'self') {
            assert($context->self !== null);
            $className = $context->self;
        }

        $constant = sprintf('%s::%s', $className, $expr->name->toString());

        if ($context->hasVariable($constant)) {
            return $this->extractMostAccurateStringRepresentationOfType(
                $context->vars_in_scope[$constant],
            );
        }

        throw new InvalidArgumentException(sprintf('Could not find class constant "%s" in scope.', $constant));
    }

    private function parseConstant(ConstFetch $expr, Context $context): string
    {
        $constant = (string) $expr->name;

        if ($context->hasVariable($constant)) {
            return $this->extractMostAccurateStringRepresentationOfType(
                $context->vars_in_scope[$constant],
            );
        }

        throw new InvalidArgumentException(sprintf('Could not find constant "%s" in scope.', $constant));
    }

    /**
     * @param ClassConstFetch|ConstFetch $expr
     */
    private function extractMostAccurateStringRepresentationOfType(
        Union $type
    ): string {
        if ($type->isSingleStringLiteral()) {
            return $type->getSingleStringLiteral()->value;
        }

        if ($type->isSingleFloatLiteral()) {
            return (string) $type->getSingleFloatLiteral()->value;
        }

        if ($type->isSingleIntLiteral()) {
            return (string) $type->getSingleIntLiteral()->value;
        }

        $nodeTypeProvider = $this->statementsSource->getNodeTypeProvider();
        if ($nodeTypeProvider instanceof NodeDataProvider) {
            return $this->extractMostAccurateStringRepresentationOfTypeFromNodeDataProvider(
                $nodeTypeProvider,
                $type,
            );
        }

        throw $this->createInvalidArgumentException($type);
    }

    /**
     * Method uses reflection to hijack the native string which was inferred by php-parser. By doing this, we can
     * bypass the `maxStringLength` psalm setting.
     */
    private function extractMostAccurateStringRepresentationOfTypeFromNodeDataProvider(
        NodeDataProvider $nodeDataProvider,
        Union $type
    ): string {
        $reflectionClass = new ReflectionClass($nodeDataProvider);
        if (! $reflectionClass->hasProperty('node_types')) {
            throw $this->createInvalidArgumentException($type);
        }

        $nodeTypesProperty = $reflectionClass->getProperty('node_types');
        $nodeTypesProperty->setAccessible(true);
        $nodeTypes = $nodeTypesProperty->getValue($nodeDataProvider);
        if (! $nodeTypes instanceof SplObjectStorage) {
            throw $this->createInvalidArgumentException($type);
        }

        foreach ($nodeTypes as $phpParserType) {
            if (! $phpParserType instanceof String_) {
                continue;
            }

            $psalmType = $nodeTypes->offsetGet($phpParserType);
            if (! $psalmType instanceof TypeNode) {
                continue;
            }

            if ($psalmType !== $type) {
                continue;
            }

            return $phpParserType->value;
        }

        throw $this->createInvalidArgumentException($type);
    }

    private function createInvalidArgumentException(Union $type): InvalidArgumentException
    {
        return new InvalidArgumentException(sprintf(
            'Unable to parse a string representation of the provided type: %s',
            $type->getId(),
        ));
    }
}
