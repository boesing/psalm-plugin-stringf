<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Hook;

use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use Psalm\Plugin\EventHandler\Event\FunctionReturnTypeProviderEvent;
use Psalm\Plugin\EventHandler\FunctionReturnTypeProviderInterface;
use Psalm\Type;

final class StringfFunctionReturnProvider implements FunctionReturnTypeProviderInterface
{
    private const FUNCTION_SPRINTF = 'sprintf';

    private const TEMPLATE_ARGUMENT_POSITION = 0;

    /**
     * @psalm-return non-empty-list<lowercase-string>
     */
    public static function getFunctionIds(): array
    {
        return [self::FUNCTION_SPRINTF];
    }

    public static function getFunctionReturnType(FunctionReturnTypeProviderEvent $event): ?Type\Union
    {
        $functionName          = $event->getFunctionId();
        $functionCallArguments = $event->getCallArgs();

        if (! isset($functionCallArguments[self::TEMPLATE_ARGUMENT_POSITION])) {
            return null;
        }

        $templateArgument           = $functionCallArguments[self::TEMPLATE_ARGUMENT_POSITION];
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
