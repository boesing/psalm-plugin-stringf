<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\Psalm;

use Psalm\Codebase;
use Psalm\StatementsSource;
use Webmozart\Assert\Assert;

final class PhpVersion
{
    /** @psalm-var positive-int */
    private int $major;

    /** @psalm-var 0|positive-int */
    private int $minor;

    /**
     * @psalm-param positive-int $major
     * @psalm-param 0|positive-int $minor
     */
    private function __construct(int $major, int $minor)
    {
        $this->major = $major;
        $this->minor = $minor;
    }

    /**
     * @psalm-return positive-int
     */
    public static function fromCodebase(Codebase $codebase): int
    {
        $major = $codebase->php_major_version;
        Assert::positiveInteger($major);
        $minor = $codebase->php_minor_version;
        Assert::greaterThanEq($minor, 0);
        /** @psalm-var 0|positive-int $minor */

        return (new self($major, $minor))->toVersionId();
    }

    /**
     * @psalm-return positive-int
     */
    private function toVersionId(): int
    {
        return $this->major * 10000 + $this->minor * 100;
    }

    /**
     * @psalm-return positive-int
     */
    public static function fromStatementSource(StatementsSource $statementsSource): int
    {
        return self::fromCodebase($statementsSource->getCodebase());
    }
}
