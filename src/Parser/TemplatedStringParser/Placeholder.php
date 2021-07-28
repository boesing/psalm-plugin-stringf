<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\TemplatedStringParser;

final class Placeholder
{
    public int $position;

    public string $value;

    private function __construct(string $value, int $position)
    {
        $this->value    = $value;
        $this->position = $position;
    }

    public static function create(string $value, int $position): self
    {
        return new self($value, $position);
    }
}
