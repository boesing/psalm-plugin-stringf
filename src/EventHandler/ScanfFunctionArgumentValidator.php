<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\EventHandler;

use Boesing\PsalmPluginStringf\ArgumentValidator\ArgumentValidator;
use Boesing\PsalmPluginStringf\ArgumentValidator\ScanfArgumentValidator;

use function in_array;

final class ScanfFunctionArgumentValidator extends AbstractFunctionArgumentValidator
{
    private const FUNCTIONS = [
        'sscanf',
        'fscanf',
    ];

    private const TEMPLATE_ARGUMENT_INDEX = 1;

    private const ISSUE_TEMPLATE = 'Template passed to function `%s` declares %d specifier but only %d  argument is passed.';

    protected function getTemplateArgumentIndex(): int
    {
        return self::TEMPLATE_ARGUMENT_INDEX;
    }

    protected function getIssueTemplate(): string
    {
        return self::ISSUE_TEMPLATE;
    }

    protected function canHandleFunction(string $functionId): bool
    {
        return in_array($functionId, self::FUNCTIONS, true);
    }

    protected function getArgumentValidator(): ArgumentValidator
    {
        return new ScanfArgumentValidator();
    }
}
