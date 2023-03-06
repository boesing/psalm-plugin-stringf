<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Psalm\Issue;

use Psalm\CodeLocation;
use Psalm\Issue\PluginIssue;

use function strtolower;

final class TooFewArguments extends PluginIssue
{
    public ?string $function_id;

    public function __construct(
        string $message,
        CodeLocation $code_location,
        ?string $function_id = null
    ) {
        parent::__construct($message, $code_location);

        $this->function_id = $function_id ? strtolower($function_id) : null;
    }
}
