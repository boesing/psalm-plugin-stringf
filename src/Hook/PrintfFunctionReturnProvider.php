<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Hook;

use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;
use Webmozart\Assert\Assert;

use function in_array;

final class PrintfFunctionReturnProvider implements FunctionReturnTypeProviderInterface
{
    private const FUNCTION_SPRINTF = 'sprintf';
    private const FUNCTION_PRINTF  = 'printf';
    private const FUNCTION_SSCANF  = 'sscanf';
    private const FUNCTION_FSCANF  = 'fscanf';

    private const SUPPORTED_FUNCTIONS = [
        self::FUNCTION_SPRINTF,
        self::FUNCTION_PRINTF,
        self::FUNCTION_FSCANF,
        self::FUNCTION_SSCANF,
    ];

    private const TEMPLATE_ARGUMENT_POSITION        = [
        self::FUNCTION_SPRINTF => 0,
        self::FUNCTION_PRINTF => 0,
        self::FUNCTION_SSCANF => 1,
        self::FUNCTION_FSCANF => 1,
    ];

    private const FUNCTIONS_WITH_STRING_RETURN_TYPE = [
        self::FUNCTION_SPRINTF,
        self::FUNCTION_PRINTF,
    ];

    /**
     * @psalm-return non-empty-list<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return self::SUPPORTED_FUNCTIONS;
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        /** @psalm-var PrintfFunctionReturnProvider::FUNCTION_* $functionName */
        $functionName = $event->getFunctionId();
        Assert::oneOf($functionName, self::SUPPORTED_FUNCTIONS);
        $functionCallArguments = $event->getCallArgs();

        if (! isset(self::TEMPLATE_ARGUMENT_POSITION[$functionName])) {
            return null;
        }

        $templateArgumentPosition = self::TEMPLATE_ARGUMENT_POSITION[$functionName];
        if (! isset($functionCallArguments[$templateArgumentPosition])) {
            return null;
        }

        $templateArgument      = $functionCallArguments[$templateArgumentPosition];
        $templatedStringParser = TemplatedStringParser::fromArgument(
            $functionName,
            $templateArgument
        );

        return self::createTypeBasedOnFunction(
            $functionName,
            $templatedStringParser
        );
    }

    /** @psalm-param PrintfFunctionReturnProvider::FUNCTION_* $functionName */
    private static function createTypeBasedOnFunction(string $functionName, TemplatedStringParser $parser): ?Type\Union
    {
        if (in_array($functionName, self::FUNCTIONS_WITH_STRING_RETURN_TYPE, true)) {
            $templateWithoutPlaceholder = $parser->getTemplateWithoutPlaceholder();
            if ($templateWithoutPlaceholder !== '') {
                return new Type\Union(
                    [new Type\Atomic\TNonEmptyString()],
                );
            }

            return new Type\Union(
                [new Type\Atomic\TString()],
            );
        }

        return null;
    }
}
