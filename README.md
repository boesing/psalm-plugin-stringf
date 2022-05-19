# Psalm Plugin Stringf

[![Build Status](https://github.com/boesing/psalm-plugin-stringf/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/boesing/psalm-plugin-stringf/actions/workflows/continuous-integration.yml)

This plugin provides additional checks to the built-in `sprintf`, `printf`, `sscanf` and `fscanf` function usage.

## Installation

### Require composer dev-dependency

```
composer require --dev boesing/psalm-plugin-stringf
```

### Run Psalm-Plugin Binary

```
vendor/bin/psalm-plugin enable boesing/psalm-plugin-stringf
```

## Features

- Parses `sprintf` and `printf` arguments to verify if the number of passed arguments matches the amount of specifiers
- Verifies if the return value of `sprintf` might be a `non-empty-string`
- Verifies possibly invalid argument of `sprintf` and `printf` ([experimental](#report-possibly-invalid-argument-for-specifier))
- Verifies unnecessary function calls of `sprintf` and `printf` ([experimental](#report-unnecessary-function-calls))

## Experimental

This plugin also provides experimental features.

Experimental features can be enabled by extending the plugin configuration as follows:

```xml
<?xml version="1.0"?>
<psalm>
    <plugins>
        <pluginClass class="Boesing\PsalmPluginStringf\Plugin">
            <experimental>
                <NameOfExperimentalFeature/>
            </experimental>
        </pluginClass>
    </plugins>
</psalm>
```

### Report Possibly Invalid Argument for Specifier

```xml
<pluginClass class="Boesing\PsalmPluginStringf\Plugin">
    <experimental>
        <ReportPossiblyInvalidArgumentForSpecifier allowIntegerForString="yes" />
    </experimental>
</pluginClass>
```

The `ReportPossiblyInvalidArgumentForSpecifier` experimental feature will report `PossiblyInvalidArgument` errors for
arguments used with `sprintf` or `printf`. Here are some examples:

```php
printf('%s', 1);
```

```
PossiblyInvalidArgument: Argument 1 inferred as "int" does not match (any of) the suggested type(s) "string"
```


```php
printf('%d', 'foo');
```

```
PossiblyInvalidArgument: Argument 1 inferred as "string" does not match (any of) the suggested type(s) "float\|int\|numeric-string"
```

### Report Unnecessary Function Calls

```xml
<pluginClass class="Boesing\PsalmPluginStringf\Plugin">
    <experimental>
        <ReportUnnecessaryFunctionCalls/>
    </experimental>
</pluginClass>
```

The `ReportUnnecessaryFunctionCalls` experimental feature will report `UnnecessaryFunctionCall` errors for
function calls to `sprintf` or `printf` which can be omitted. Here are some examples:

```php
printf('Some text without any placeholder');
sprintf('Some text without any placeholder');
```

```
UnnecessaryFunctionCall: Function call is unnecessary as there is no placeholder within the template.
```


## Release Versioning Disclaimer

This plugin won't follow semantic versioning even tho the version numbers state to be semantic versioning compliant.
The source code of this plugin is not meant to used like library code and therefore **MUST** be treated as internal code.
- This package will raise dependency requirements whenever necessary.
- If there is a new major version of psalm, this plugin **MAY** migrate to that version but won't be early adopter.
- If there is a new PHP minor/major version which is not supported by this library, this library **MAY** migrate to that version but won't be early adopter.

So to summarize: If your project depends on the latest shiny versions of either Psalm or PHP, this plugin is not for you. If you can live with that, feel free to install. Demands in any way will be either ignored or handled whenever I feel I want to spend time on it.
