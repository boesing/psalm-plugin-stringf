<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\EventHandler;

use Boesing\PsalmPluginStringf\Parser\Psalm\PhpVersion;
use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use Boesing\PsalmPluginStringf\Psalm\Issue\UnnecessaryFunctionCall;
use InvalidArgumentException;
use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterEveryFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent;
use Webmozart\Assert\Assert;

use function in_array;

final class UnnecessaryFunctionCallValidator implements AfterEveryFunctionCallAnalysisInterface
{
    private const FUNCTIONS = [
        'sprintf',
        'printf',
    ];

    /** @psalm-var non-empty-string */
    private string $functionName;

    /** @psalm-var non-empty-list<Arg> */
    private array $arguments;

    /**
     * @param non-empty-string    $functionName
     * @param non-empty-list<Arg> $arguments
     */
    public function __construct(
        string $functionName,
        array $arguments
    ) {
        $this->functionName = $functionName;
        $this->arguments    = $arguments;
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

        Assert::isNonEmptyList($arguments);
        Assert::allIsInstanceOf($arguments, Arg::class);

        $context = $event->getContext();

        /** @psalm-suppress InvalidScalarArgument */
        (new self(
            $functionId,
            $arguments
        ))->assert(
            new CodeLocation($event->getStatementsSource(), $expression),
            $context,
            PhpVersion::fromCodebase($event->getCodebase())
        );
    }

    /**
     * @psalm-param positive-int $phpVersion
     */
    private function assert(
        CodeLocation $codeLocation,
        Context $context,
        int $phpVersion
    ): void {
        $template = $this->arguments[0];

        try {
            $parsed = TemplatedStringParser::fromArgument(
                $this->functionName,
                $template,
                $context,
                $phpVersion
            );
        } catch (InvalidArgumentException $exception) {
            return;
        }

        $this->assertFunctionCallMakesSense(
            $codeLocation,
            $parsed
        );
    }

    private function assertFunctionCallMakesSense(
        CodeLocation $codeLocation,
        TemplatedStringParser $parsed
    ): void {
        if ($parsed->getTemplate() !== $parsed->getTemplateWithoutPlaceholder()) {
            return;
        }

        // TODO: find out how to provide psalter functionality
        IssueBuffer::add(new UnnecessaryFunctionCall($codeLocation, $this->functionName), false);
    }
}
