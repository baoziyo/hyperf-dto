<?php
/*
 * Copyright (c) 2023. ogg. Inc. All Rights Reserved.
 * ogg sit down and start building bugs in sunny weather.
 * Author: Ogg <baoziyoo@gmail.com>.
 * LastChangeTime: 2023-01-16 06:16:27
 * ChangeTime: 2023-04-26 10:21:30
 */

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
