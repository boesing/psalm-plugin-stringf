<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\TemplatedStringParser;

use Boesing\PsalmPluginStringf\Parser\PhpParser\ArgumentValueParser;
use Boesing\PsalmPluginStringf\Parser\Psalm\TypeParser;
use InvalidArgumentException;
use PhpParser\Node\Arg;
use Psalm\Context;
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

    /**
     * @psalm-param non-empty-string $value
     * @psalm-param positive-int $position
     */
    private function __construct(string $value, int $position)
    {
        $this->value             = $value;
        $this->position          = $position;
        $this->argumentValueType = null;
        $this->type              = null;
    }

    /**
     * @psalm-param non-empty-string $value
     * @psalm-param positive-int $position
     */
    public static function create(string $value, int $position): self
    {
        return new self($value, $position);
    }

    /**
     * @psalm-param non-empty-list<Arg> $functionCallArguments
     */
    public function getArgumentValue(array $functionCallArguments, Context $context): ?string
    {
        $type = $this->getArgumentType($functionCallArguments, $context);
        if ($type === null) {
            return null;
        }

        return TypeParser::create($type)->stringify();
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
            $this->argumentValueType = ArgumentValueParser::create($argument->value, $context)->toType();
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
            $type = SpecifierTypeGenerator::create($this->value)->getSuggestedType();
        } catch (InvalidArgumentException $exception) {
            return null;
        }

        if ($this->repeated === []) {
            return $type;
        }

        $unions = [$type];

        foreach ($this->repeated as $placeholder) {
            try {
                $unions[] = $placeholder->getSuggestedType();
            } catch (InvalidArgumentException $exception) {
                return null;
            }
        }

        $types = [];
        foreach ($unions as $union) {
            foreach ($union->getAtomicTypes() as $type) {
                $types[] = $type;
            }
        }

        return $this->type = new Union($types);
    }
}
