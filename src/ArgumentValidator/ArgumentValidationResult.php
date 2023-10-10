<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\ArgumentValidator;

final class ArgumentValidationResult
{
    /**
     * @param 0|positive-int $requiredArgumentCount
     * @param 0|positive-int $actualArgumentCount
     */
    public function __construct(
        public int $requiredArgumentCount,
        public int $actualArgumentCount,
    ) {
    }

    public function valid(): bool
    {
        return $this->requiredArgumentCount === $this->actualArgumentCount;
    }
}
