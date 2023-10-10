<?php

declare(strict_types=1);

namespace Boesing\PsalmPluginStringf\Parser\Psalm;

use Psalm\Type\Union;

final class TypeParser
{
    private function __construct(private Union $type)
    {
    }

    public static function create(Union $type): self
    {
        return new self($type);
    }

    public function stringify(): string|null
    {
        $type = $this->type;

        if ($type->isNull()) {
            return '';
        }

        if ($type->isSingleIntLiteral()) {
            return (string) $type->getSingleIntLiteral()->value;
        }

        if ($type->isSingleStringLiteral()) {
            return $type->getSingleStringLiteral()->value;
        }

        if ($type->isFloat()) {
            if ($type->isSingleFloatLiteral()) {
                return FloatVariableParser::stringify($type);
            }

            return null;
        }

        if ($type->isTrue()) {
            return '1';
        }

        if ($type->isFalsable()) {
            return '';
        }

        return null;
    }
}
