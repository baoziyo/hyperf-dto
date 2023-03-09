<?php

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO\Scan;

class MethodParametersManager
{
    /** @var array<string,MethodParameter> */
    public static array $content = [];

    public static function setContent(string $className, string $methodName, string $paramName, MethodParameter $method): void
    {
        $className = trim($className, '\\');
        $key = $className . $methodName . $paramName;

        if (!isset(static::$content[$key])) {
            static::$content[$key] = $method;
        }
    }

    public static function getMethodParameter(string $className, string $methodName, string $paramName): null|MethodParameter
    {
        $className = trim($className, '\\');
        $key = $className . $methodName . $paramName;

        return static::$content[$key] ?? null;
    }
}
