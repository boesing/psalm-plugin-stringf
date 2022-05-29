<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\ArgumentValidator;

use Boesing\PsalmPluginStringf\Parser\TemplatedStringParser\TemplatedStringParser;
use PhpParser\Node\Arg;
use PhpParser\Node\VariadicPlaceholder;

interface ArgumentValidatorInterface
{
    /**
     * @param array<Arg|VariadicPlaceholder> $arguments
     */
    public function validate(TemplatedStringParser $templatedStringParser, array $arguments): ArgumentValidationResult;
}
