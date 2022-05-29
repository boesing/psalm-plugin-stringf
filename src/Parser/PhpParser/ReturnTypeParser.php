<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use InvalidArgumentException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use Psalm\Context;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\StatementsSource;
use Psalm\Storage\MethodStorage;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use Webmozart\Assert\Assert;

use function get_class;
use function is_string;
use function sprintf;

final class ReturnTypeParser
{
    private StatementsSource $statementsSource;

    private Context $context;

    /** @var FuncCall|StaticCall|MethodCall */
    private Expr $value;

    /**
     * @param FuncCall|StaticCall|MethodCall $value
     */
    private function __construct(StatementsSource $statementsSource, Context $context, Expr $value)
    {
        $this->statementsSource = $statementsSource;
        $this->context          = $context;
        $this->value            = $value;
    }

    /**
     * @param FuncCall|StaticCall|MethodCall $value
     */
    public static function create(
        StatementsSource $statementsSource,
        Context $context,
        Expr $value
    ): self {
        return new self($statementsSource, $context, $value);
    }

    public function toType(): Union
    {
        if ($this->value instanceof FuncCall) {
            return $this->detectTypeFromFunctionCall($this->value);
        }

        if ($this->value instanceof StaticCall) {
            return $this->detectTypeFromStaticMethodCall($this->value);
        }

        return $this->detectTypeFromMethodCall($this->value);
    }

    private function detectTypeFromFunctionCall(FuncCall $value): Union
    {
        $name = $value->name;
        Assert::isInstanceOf($name, Name::class, 'Could not detect function name.');

        $source = $this->statementsSource;
        if (! $source instanceof StatementsAnalyzer) {
            throw new InvalidArgumentException(sprintf(
                'Invalid statements source given. Can only handle %s at this time.',
                StatementsAnalyzer::class
            ));
        }

        $function_id = $name->toLowerString();

        /** @psalm-suppress InternalMethod I don't see any other way of detecting the return type of a function (yet) */
        $analyzer = $source->getFunctionAnalyzer($function_id);
        if ($analyzer === null) {
            throw new InvalidArgumentException(sprintf(
                'Could not detect function analyzer for `function_id`: %s',
                $function_id
            ));
        }

        /** @psalm-suppress InternalMethod I don't see any other way of detecting the return type of a function (yet) */
        $storage              = $analyzer->getFunctionLikeStorage($source);
        $declared_return_type = $storage->return_type;
        if ($declared_return_type === null) {
            throw new InvalidArgumentException(sprintf('Could not detect return type for `function_id`: %s', $function_id));
        }

        return $declared_return_type;
    }

    private function detectTypeFromStaticMethodCall(StaticCall $value): Union
    {
        $class = $value->class;
        if (! $class instanceof Name) {
            throw new InvalidArgumentException(sprintf(
                'Expected `class` to be instance of `%s`: `%s` given.',
                Name::class,
                get_class($class)
            ));
        }

        $method = $value->name;
        if (! $method instanceof Identifier) {
            throw new InvalidArgumentException(sprintf(
                'Expected `name` to be instance of `%s`: `%s` given.',
                Identifier::class,
                get_class($method)
            ));
        }

        /** @var class-string $className */
        $className = $class->toString();

        return $this->detectTypeFromMethodCallOfClass($method, $className);
    }

    private function detectTypeFromMethodCall(MethodCall $value): Union
    {
        $class  = $this->detectClass($value->var);
        $method = $value->name;

        if (! $method instanceof Identifier) {
            throw new InvalidArgumentException(sprintf(
                'Expected `name` to be instance of `%s`: `%s` given.',
                Identifier::class,
                get_class($method)
            ));
        }

        return $this->detectTypeFromMethodCallOfClass($method, $class);
    }

    /**
     * @param class-string $class
     */
    private function detectTypeFromMethodCallOfClass(Identifier $method, string $class): Union
    {
        /** @psalm-suppress InternalMethod We need the class like storage to detect the return type of the method call of a specific class. */
        $classLikeStorage = $this->statementsSource->getCodebase()->classlike_storage_provider->get($class);
        /** @var lowercase-string $lowercasedMethodName */
        $lowercasedMethodName = $method->toLowerString();
        $methodStorage        = $classLikeStorage->methods[$lowercasedMethodName] ?? null;
        if (! $methodStorage instanceof MethodStorage) {
            throw new InvalidArgumentException(
                'Provided static call does contain a method call to a method which can not be found within the methods parsed from the class.'
            );
        }

        $returnType = $methodStorage->return_type;
        if ($returnType === null) {
            throw new InvalidArgumentException(sprintf('Could not detect return type for method call: %s::%s', $class, $method->toString()));
        }

        return $returnType;
    }

    /** @return class-string */
    private function detectClass(Expr $var): string
    {
        if ($var instanceof Expr\New_) {
            $class = $var->class;
            if (! $class instanceof Name) {
                throw new InvalidArgumentException(sprintf(
                    'Expected `class` to be instance of `%s`: `%s` given.',
                    Name::class,
                    get_class($class)
                ));
            }

            /** @var class-string $className */
            $className = $class->toString();

            return $className;
        }

        if ($var instanceof Expr\Variable) {
            $variableName = $var->name;
            if (! is_string($variableName)) {
                throw new InvalidArgumentException('Can\'t handle non-string variable name');
            }

            $variableNameWithLeadingDollar = sprintf('$%s', $variableName);
            if (! isset($this->context->vars_possibly_in_scope[$variableNameWithLeadingDollar])) {
                throw new InvalidArgumentException('Used variable is unknown in context');
            }

            $variable = $this->context->vars_in_scope[$variableNameWithLeadingDollar];
            if (! $variable->isSingle()) {
                throw new InvalidArgumentException('Unable to detect class name from non-single typed variable.');
            }

            $type = $variable->getSingleAtomic();
            if (! $type instanceof TNamedObject) {
                throw new InvalidArgumentException('Unable to detect class name from non-named object.');
            }

            /** @var class-string $className */
            $className = $type->value;

            return $className;
        }

        throw new InvalidArgumentException(sprintf('Unable to parse class name from expression of type: %s', get_class($var)));
    }
}
