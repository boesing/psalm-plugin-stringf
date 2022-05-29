<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\ArgumentValidator;

use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;

final class ScanfArgumentValidator implements ArgumentValidatorInterface
{
    private ArgumentValidatorInterface $printfArgumentValidator;

    public function __construct()
    {
        $this->printfArgumentValidator = new StringfArgumentValidator(2);
    }

    public function validate(TemplatedStringParser $templatedStringParser, array $arguments): ArgumentValidationResult
    {
        $result = $this->printfArgumentValidator->validate($templatedStringParser, $arguments);
        if ($result->valid()) {
            return $result;
        }

        if ($result->actualArgumentCount !== 0) {
            return $result;
        }

        // sscanf and fscanf can return the arguments in case no arguments are passed
        return new ArgumentValidationResult(
            0,
            0
        );
    }
}
