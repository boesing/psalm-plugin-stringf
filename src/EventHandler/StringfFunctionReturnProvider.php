<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\EventHandler;

use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\Placeholder;
use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use InvalidArgumentException;
use PhpParser\Node\Arg;
use Psalm\Context;
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

        $templateArgument = $functionCallArguments[self::TEMPLATE_ARGUMENT_POSITION];
        $context          = $event->getContext();
        try {
            $parser = TemplatedStringParser::fromArgument(
                $functionName,
                $templateArgument,
                $context
            );
        } catch (InvalidArgumentException $exception) {
            return null;
        }

        return self::detectTypes($parser, $functionCallArguments, $context);
    }

    /**
     * @psalm-param list<Arg> $functionCallArguments
     */
    private static function detectTypes(
        TemplatedStringParser $parser,
        array $functionCallArguments,
        Context $context
    ): ?Type\Union {
        $templateWithoutPlaceholder = $parser->getTemplateWithoutPlaceholder();

        if ($templateWithoutPlaceholder !== '') {
            return new Type\Union(
                [new Type\Atomic\TNonEmptyString()],
            );
        }

        $placeholders = $parser->getPlaceholders();

        return self::detectReturnTypeWithConsideringFunctionCallArguments(
            $placeholders,
            $functionCallArguments,
            $context
        );
    }

    /**
     * @psalm-param array<positive-int,Placeholder> $placeholders
     * @psalm-param list<Arg> $functionCallArguments
     */
    private static function detectReturnTypeWithConsideringFunctionCallArguments(
        array $placeholders,
        array $functionCallArguments,
        Context $context
    ): ?Type\Union {
        if ($placeholders === [] || $functionCallArguments === []) {
            return null;
        }

        foreach ($placeholders as $placeholder) {
            if ($placeholder->stringifiedValueMayBeEmpty($functionCallArguments, $context)) {
                continue;
            }

            return new Type\Union([new Type\Atomic\TNonEmptyString()]);
        }

        return null;
    }
}
