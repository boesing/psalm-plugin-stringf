<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Issue\PluginIssue;

final class UnnecessaryFunctionCall extends PluginIssue
{
    public string $function_id;

    public function __construct(CodeLocation $code_location, string $function_id)
    {
        parent::__construct(
            'Function call is unnecessary as there is no placeholder within the template.',
            $code_location,
        );

        $this->function_id = $function_id;
    }
}
