<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\TemplatedStringParser;

use Boesing\PsalmPluginStringf\Parser\PhpParser\ArgumentValueParser;
use Boesing\PsalmPluginStringf\Parser\Psalm\TypeParser;
use InvalidArgumentException;
use PhpParser\Node\Arg;
use Psalm\Context;
use Psalm\Type\Union;

final class Placeholder
{
    public int $position;

    public string $value;

    private ?Union $type;

    private function __construct(string $value, int $position)
    {
        $this->value    = $value;
        $this->position = $position;
        $this->type     = null;
    }

    public static function create(string $value, int $position): self
    {
        return new self($value, $position);
    }

    /**
     * @psalm-param non-empty-list<Arg> $functionCallArguments
     */
    public function getArgumentValue(array $functionCallArguments, Context $context): ?string
    {
        $type = $this->type($functionCallArguments, $context);
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
        $type = $this->type($functionCallArguments, $context);
        if ($type === null) {
            return true;
        }

        $string = $this->stringify($type);

        return $string === null || $string === '';
    }

    /**
     * @psalm-param list<Arg> $functionCallArguments
     */
    private function type(array $functionCallArguments, Context $context): ?Union
    {
        if ($this->type) {
            return $this->type;
        }

        $argument = $functionCallArguments[$this->position] ?? null;
        if ($argument === null) {
            return null;
        }

        try {
            $this->type = ArgumentValueParser::create($argument->value, $context)->toType();
        } catch (InvalidArgumentException $exception) {
            return null;
        }

        return $this->type;
    }

    private function stringify(Union $type): ?string
    {
        return TypeParser::create($type)->stringify();
    }
}
