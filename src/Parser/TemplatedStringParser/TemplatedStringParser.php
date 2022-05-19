<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\TemplatedStringParser;

use Boesing\PsalmPluginStringf\Parser\PhpParser\ArgumentValueParser;
use PhpParser\Node\Arg;
use Psalm\Context;
use Webmozart\Assert\Assert;

use function array_filter;
use function assert;
use function count;
use function in_array;
use function max;
use function preg_match_all;
use function sprintf;
use function strlen;
use function substr_replace;

use const ARRAY_FILTER_USE_BOTH;
use const PREG_OFFSET_CAPTURE;
use const PREG_SET_ORDER;

/**
 * This class was heavily inspired by {@link https://github.com/phpstan/phpstan-src/blob/c471c7b050e0929daf432288770de673b394a983/src/Rules/Functions/PrintfParametersRule.php PHPStan}.
 * Part of this file therefore belongs to the work of Ondřej Mirtes.
 */
final class TemplatedStringParser
{
    private const ARGUMENT_SCAN_REGEX_PATTERN_PREFIX       = '(?<before>%*)%(?:(?<position>\d+)\$)?[-+]?(?:[ 0]|'
    . '(?:\'[^%]))?-?\d*(?:\.\d*)?';
    private const PRINTF_SPECIFIERS_REGEX_PATTERN_TEMPLATE = '[bcdeEfFgGosuxX%s]';
    private const SCANF_SPECIFIERS_REGEX_PATTERN_TEMPLATE  = '(?:[cdDeEfinosuxX%s]|\[[^\]]+\])';
    private const SPECIFIER_SINCE_PHP8                     = 'hH';

    private string $templateWithoutPlaceholder;

    /** @psalm-var array<positive-int,Placeholder> */
    private array $placeholders;

    private string $template;

    /**
     * @psalm-param positive-int $phpVersion
     */
    private function __construct(
        string $functionName,
        string $template,
        int $phpVersion
    ) {
        $this->template                   = $template;
        $this->templateWithoutPlaceholder = $template;
        $this->placeholders               = [];
        $this->parse($functionName, $template, $phpVersion);
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

        /** @var array<positive-int,Placeholder> $placeholderInstances */
        $placeholderInstances         = [];
        $removedCharacters            = 0;
        $maximumOrdinalPosition       = 1;
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

            Assert::positiveInteger($placeholderPosition);
            assert($placeholderValue !== '');

            $initialPlaceholderInstance = $placeholderInstances[$placeholderPosition] ?? null;
            $placeholderInstance        = Placeholder::create(
                $placeholderValue,
                $placeholderPosition
            );

            if ($initialPlaceholderInstance !== null) {
                $placeholderInstance = $initialPlaceholderInstance
                    ->withRepeatedPlaceholder($placeholderInstance);
            }

            $placeholderInstances[$placeholderPosition] = $placeholderInstance;
        }

        $this->placeholders               = $placeholderInstances;
        $this->templateWithoutPlaceholder = $templateWithoutPlaceholders;
    }

    /** @psalm-param positive-int $phpVersion */
    public static function fromArgument(
        string $functionName,
        Arg $templateArgument,
        Context $context,
        int $phpVersion
    ): self {
        return new self(
            $functionName,
            ArgumentValueParser::create($templateArgument->value, $context)->toString(),
            $phpVersion
        );
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function getTemplateWithoutPlaceholder(): string
    {
        return $this->templateWithoutPlaceholder;
    }

    /**
     * @psalm-return array<positive-int,Placeholder>
     */
    public function getPlaceholders(): array
    {
        return $this->placeholders;
    }

    public function getPlaceholderCount(): int
    {
        // TODO: normalize as %1$s is the same as %s, e.g.
        return count($this->placeholders);
    }
}
