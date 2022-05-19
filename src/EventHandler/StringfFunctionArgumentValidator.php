<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\EventHandler;

use Boesing\PsalmPluginStringf\Parser\Psalm\PhpVersion;
use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use InvalidArgumentException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ArgumentIssue;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterEveryFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent;
use Webmozart\Assert\Assert;

use function assert;
use function count;
use function in_array;
use function sprintf;

final class StringfFunctionArgumentValidator implements AfterEveryFunctionCallAnalysisInterface
{
    private const FUNCTIONS = [
        'sprintf',
        'printf',
        'sscanf',
        'fscanf',
    ];

    private const TEMPLATE_ARGUMENT_INDEX = [
        'sprintf' => 0,
        'printf' => 0,
        'sscanf' => 1,
        'fscanf' => 1,
    ];

    private const ISSUE_TEMPLATE_BY_FUNCTION_NAME = [
        'sprintf' => self::PRINTF_ISSUE_TEMPLATE,
        'printf' => self::PRINTF_ISSUE_TEMPLATE,
        'sscanf' => self::SCANF_ISSUE_TEMPLATE,
        'fscanf' => self::SCANF_ISSUE_TEMPLATE,
    ];

    private const PRINTF_ISSUE_TEMPLATE = 'Template passed to function `%s` requires %d specifier but %d are passed.';
    private const SCANF_ISSUE_TEMPLATE  = 'Template passed to function `%s` declares %d specifier but only %d'
    . ' argument is passed.';

    private FuncCall $functionCall;

    /** @psalm-var non-empty-string */
    private string $functionName;

    /** @psalm-var non-empty-list<Arg> */
    private array $arguments;

    /**
     * @psalm-param non-empty-string    $functionName
     * @psalm-param non-empty-list<Arg> $arguments
     */
    public function __construct(FuncCall $functionCall, string $functionName, array $arguments)
    {
        $this->functionCall = $functionCall;
        $this->functionName = $functionName;
        $this->arguments    = $arguments;
    }

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

    public static function afterEveryFunctionCallAnalysis(AfterEveryFunctionCallAnalysisEvent $event): void
    {
        $functionId = $event->getFunctionId();
        if (! in_array($functionId, self::FUNCTIONS, true)) {
            return;
        }

        $argumentIndex = self::TEMPLATE_ARGUMENT_INDEX[$functionId] ?? null;
        if ($argumentIndex === null) {
            return;
        }

        $functionCall = $event->getExpr();
        $arguments    = $functionCall->args;
        if (! isset($arguments[$argumentIndex])) {
            return;
        }

        Assert::isNonEmptyList($arguments);
        Assert::allIsInstanceOf($arguments, Arg::class);

        (new self($functionCall, $functionId, $arguments))->validate(
            new CodeLocation($event->getStatementsSource(), $functionCall),
            $argumentIndex,
            $event->getContext(),
            PhpVersion::fromCodebase($event->getCodebase())
        );
    }

    /**
     * @psalm-param 0|positive-int $templateArgumentIndex
     * @psalm-param positive-int $phpVersion
     */
    private function validate(
        CodeLocation $codeLocation,
        int $templateArgumentIndex,
        Context $context,
        int $phpVersion
    ): void {
        $template = $this->arguments[$templateArgumentIndex];

        try {
            $parsed = TemplatedStringParser::fromArgument(
                $this->functionName,
                $template,
                $context,
                $phpVersion,
                false
            );
        } catch (InvalidArgumentException $exception) {
            return;
        }

        $argumentCount         = count($this->arguments) - $templateArgumentIndex - 1;
        $requiredArgumentCount = $parsed->getPlaceholderCount();

        if ($argumentCount === $requiredArgumentCount) {
            return;
        }

        IssueBuffer::add($this->createCodeIssue(
            $codeLocation,
            $this->functionName,
            $argumentCount,
            $requiredArgumentCount
        ));
    }

    /**
     * @psalm-return non-empty-string
     */
    private function createIssueMessage(string $functionName, int $requiredArgumentCount, int $argumentCount): string
    {
        $message = sprintf(
            self::ISSUE_TEMPLATE_BY_FUNCTION_NAME[$functionName],
            $functionName,
            $requiredArgumentCount,
            $argumentCount
        );

        assert($message !== '');

        return $message;
    }
}
