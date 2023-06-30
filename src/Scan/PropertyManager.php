<?php
/*
 * Copyright (c) 2023. ogg. Inc. All Rights Reserved.
 * ogg sit down and start building bugs in sunny weather.
 * Author: Ogg <baoziyoo@gmail.com>.
 * LastChangeTime: 2023-01-17 09:58:11
 * ChangeTime: 2023-04-26 10:21:30
 */

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO\Scan;

class PropertyManager
{
    /** @var array<string,Property> */
    protected static array $content = [];

    /** @var array<string,bool> */
    protected static array $notSimpleClass = [];

    public static function getAll(): array
    {
        return [static::$content, static::$notSimpleClass];
    }

    public static function setNotSimpleClass($className): void
    {
        $className = trim($className, '\\');

        static::$notSimpleClass[$className] = true;
    }

    /**
     * 设置类中字段的属性.
     */
    public static function setProperty(string $className, string $fieldName, Property $property): void
    {
        $className = trim($className, '\\');
        if (!isset(static::$content[$className][$fieldName])) {
            static::$content[$className][$fieldName] = $property;
        }
    }

    /**
     * 获取类中字段的属性.
     * @param $className
     * @param $fieldName
     * @return null|Property
     */
    public static function getProperty($className, $fieldName): ?Property
    {
        $className = trim($className, '\\');

        return static::$content[$className][$fieldName] ?? null;
    }

    /**
     * @param $className
     * @return Property[]
     */
    public static function getPropertyAndNotSimpleType($className): array
    {
        $className = trim($className, '\\');
        if (!isset(static::$notSimpleClass[$className])) {
            return [];
        }
        $data = [];
        foreach (static::$content[$className] ?? [] as $fieldName => $property) {
            if (!$property->isSimpleType) {
                $data[$fieldName] = $property;
            }
        }
        return $data;
    }
}
