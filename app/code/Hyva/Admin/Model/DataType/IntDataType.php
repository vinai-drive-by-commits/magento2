<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeInterface;

class IntDataType implements DataTypeInterface
{
    public const TYPE_INT = 'int';

    public function valueToTypeCode($value): ?string
    {
        return is_int($value) || (is_string($value) && preg_match('/^\d+$/', $value))
            ? self::TYPE_INT
            : null;
    }

    public function typeToTypeCode(string $type): ?string
    {
        return $type === self::TYPE_INT
            ? self::TYPE_INT
            : null;
    }

    public function toString($value): ?string
    {
        return $this->valueToTypeCode($value)
            ? (string) $value
            : null;
    }

    public function toHtmlRecursive($value, $maxRecursionDepth = self::UNLIMITED_RECURSION): ?string
    {
        return $this->toString($value);
    }
}
