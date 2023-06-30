<?php
/*
 * Copyright (c) 2023. ogg. Inc. All Rights Reserved.
 * ogg sit down and start building bugs in sunny weather.
 * Author: Ogg <baoziyoo@gmail.com>.
 * LastChangeTime: 2023-01-17 09:59:00
 * ChangeTime: 2023-04-26 10:21:32
 */

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO;

use Hyperf\Di\Annotation\AnnotationCollector;

class ApiAnnotation
{
    public static function getProperty(string $className, string $propertyName, string $annotationClassName): ?object
    {
        $propertyAnnotations = AnnotationCollector::getClassPropertyAnnotation($className, $propertyName);

        return $propertyAnnotations[$annotationClassName] ?? null;
    }

    public static function getClassProperty(string $className, string $propertyName): array
    {
        return AnnotationCollector::getClassPropertyAnnotation($className, $propertyName) ?? [];
    }

    public static function classMetadata(string $className)
    {
        return AnnotationCollector::list()[$className]['_c'] ?? [];
    }

    public static function methodMetadata(string $className)
    {
        return AnnotationCollector::list()[$className]['_m'] ?? [];
    }

    public static function propertyMetadata(string $className)
    {
        return AnnotationCollector::list()[$className]['_p'] ?? [];
    }
}
