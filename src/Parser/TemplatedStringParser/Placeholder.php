<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\TemplatedStringParser;

use Boesing\PsalmPluginStringf\Parser\PhpParser\ArgumentValueParser;
use Boesing\PsalmPluginStringf\Parser\PhpParser\ReturnTypeParser;
use Boesing\PsalmPluginStringf\Parser\Psalm\TypeParser;
use InvalidArgumentException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use Psalm\Context;
use Psalm\StatementsSource;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Union;

final class Placeholder
{
    /** @psalm-var positive-int */
    public int $position;

    /** @psalm-var non-empty-string */
    public string $value;

    private ?Union $argumentValueType;

    /** @var list<Placeholder> */
    private array $repeated = [];

    private ?Union $type;

    private bool $allowIntegerForStringPlaceholder;

    private StatementsSource $statementsSource;

    /**
     * @psalm-param non-empty-string $value
     * @psalm-param positive-int $position
     */
    private function __construct(
        string $value,
        int $position,
        bool $allowIntegerForStringPlaceholder,
        StatementsSource $statementsSource
    ) {
        $this->value                            = $value;
        $this->position                         = $position;
        $this->argumentValueType                = null;
        $this->type                             = null;
        $this->allowIntegerForStringPlaceholder = $allowIntegerForStringPlaceholder;
        $this->statementsSource                 = $statementsSource;
    }

    /**
     * @psalm-param non-empty-string $value
     * @psalm-param positive-int $position
     */
    public static function create(string $value, int $position, bool $allowIntegerForStringPlaceholder, StatementsSource $statementsSource): self
    {
        return new self($value, $position, $allowIntegerForStringPlaceholder, $statementsSource);
    }

    /**
     * @psalm-param list<Arg> $functionCallArguments
     */
    public function stringifiedValueMayBeEmpty(array $functionCallArguments, Context $context): bool
    {
        $type = $this->getArgumentType($functionCallArguments, $context);
        if ($type === null) {
            return true;
        }

        if ($this->isNonEmptyString($type)) {
            return false;
        }

        $string = $this->stringify($type);

        return $string === null || $string === '';
    }

    /**
     * @psalm-param list<Arg> $functionCallArguments
     */
    public function getArgumentType(array $functionCallArguments, Context $context): ?Union
    {
        if ($this->argumentValueType) {
            return $this->argumentValueType;
        }

        $argument = $functionCallArguments[$this->position] ?? null;
        if ($argument === null) {
            return null;
        }

        try {
            $this->argumentValueType = $this->getArgumentValueType($argument->value, $context);
        } catch (InvalidArgumentException $exception) {
            return null;
        }

        return $this->argumentValueType;
    }

    private function stringify(Union $type): ?string
    {
        return TypeParser::create($type)->stringify();
    }

    private function isNonEmptyString(Union $type): bool
    {
        if (! $type->isString()) {
            return false;
        }

        foreach ($type->getAtomicTypes() as $type) {
            if (! $type instanceof TNonEmptyString) {
                return false;
            }
        }

        return true;
    }

    public function withRepeatedPlaceholder(Placeholder $placeholder): self
    {
        $instance             = clone $this;
        $instance->repeated[] = $placeholder;
        $instance->type       = null;

        return $instance;
    }

    public function getSuggestedType(): ?Union
    {
        if ($this->type) {
            return $this->type;
        }

        try {
            $type = SpecifierTypeGenerator::create($this->value, $this->allowIntegerForStringPlaceholder)->getSuggestedType();
        } catch (InvalidArgumentException $exception) {
            return null;
        }

        if ($this->repeated === []) {
            return $this->type = $type;
        }

        $unions = [$type];

        foreach ($this->repeated as $placeholder) {
            $suggestion = $placeholder->getSuggestedType();
            if ($suggestion === null) {
                return null;
            }

            $unions[] = $suggestion;
        }

        $types = [];
        foreach ($unions as $union) {
            foreach ($union->getAtomicTypes() as $type) {
                $types[] = $type;
            }
        }

        return $this->type = new Union($types);
    }

    private function getArgumentValueType(Expr $value, Context $context): Union
    {
        if ($value instanceof Expr\FuncCall || $value instanceof Expr\StaticCall || $value instanceof Expr\MethodCall) {
            return ReturnTypeParser::create($this->statementsSource, $context, $value)->toType();
        }

        return ArgumentValueParser::create($value, $context, $this->statementsSource)->toType();
    }
}
