<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\PhpParser;

use InvalidArgumentException;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\StatementsSource;
use Psalm\Type\Union;
use Webmozart\Assert\Assert;

use function sprintf;
use function strtolower;

final class ReturnTypeParser
{
    private StatementsSource $statementsSource;

    private CodeLocation $codeLocation;

    private FuncCall $value;

    private function __construct(StatementsSource $statementsSource, CodeLocation $codeLocation, FuncCall $value)
    {
        $this->statementsSource = $statementsSource;
        $this->codeLocation     = $codeLocation;
        $this->value            = $value;
    }

    public static function create(
        StatementsSource $statementsSource,
        CodeLocation $codeLocation,
        FuncCall $value
    ): self {
        return new self($statementsSource, $codeLocation, $value);
    }

    public function toType(): Union
    {
        $name = $this->value->name;
        Assert::isInstanceOf($name, Name::class, 'Could not detect function name.');

        $source = $this->statementsSource;
        if (! $source instanceof StatementsAnalyzer) {
            throw new InvalidArgumentException(sprintf(
                'Invalid statements source given. Can only handle %s at this time.',
                StatementsAnalyzer::class
            ));
        }

        $function_id = strtolower($name->toString());

        /** @psalm-suppress InternalMethod I don't see any other way of detecting the return type of a function (yet) */
        $analyzer = $source->getFunctionAnalyzer($function_id);
        if ($analyzer === null) {
            throw new InvalidArgumentException(sprintf(
                'Could not detect function analyzer for `function_id`: %s',
                $function_id
            ));
        }

        /** @psalm-suppress InternalMethod I don't see any other way of detecting the return type of a function (yet) */
        $storage              = $analyzer->getFunctionLikeStorage($source);
        $declared_return_type = $storage->return_type;
        if ($declared_return_type === null) {
            throw new InvalidArgumentException(sprintf('Could not detect return type for `function_id`: %s', $function_id));
        }

        return $declared_return_type;
    }
}
