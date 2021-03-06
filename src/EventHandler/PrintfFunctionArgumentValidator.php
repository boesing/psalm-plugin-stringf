<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\EventHandler;

use Boesing\PsalmPluginStringf\ArgumentValidator\ArgumentValidator;
use Boesing\PsalmPluginStringf\ArgumentValidator\StringfArgumentValidator;

use function in_array;

final class PrintfFunctionArgumentValidator extends FunctionArgumentValidator
{
    private const FUNCTIONS = [
        'sprintf',
        'printf',
    ];

    private const TEMPLATE_ARGUMENT_INDEX = 0;

    private const ISSUE_TEMPLATE = 'Template passed to function `%s` requires %d specifier but %d are passed.';

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
        return new StringfArgumentValidator(1);
    }
}
