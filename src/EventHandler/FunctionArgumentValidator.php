<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\EventHandler;

use Boesing\PsalmPluginStringf\ArgumentValidator\ArgumentValidator;
use Boesing\PsalmPluginStringf\Parser\Psalm\PhpVersion;
use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use Boesing\PsalmPluginStringf\Psalm\Issue\TooFewArguments;
use Boesing\PsalmPluginStringf\Psalm\Issue\TooManyArguments;
use InvalidArgumentException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\VariadicPlaceholder;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\PluginIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterEveryFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent;
use Psalm\StatementsSource;

use function sprintf;

/**
 * @psalm-consistent-constructor
 */
abstract class FunctionArgumentValidator implements AfterEveryFunctionCallAnalysisInterface
{
    protected function __construct(protected StatementsSource $statementsSource, protected CodeLocation $codeLocation, protected PhpVersion $phpVersion, protected FuncCall $functionCall)
    {
    }

    /**
     * @return 0|positive-int
     */
    abstract protected function getTemplateArgumentIndex(): int;

    /**
     * @return non-empty-string
     */
    abstract protected function getIssueTemplate(): string;

    abstract protected function getArgumentValidator(): ArgumentValidator;

    private function createCodeIssue(
        CodeLocation $codeLocation,
        string $functionName,
        int $argumentCount,
        int $requiredArgumentCount,
    ): PluginIssue {
        $message = $this->createIssueMessage(
            $functionName,
            $requiredArgumentCount,
            $argumentCount,
        );

        if ($argumentCount < $requiredArgumentCount) {
            return new TooFewArguments($message, $codeLocation, $functionName);
        }

        return new TooManyArguments($message, $codeLocation, $functionName);
    }

    /**
     * @psalm-return non-empty-string
     */
    private function createIssueMessage(string $functionName, int $requiredArgumentCount, int $argumentCount): string
    {
        return sprintf(
            $this->getIssueTemplate(),
            $functionName,
            $requiredArgumentCount,
            $argumentCount,
        );
    }

    /**
     * @param non-empty-string $functionId
     */
    abstract protected function canHandleFunction(string $functionId): bool;

    public static function afterEveryFunctionCallAnalysis(AfterEveryFunctionCallAnalysisEvent $event): void
    {
        $functionId = $event->getFunctionId();
        if ($functionId === '') {
            return;
        }

        $functionCall = $event->getExpr();
        $arguments    = $functionCall->args;

        $statementsSource = $event->getStatementsSource();

        (new static($statementsSource, new CodeLocation($statementsSource, $functionCall), PhpVersion::fromCodebase($event->getCodebase()), $functionCall))->validate(
            $functionId,
            $arguments,
            $event->getContext(),
        );
    }

    /**
     * @param non-empty-string               $functionName
     * @param array<Arg|VariadicPlaceholder> $arguments
     */
    private function validate(
        string $functionName,
        array $arguments,
        Context $context,
    ): void {
        if (! $this->canHandleFunction($functionName)) {
            return;
        }

        $templateArgumentIndex = $this->getTemplateArgumentIndex();
        $template              = null;

        foreach ($arguments as $index => $argument) {
            if ($index < $templateArgumentIndex) {
                continue;
            }

            if ($argument instanceof VariadicPlaceholder) {
                continue;
            }

            $template = $argument;
            break;
        }

        // Unable to detect template argument
        if ($template === null) {
            return;
        }

        try {
            $parsed = TemplatedStringParser::fromArgument(
                $functionName,
                $template,
                $context,
                $this->phpVersion->versionId,
                false,
                $this->statementsSource,
            );
        } catch (InvalidArgumentException) {
            return;
        }

        $validator        = $this->getArgumentValidator();
        $validationResult = $validator->validate($parsed, $arguments);

        if ($validationResult->valid()) {
            return;
        }

        IssueBuffer::maybeAdd($this->createCodeIssue(
            $this->codeLocation,
            $functionName,
            $validationResult->actualArgumentCount,
            $validationResult->requiredArgumentCount,
        ));
    }
}
