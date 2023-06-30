<?php
/*
 * Copyright (c) 2023. ogg. Inc. All Rights Reserved.
 * ogg sit down and start building bugs in sunny weather.
 * Author: Ogg <baoziyoo@gmail.com>.
 * LastChangeTime: 2023-02-03 05:40:04
 * ChangeTime: 2023-04-26 10:21:30
 */

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO\Scan;

use BackedEnum;
use ReflectionEnum;

class PropertyEnum
{
    /** @var null|string 返回的类型 */
    public ?string $backedType = null;

    /** @var null|string 名称 */
    public ?string $className = null;

    /** @var null|array<string> 枚举类 value列表. */
    public ?array $valueList = null;

    public static function get(string $className): ?PropertyEnum
    {
        /* @phpstan-ignore-next-line */
        if (PHP_VERSION_ID < 80100 || !is_subclass_of($className, BackedEnum::class)) {
            return null;
        }

        $propertyEnum = new self();
        try {
            $rEnum = new ReflectionEnum($className);
            $propertyEnum->backedType = (string)$rEnum->getBackingType();
        } catch (\ReflectionException) {
            $propertyEnum->backedType = 'string';
        }

        $propertyEnum->className = trim($className, '\\');
        $propertyEnum->valueList = collect($className::cases())->map(fn ($v) => $v->value)->all();

        return $propertyEnum;
    }
}
