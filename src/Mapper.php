<?php

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO;

use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Contracts\Arrayable;

class Mapper
{
    /** @var array<string,JsonMapper> */
    protected static array $jsonMapper = [];

    public static function map($json, object $object)
    {
        return static::getJsonMapper()->map($json, $object);
    }

    public static function mapDto($json, object $object)
    {
        return static::getJsonMapper()->mapDto($json, $object);
    }

    public static function copyProperties($source, object $target)
    {
        if ($source == null) {
            return null;
        }
        if ($source instanceof Arrayable) {
            return static::getJsonMapper()->map($source->toArray(), $target);
        }
        return static::getJsonMapper()->map($source, $target);
    }

    public static function mapArray($json, string $className)
    {
        if (empty($json)) {
            return [];
        }
        if ($json instanceof Arrayable) {
            return static::getJsonMapper()->mapArray($json->toArray(), [], $className);
        }
        return static::getJsonMapper()->mapArray($json, [], $className);
    }

    public static function getJsonMapper($key = 'default'): JsonMapper
    {
        if (!isset(static::$jsonMapper[$key])) {
            $jsonMapper = new JsonMapper();
            $logger = ApplicationContext::getContainer()->get(StdoutLoggerInterface::class);
            $jsonMapper->setLogger($logger);
            // 将数组传递给映射
            $jsonMapper->bEnforceMapType = false;
            $jsonMapper->bStrictNullTypes = false;
            static::$jsonMapper[$key] = $jsonMapper;
        }
        return static::$jsonMapper[$key];
    }
}
