<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\EventHandler;

use Boesing\PsalmPluginStringf\Parser\Psalm\PhpVersion;
use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use InvalidArgumentException;
use Psalm\CodeLocation;
use Psalm\Issue\ArgumentIssue;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterEveryFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent;

use function count;
use function in_array;
use function sprintf;

final class StringfFunctionArgumentValidator implements AfterEveryFunctionCallAnalysisInterface
{
    private const FUNCTIONS = [
        'sprintf',
        'printf',
    ];

    private static function createCodeIssue(
        CodeLocation $codeLocation,
        string $functionName,
        int $argumentCount,
        int $requiredArgumentCount
    ): ArgumentIssue {
        $message = sprintf(
            'Template passed to function `%s` requires %d specifier but %d are passed.',
            $functionName,
            $requiredArgumentCount,
            $argumentCount
        );

        if ($argumentCount < $requiredArgumentCount) {
            return new TooFewArguments($message, $codeLocation, $functionName);
        }

        return new TooManyArguments($message, $codeLocation, $functionName);
    }

    public static function afterEveryFunctionCallAnalysis(AfterEveryFunctionCallAnalysisEvent $event): void
    {
        $functionId = $event->getFunctionId();
        if (! in_array($functionId, self::FUNCTIONS, true)) {
            return;
        }

        $expression = $event->getExpr();
        $arguments  = $expression->args;

        $template = $arguments[0] ?? null;
        if ($template === null) {
            return;
        }

        $context = $event->getContext();

        try {
            $parsed = TemplatedStringParser::fromArgument(
                $functionId,
                $template,
                $context,
                PhpVersion::fromCodebase($event->getCodebase())
            );
        } catch (InvalidArgumentException $exception) {
            return;
        }

        $argumentCount         = count($arguments) - 1;
        $requiredArgumentCount = $parsed->getPlaceholderCount();

        if ($argumentCount === $requiredArgumentCount) {
            return;
        }

        $codeLocation = new CodeLocation($event->getStatementsSource(), $expression);

        IssueBuffer::add(self::createCodeIssue(
            $codeLocation,
            $event->getFunctionId(),
            $argumentCount,
            $requiredArgumentCount
        ));
    }
}
