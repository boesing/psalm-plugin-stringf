<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\Psalm;

use Psalm\Codebase;
use Psalm\StatementsSource;
use Webmozart\Assert\Assert;

final class PhpVersion
{
    /** @var positive-int */
    public int $versionId;

    /**
     * @param positive-int $versionId
     */
    private function __construct(int $versionId)
    {
        $this->versionId = $versionId;
    }

    public static function fromCodebase(Codebase $codebase): self
    {
        $versionId = $codebase->analysis_php_version_id;
        Assert::positiveInteger($versionId);

        return new self($versionId);
    }

    public static function fromStatementSource(StatementsSource $statementsSource): self
    {
        return self::fromCodebase($statementsSource->getCodebase());
    }
}
