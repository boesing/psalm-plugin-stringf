<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\EventHandler;

use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use InvalidArgumentException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\PossiblyInvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterEveryFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent;
use Psalm\Type\Union;
use Webmozart\Assert\Assert;

use function in_array;
use function sprintf;

final class PossiblyInvalidArgumentForSpecifierValidator implements AfterEveryFunctionCallAnalysisInterface
{
    private const FUNCTIONS = [
        'sprintf',
        'printf',
    ];

    /** @psalm-var non-empty-string */
    private string $functionName;

    /** @psalm-var non-empty-list<Arg> */
    private array $arguments;

    private FuncCall $functionCall;

    /**
     * @param non-empty-string    $functionName
     * @param non-empty-list<Arg> $arguments
     */
    public function __construct(
        FuncCall $functionCall,
        string $functionName,
        array $arguments
    ) {
        $this->functionCall = $functionCall;
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

        $context = $event->getContext();

        (new self(
            $expression,
            $functionId,
            $arguments
        ))->assert(
            new CodeLocation($event->getStatementsSource(), $expression),
            $context
        );
    }

    public function assert(CodeLocation $codeLocation, Context $context): void
    {
        $template = $this->arguments[0];

        try {
            $parsed = TemplatedStringParser::fromArgument(
                $this->functionName,
                $template,
                $context
            );
        } catch (InvalidArgumentException $exception) {
            return;
        }

        $this->assertArgumentsMatchingPlaceholderTypes(
            $codeLocation,
            $parsed,
            $this->arguments,
            $context
        );
    }

    /**
     * @psalm-param non-empty-list<Arg> $args
     */
    private function assertArgumentsMatchingPlaceholderTypes(
        CodeLocation $codeLocation,
        TemplatedStringParser $parsed,
        array $args,
        Context $context
    ): void {
        foreach ($parsed->getPlaceholders() as $placeholder) {
            $argumentType = $placeholder->getArgumentType($args, $context);
            if ($argumentType === null) {
                continue;
            }

            $type = $placeholder->getSuggestedType();

            if ($this->validateArgumentTypeMatchesSuggestedType($argumentType, $type)) {
                continue;
            }

            IssueBuffer::add(
                new PossiblyInvalidArgument(
                    sprintf(
                        'Argument %d inferred as "%s" does not match (any of) the suggested type(s) "%s"',
                        $placeholder->position,
                        (string) $argumentType,
                        (string) $type
                    ),
                    $codeLocation,
                    $this->functionName
                )
            );
        }
    }

    private function validateArgumentTypeMatchesSuggestedType(Union $argument, Union $suggested): bool
    {
        foreach ($argument->getAtomicTypes() as $type) {
            foreach ($suggested->getAtomicTypes() as $suggestType) {
                if ($type instanceof $suggestType) {
                    return true;
                }
            }
        }

        return false;
    }
}
