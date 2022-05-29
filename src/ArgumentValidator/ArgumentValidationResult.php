<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\ArgumentValidator;

final class ArgumentValidationResult
{
    /** @var 0|positive-int */
    public int $requiredArgumentCount;

    /** @var 0|positive-int */
    public int $actualArgumentCount;

    /**
     * @param 0|positive-int $requiredArgumentCount
     * @param 0|positive-int $actualArgumentCount
     */
    public function __construct(
        int $requiredArgumentCount,
        int $actualArgumentCount
    ) {
        $this->requiredArgumentCount = $requiredArgumentCount;
        $this->actualArgumentCount   = $actualArgumentCount;
    }

    public function valid(): bool
    {
        return $this->requiredArgumentCount === $this->actualArgumentCount;
    }
}
