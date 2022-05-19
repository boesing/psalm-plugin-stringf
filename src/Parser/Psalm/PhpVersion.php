<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\Psalm;

use Psalm\Codebase;
use Psalm\StatementsSource;
use Webmozart\Assert\Assert;

final class PhpVersion
{
    /**
     * @psalm-return positive-int
     */
    public static function fromCodebase(Codebase $codebase): int
    {
        $versionId = $codebase->analysis_php_version_id;
        Assert::positiveInteger($versionId);

        return $versionId;
    }

    /**
     * @psalm-return positive-int
     */
    public static function fromStatementSource(StatementsSource $statementsSource): int
    {
        return self::fromCodebase($statementsSource->getCodebase());
    }
}
