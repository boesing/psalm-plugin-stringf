<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Hook;

use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;

final class PrintfFunctionReturnProvider implements FunctionReturnTypeProviderInterface
{
    private const SPRINTF_FUNCTION = 'sprintf';
    private const PRINTF_FUNCTION  = 'printf';
    private const SSCANF_FUNCTION  = 'sscanf';
    private const FSCANF_FUNCTION  = 'fscanf';

    private const TEMPLATE_ARGUMENT_POSITION = [
        self::SPRINTF_FUNCTION => 0,
        self::PRINTF_FUNCTION => 0,
        self::SSCANF_FUNCTION => 1,
        self::FSCANF_FUNCTION => 1,
    ];

    /**
     * @psalm-return non-empty-list<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [self::SPRINTF_FUNCTION, self::PRINTF_FUNCTION];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        $functionName          = $event->getFunctionId();
        $functionCallArguments = $event->getCallArgs();

        if (! isset(self::TEMPLATE_ARGUMENT_POSITION[$functionName])) {
            return null;
        }

        $templateArgumentPosition = self::TEMPLATE_ARGUMENT_POSITION[$functionName];
        if (! isset($functionCallArguments[$templateArgumentPosition])) {
            return null;
        }

        $templateArgument           = $functionCallArguments[$templateArgumentPosition];
        $templateWithoutPlaceholder = TemplatedStringParser::fromArgument(
            $functionName,
            $templateArgument
        )->getTemplateWithoutPlaceholder();

        if ($templateWithoutPlaceholder !== '') {
            return new Type\Union(
                [new Type\Atomic\TNonEmptyString()],
            );
        }

        return new Type\Union(
            [new Type\Atomic\TString()],
        );
    }
}
