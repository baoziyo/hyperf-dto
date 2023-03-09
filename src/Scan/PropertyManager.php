<?php

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
