<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\EventHandler;

use Boesing\PsalmPluginStringf\Parser\Psalm\PhpVersion;
use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use InvalidArgumentException;
use PhpParser\Node\Arg;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\PossiblyInvalidArgument;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterEveryFunctionCallAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterEveryFunctionCallAnalysisEvent;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Union;
use Webmozart\Assert\Assert;

use function in_array;
use function is_numeric;
use function sprintf;

final class PossiblyInvalidArgumentForSpecifierValidator implements AfterEveryFunctionCallAnalysisInterface
{
    public static bool $allowIntegerForStringPlaceholder = true;

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

        Assert::allIsInstanceOf($arguments, Arg::class);
        Assert::isNonEmptyList($arguments);

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
    public function assert(
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
                $phpVersion,
                self::$allowIntegerForStringPlaceholder
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
            if ($type === null) {
                continue;
            }

            if ($this->validateArgumentTypeMatchesSuggestedType($argumentType, $type)) {
                continue;
            }

            IssueBuffer::add(
                new PossiblyInvalidArgument(
                    sprintf(
                        'Argument %d inferred as "%s" does not match (any of) the suggested type(s) "%s"',
                        $placeholder->position,
                        $argumentType->getId(),
                        $type->getId()
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
            if ($this->invalidTypeWouldBeCoveredByPsalmItself($type)) {
                return true;
            }

            foreach ($suggested->getAtomicTypes() as $suggestType) {
                if ($type instanceof $suggestType) {
                    return true;
                }

                if ($this->typeMatchesSuggestedTypeDueToAdditionalChecks($type, $suggestType)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function typeMatchesSuggestedTypeDueToAdditionalChecks(Atomic $type, Atomic $suggestType): bool
    {
        if ($suggestType instanceof TNumericString) {
            if ($type instanceof TLiteralString) {
                return is_numeric($type->value);
            }
        }

        return false;
    }

    private function invalidTypeWouldBeCoveredByPsalmItself(Atomic $type): bool
    {
        if ($type instanceof Atomic\TString) {
            return false;
        }

        if ($type instanceof Atomic\TInt) {
            return false;
        }

        if ($type instanceof Atomic\TFloat) {
            return false;
        }

        return true;
    }

    public static function applyOptions(array $options): void
    {
        if (! isset($options['allowIntegerForString']) || $options['allowIntegerForString'] === 'yes') {
            return;
        }

        self::$allowIntegerForStringPlaceholder = false;
    }
}
