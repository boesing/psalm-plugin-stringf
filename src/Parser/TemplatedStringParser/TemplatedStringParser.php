<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\TemplatedStringParser;

use Boesing\PsalmPluginStringf\Parser\PhpParser\ArgumentValueParser;
use PhpParser\Node\Arg;

use function array_filter;
use function in_array;
use function max;
use function preg_match_all;
use function sprintf;
use function strlen;
use function substr_replace;

use const ARRAY_FILTER_USE_BOTH;
use const PHP_INT_MIN;
use const PREG_OFFSET_CAPTURE;
use const PREG_SET_ORDER;

final class TemplatedStringParser
{
    private const ARGUMENT_SCAN_REGEX_PATTERN_PREFIX       = '(?<before>%*)%(?:(?<position>\d+)\$)?[-+]?(?:[ 0]|'
    . '(?:\'[^%]))?-?\d*(?:\.\d*)?';
    private const PRINTF_SPECIFIERS_REGEX_PATTERN_TEMPLATE = '[bcdeEfFgGosuxX%s]';
    private const SCANF_SPECIFIERS_REGEX_PATTERN_TEMPLATE  = '(?:[cdDeEfinosuxX%s]|\[[^\]]+\])';
    private const SPECIFIER_SINCE_PHP8                     = 'hH';

    private string $templateWithoutPlaceholder;

    /** @psalm-var list<Placeholder> */
    private array $placeholders;

    private function __construct(
        string $functionName,
        string $template,
        ?int $phpVersion
    ) {
        $this->templateWithoutPlaceholder = $template;
        $this->placeholders               = [];
        $this->parse($functionName, $template, $phpVersion ?? PHP_INT_MIN);
    }

    private function parse(
        string $functionName,
        string $template,
        int $phpVersion
    ): void {
        $additionalSpecifierDependingOnPhpVersion = '';
        if ($phpVersion >= 80000) {
            $additionalSpecifierDependingOnPhpVersion .= self::SPECIFIER_SINCE_PHP8;
        }

        $specifiers = sprintf(
            in_array($functionName, ['sprintf', 'printf'], true)
                ? self::PRINTF_SPECIFIERS_REGEX_PATTERN_TEMPLATE : self::SCANF_SPECIFIERS_REGEX_PATTERN_TEMPLATE,
            $additionalSpecifierDependingOnPhpVersion
        );

        $pattern               = self::ARGUMENT_SCAN_REGEX_PATTERN_PREFIX . $specifiers;
        $potentialPlaceholders = [];
        preg_match_all(
            sprintf('~%s~', $pattern),
            $template,
            $potentialPlaceholders,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE
        );

        if ($potentialPlaceholders === []) {
            return;
        }

        $placeholders = array_filter(
            $potentialPlaceholders,
            /** @param array{before:array{0:string}} $placeholder */
            static function (
                array $placeholder
            ): bool {
                $patternPrefix = $placeholder['before'][0];

                return strlen($patternPrefix) % 2 === 0;
            },
            ARRAY_FILTER_USE_BOTH
        );

        if ($placeholders === []) {
            return;
        }

        $placeholderInstances         = [];
        $removedCharacters            = 0;
        $maximumOrdinalPosition       = 0;
        $maximumPositionByPlaceholder = 0;

        $templateWithoutPlaceholders = $template;

        foreach ($placeholders as $placeholder) {
            [$placeholderValue, $placeholderIndex] = $placeholder[0];
            $placeholderLength                     = strlen($placeholderValue);
            $templateWithoutPlaceholders           = substr_replace(
                $templateWithoutPlaceholders,
                '',
                $placeholderIndex - $removedCharacters,
                $placeholderLength
            );
            $removedCharacters                    += $placeholderLength;
            $placeholderPosition                   = (int) ($placeholder['position'][0] ?? 0);
            $maximumPositionByPlaceholder          = max($maximumPositionByPlaceholder, $placeholderPosition);
            if ($placeholderPosition === 0) {
                $placeholderPosition = $maximumOrdinalPosition;
                $maximumOrdinalPosition++;
            }

            $placeholderInstances[] = Placeholder::create(
                $placeholderValue,
                $placeholderPosition
            );
        }

        $this->placeholders               = $placeholderInstances;
        $this->templateWithoutPlaceholder = $templateWithoutPlaceholders;
    }

    public static function fromArgument(
        string $functionName,
        Arg $templateArgument
    ): self {
        return new self(
            $functionName,
            ArgumentValueParser::create($templateArgument)->toString(),
            null
        );
    }

    public function getTemplateWithoutPlaceholder(): string
    {
        return $this->templateWithoutPlaceholder;
    }

    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }
}
