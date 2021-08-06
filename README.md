# Psalm Plugin Stringf

[![Build Status](https://github.com/boesing/psalm-plugin-stringf/actions/workflows/continous-integration.yml/badge.svg)](https://github.com/boesing/psalm-plugin-stringf/actions/workflows/continous-integration.yml)

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
        <ReportPossiblyInvalidArgumentForSpecifier/>
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
printf('%s', 1.035);
```

```
PossiblyInvalidArgument: Argument 1 inferred as "float" does not match (any of) the suggested type(s) "string"
```
