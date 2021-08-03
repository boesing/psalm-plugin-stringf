<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf;

use Boesing\PsalmPluginStringf\EventHandler\PossiblyInvalidArgumentForSpecifierValidator;
use Boesing\PsalmPluginStringf\EventHandler\SprintfFunctionReturnProvider;
use Boesing\PsalmPluginStringf\EventHandler\StringfFunctionArgumentValidator;
use Psalm\Exception\ConfigCreationException;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

use function assert;
use function basename;
use function sprintf;
use function str_replace;

final class Plugin implements PluginEntryPointInterface
{
    private const CONFIGURATION_FEATURE_ELEMENT = 'feature';
    private const FEATURE_TO_EVENT_HANDLER      = [
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

        $this->registerHooksForConfiguration($registration, $config);
    }

    private function registerHooksForConfiguration(RegistrationInterface $registration, SimpleXMLElement $config): void
    {
        foreach ($config->feature ?? [] as $element) {
            assert($element instanceof SimpleXMLElement);
            if ($element->getName() !== self::CONFIGURATION_FEATURE_ELEMENT) {
                continue;
            }

            $this->registerFeatureHook($registration, $element);
        }
    }

    private function registerFeatureHook(RegistrationInterface $registration, SimpleXMLElement $element): void
    {
        $nameAttribute    = $element['name'] ?? null;
        $enabledAttribute = $element['enabled'] ?? null;
        if (! $nameAttribute instanceof SimpleXMLElement || ! $enabledAttribute instanceof SimpleXMLElement) {
            throw new ConfigCreationException(
                'Configuration is invalid! Missing `name` and/or `enabled` in one or more `<feature>`-Nodes.'
            );
        }

        $enabled = ((string) $enabledAttribute) === 'true';
        $name    = (string) $nameAttribute;

        $eventHandlerClassName = self::FEATURE_TO_EVENT_HANDLER[$name] ?? null;
        if ($eventHandlerClassName === null || ! $enabled) {
            return;
        }

        /** @psalm-suppress UnresolvableInclude */
        require_once __DIR__ . sprintf(
            '/EventHandler/%s.php',
            basename(str_replace('\\', '/', $eventHandlerClassName))
        );
        $registration->registerHooksFromClass($eventHandlerClassName);
    }
}
