<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf;

use Boesing\PsalmPluginStringf\EventHandler\SprintfFunctionReturnProvider;
use Boesing\PsalmPluginStringf\EventHandler\StringfFunctionArgumentValidator;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

final class Plugin implements PluginEntryPointInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/EventHandler/SprintfFunctionReturnProvider.php';
        require_once __DIR__ . '/EventHandler/StringfFunctionArgumentValidator.php';
        $registration->registerHooksFromClass(SprintfFunctionReturnProvider::class);
        $registration->registerHooksFromClass(StringfFunctionArgumentValidator::class);
    }
}
