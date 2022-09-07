<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\ArgumentValidator;

use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use PhpParser\Node\Arg;
use PhpParser\Node\VariadicPlaceholder;
use Webmozart\Assert\Assert;

final class StringfArgumentValidator implements ArgumentValidator
{
    /** @var 0|positive-int */
    private int $argumentsPriorPlaceholderArgumentsStart;

    /**
     * @param 0|positive-int $argumentsPriorPlaceholderArgumentsStart
     */
    public function __construct(int $argumentsPriorPlaceholderArgumentsStart)
    {
        $this->argumentsPriorPlaceholderArgumentsStart = $argumentsPriorPlaceholderArgumentsStart;
    }

    public function validate(TemplatedStringParser $templatedStringParser, array $arguments): ArgumentValidationResult
    {
        $requiredArgumentCount = $templatedStringParser->getPlaceholderCount();
        $currentArgumentCount  = $this->countArguments($arguments) - $this->argumentsPriorPlaceholderArgumentsStart;
        Assert::natural($currentArgumentCount);

        return new ArgumentValidationResult(
            $requiredArgumentCount,
            $currentArgumentCount,
        );
    }

    /**
     * @param array<Arg|VariadicPlaceholder> $arguments
     */
    private function countArguments(array $arguments): int
    {
        $argumentCount = 0;
        foreach ($arguments as $argument) {
            if ($argument instanceof VariadicPlaceholder) {
                continue;
            }

            $argumentCount++;
        }

        return $argumentCount;
    }
}
