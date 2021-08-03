<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\TemplatedStringParser;

use InvalidArgumentException;
use Psalm\Type;
use Webmozart\Assert\Assert;

use function assert;
use function preg_match;
use function sprintf;
use function strlen;

final class SpecifierTypeGenerator
{
    private const KNOWN_SPECIFIERS = 'bcdeEfFgGosuxXhHDin';

    /** @psalm-var non-empty-string */
    private string $specifier;

    /**
     * @psalm-param non-empty-string $specifier
     */
    private function __construct(string $specifier)
    {
        $this->specifier = $this->parse($specifier);
    }

    /**
     * @psalm-param non-empty-string $specifier
     */
    public static function create(string $specifier): self
    {
        return new self($specifier);
    }

    public function getSuggestedType(): Type\Union
    {
        switch ($this->specifier) {
            case 's':
                return Type::getString();

            case 'd':
            case 'f':
                return $this->numeric();

            default:
                throw new InvalidArgumentException(sprintf('Specifier "%s" is not yet supported.', $this->specifier));
        }
    }

    /** @psalm-return non-empty-string */
    private function parse(string $specifier): string
    {
        if (strlen($specifier) === 1) {
            Assert::contains(self::KNOWN_SPECIFIERS, $specifier);
            assert($specifier !== '');

            return $specifier;
        }

        preg_match(sprintf('#(?<specifier>[%s])#', self::KNOWN_SPECIFIERS), $specifier, $matches);
        if (! isset($matches['specifier'])) {
            throw new InvalidArgumentException(sprintf('Provided specifier %s is unknown!', $specifier));
        }

        $parsed = $matches['specifier'];
        Assert::stringNotEmpty($parsed);

        return $parsed;
    }

    private function numeric(): Type\Union
    {
        return new Type\Union([
            new Type\Atomic\TInt(),
            new Type\Atomic\TFloat(),
            new Type\Atomic\TNumericString(),
        ]);
    }
}
