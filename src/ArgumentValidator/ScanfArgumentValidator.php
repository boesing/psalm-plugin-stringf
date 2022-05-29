<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\ArgumentValidator;

use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;

final class ScanfArgumentValidator implements ArgumentValidatorInterface
{
    private ArgumentValidatorInterface $printfArgumentValidator;

    public function __construct(ArgumentValidatorInterface $printfArgumentValidator)
    {
        $this->printfArgumentValidator = $printfArgumentValidator;
    }

    public function validate(TemplatedStringParser $templatedStringParser, array $arguments): ArgumentValidationResult
    {
        return $this->printfArgumentValidator->validate($templatedStringParser, $arguments);
    }
}
