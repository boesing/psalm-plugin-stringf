<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf;

use Boesing\PsalmPluginStringf\EventHandler\PossiblyInvalidArgumentForSpecifierValidator;
use Boesing\PsalmPluginStringf\EventHandler\PrintfFunctionArgumentValidator;
use Boesing\PsalmPluginStringf\EventHandler\ScanfFunctionArgumentValidator;
use Boesing\PsalmPluginStringf\EventHandler\SprintfFunctionReturnProvider;
use Boesing\PsalmPluginStringf\EventHandler\UnnecessaryFunctionCallValidator;
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
        'ReportUnnecessaryFunctionCalls' => UnnecessaryFunctionCallValidator::class,
    ];

    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        require_once __DIR__ . '/EventHandler/SprintfFunctionReturnProvider.php';
        require_once __DIR__ . '/EventHandler/PrintfFunctionArgumentValidator.php';
        require_once __DIR__ . '/EventHandler/ScanfFunctionArgumentValidator.php';
        $registration->registerHooksFromClass(SprintfFunctionReturnProvider::class);
        $registration->registerHooksFromClass(PrintfFunctionArgumentValidator::class);
        $registration->registerHooksFromClass(ScanfFunctionArgumentValidator::class);

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

        foreach ($config->experimental->children() ?? [] as $element) {
            $name = $element->getName();
            if (! isset(self::EXPERIMENTAL_FEATURES[$name])) {
                continue;
            }

            $options = $this->extractOptionsFromElement($element);
            $this->registerFeatureHook($registration, $name, $options);
        }
    }

    /**
     * @param array<non-empty-string,string> $options
     */
    private function registerFeatureHook(
        RegistrationInterface $registration,
        string $featureName,
        array $options
    ): void {
        $eventHandlerClassName = self::EXPERIMENTAL_FEATURES[$featureName];

        $fileName =  __DIR__ . sprintf(
            '/EventHandler/%s.php',
            basename(str_replace('\\', '/', $eventHandlerClassName)),
        );
        assert(file_exists($fileName));
        require_once $fileName;

        $registration->registerHooksFromClass($eventHandlerClassName);
        if ($eventHandlerClassName !== PossiblyInvalidArgumentForSpecifierValidator::class) {
            return;
        }

        $eventHandlerClassName::applyOptions($options);
    }

    /**
     * @return array<non-empty-string,string>
     */
    private function extractOptionsFromElement(SimpleXMLElement $element): array
    {
        $options = [];

        foreach ($element->attributes() ?? [] as $attribute) {
            $name = $attribute->getName();
            assert($name !== '');

            $options[$name] = (string) $attribute;
        }

        return $options;
    }
}
