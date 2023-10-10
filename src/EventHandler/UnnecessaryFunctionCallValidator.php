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
use Psalm\StatementsSource;
use Webmozart\Assert\Assert;

use function in_array;

final class UnnecessaryFunctionCallValidator implements AfterEveryFunctionCallAnalysisInterface
{
    private const FUNCTIONS = [
        'sprintf',
        'printf',
    ];

    /**
     * @param non-empty-string    $functionName
     * @param non-empty-list<Arg> $arguments
     */
    public function __construct(
        private string $functionName,
        private array $arguments,
    ) {
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

        Assert::allIsInstanceOf($arguments, Arg::class);
        Assert::isNonEmptyList($arguments);

        $context          = $event->getContext();
        $statementsSource = $event->getStatementsSource();

        (new self(
            $functionId,
            $arguments,
        ))->assert(
            $statementsSource,
            new CodeLocation($statementsSource, $expression),
            $context,
            PhpVersion::fromCodebase($event->getCodebase())->versionId,
        );
    }

    /**
     * @psalm-param positive-int $phpVersion
     */
    private function assert(
        StatementsSource $statementsSource,
        CodeLocation $codeLocation,
        Context $context,
        int $phpVersion,
    ): void {
        $template = $this->arguments[0];

        try {
            $parsed = TemplatedStringParser::fromArgument(
                $this->functionName,
                $template,
                $context,
                $phpVersion,
                false,
                $statementsSource,
            );
        } catch (InvalidArgumentException) {
            return;
        }

        $this->assertFunctionCallMakesSense(
            $codeLocation,
            $parsed,
        );
    }

    private function assertFunctionCallMakesSense(
        CodeLocation $codeLocation,
        TemplatedStringParser $parsed,
    ): void {
        if ($parsed->getTemplate() !== $parsed->getTemplateWithoutPlaceholder()) {
            return;
        }

        // TODO: find out how to provide psalter functionality
        IssueBuffer::maybeAdd(new UnnecessaryFunctionCall($codeLocation, $this->functionName));
    }
}
