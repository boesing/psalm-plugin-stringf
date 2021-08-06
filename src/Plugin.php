<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf;

use Boesing\PsalmPluginStringf\EventHandler\PossiblyInvalidArgumentForSpecifierValidator;
use Boesing\PsalmPluginStringf\EventHandler\SprintfFunctionReturnProvider;
use Boesing\PsalmPluginStringf\EventHandler\StringfFunctionArgumentValidator;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

use function assert;
use function basename;
use function file_exists;
use function sprintf;
use function str_replace;

final class Plugin implements PluginEntryPointInterface
{
    private const EXPERIMENTAL_FEATURES = [
        'ReportPossiblyInvalidArgumentForSpecifier' => PossiblyInvalidArgumentForSpecifierValidator::class,
    ];

    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/EventHandler/SprintfFunctionReturnProvider.php';
        require_once __DIR__ . '/EventHandler/StringfFunctionArgumentValidator.php';
        $registration->registerHooksFromClass(SprintfFunctionReturnProvider::class);
        $registration->registerHooksFromClass(StringfFunctionArgumentValidator::class);

        if ($config === null) {
            return;
        }

        $this->registerExperimentalHooks($registration, $config);
    }

    private function registerExperimentalHooks(RegistrationInterface $registration, SimpleXMLElement $config): void
    {
        if (! $config->experimental instanceof SimpleXMLElement) {
            return;
        }

        foreach ($config->experimental->children() as $element) {
            assert($element instanceof SimpleXMLElement);
            $name = $element->getName();
            if (! isset(self::EXPERIMENTAL_FEATURES[$name])) {
                continue;
            }

            $this->registerFeatureHook($registration, $name);
        }
    }

    private function registerFeatureHook(RegistrationInterface $registration, string $featureName): void
    {
        $eventHandlerClassName = self::EXPERIMENTAL_FEATURES[$featureName];

        $fileName =  __DIR__ . sprintf(
            '/EventHandler/%s.php',
            basename(str_replace('\\', '/', $eventHandlerClassName))
        );
        assert(file_exists($fileName));
        require_once $fileName;

        $registration->registerHooksFromClass($eventHandlerClassName);
    }
}
