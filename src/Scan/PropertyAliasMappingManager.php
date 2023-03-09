<?php

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO\Scan;

class PropertyAliasMappingManager
{
    /** @var array<string,string> */
    protected static array $content = [];

    /** @var array<string,bool> */
    protected static array $aliasMappingClassname = [];

    protected static bool $isAliasMapping = false;

    public static function setAliasMapping(string $classname, string $alias, string $propertyName): void
    {
        static::$content[$alias] = $propertyName;
        static::$aliasMappingClassname[$classname] = true;
        static::$isAliasMapping = true;
    }

    public static function getAliasMapping(string $alias): ?string
    {
        return static::$content[$alias] ?? null;
    }

    public static function isAliasMappingClassname(string $classname): bool
    {
        return isset(static::$aliasMappingClassname[$classname]);
    }

    public static function isAliasMapping(): bool
    {
        return static::$isAliasMapping;
    }
}
