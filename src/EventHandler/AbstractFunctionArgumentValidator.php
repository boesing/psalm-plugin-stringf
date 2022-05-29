<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\EventHandler;

use Boesing\PsalmPluginStringf\ArgumentValidator\ArgumentValidatorInterface;
use Boesing\PsalmPluginStringf\Parser\Psalm\PhpVersion;
use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use InvalidArgumentException;
use PhpParser\Node\Arg;
use PhpParser\Node\VariadicPlaceholder;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ArgumentIssue;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterEveryFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent;
use Psalm\StatementsSource;

use function assert;
use function sprintf;

/**
 * @psalm-consistent-constructor
 */
abstract class AbstractFunctionArgumentValidator implements AfterEveryFunctionCallAnalysisInterface
{
    private StatementsSource $statementsSource;

    private CodeLocation $codeLocation;

    private PhpVersion $phpVersion;

    protected function __construct(StatementsSource $statementsSource, CodeLocation $codeLocation, PhpVersion $phpVersion)
    {
        $this->statementsSource = $statementsSource;
        $this->codeLocation     = $codeLocation;
        $this->phpVersion       = $phpVersion;
    }

    /**
     * @return 0|positive-int
     */
    abstract protected function getTemplateArgumentIndex(): int;

    /**
     * @return non-empty-string
     */
    abstract protected function getIssueTemplate(): string;

    abstract protected function getArgumentValidator(): ArgumentValidatorInterface;

    private function createCodeIssue(
        CodeLocation $codeLocation,
        string $functionName,
        int $argumentCount,
        int $requiredArgumentCount
    ): ArgumentIssue {
        $message = $this->createIssueMessage(
            $functionName,
            $requiredArgumentCount,
            $argumentCount
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
        $message = sprintf(
            $this->getIssueTemplate(),
            $functionName,
            $requiredArgumentCount,
            $argumentCount
        );

        assert($message !== '');

        return $message;
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

        (new static($statementsSource, new CodeLocation($statementsSource, $functionCall), PhpVersion::fromCodebase($event->getCodebase())))->validate(
            $functionId,
            $arguments,
            $event->getContext()
        );
    }

    /**
     * @param non-empty-string               $functionName
     * @param array<Arg|VariadicPlaceholder> $arguments
     */
    private function validate(
        string $functionName,
        array $arguments,
        Context $context
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
                $this->statementsSource
            );
        } catch (InvalidArgumentException $exception) {
            return;
        }

        $validator        = $this->getArgumentValidator();
        $validationResult = $validator->validate($parsed, $arguments);

        if ($validationResult->valid()) {
            return;
        }

        IssueBuffer::add($this->createCodeIssue(
            $this->codeLocation,
            $functionName,
            $validationResult->actualArgumentCount,
            $validationResult->requiredArgumentCount
        ));
    }
}
