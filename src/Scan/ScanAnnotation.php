<?php
/*
 * Copyright (c) 2023. ogg. Inc. All Rights Reserved.
 * ogg sit down and start building bugs in sunny weather.
 * Author: Ogg <baoziyoo@gmail.com>.
 * LastChangeTime: 2023-02-07 04:16:21
 * ChangeTime: 2023-04-26 10:21:30
 */

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO\Scan;

use Baoziyoo\Hyperf\ApiDocs\Annotation\ApiResponse;
use Baoziyoo\Hyperf\DTO\Annotation\Contracts\RequestBody;
use Baoziyoo\Hyperf\DTO\Annotation\Contracts\RequestFormData;
use Baoziyoo\Hyperf\DTO\Annotation\Contracts\RequestHeader;
use Baoziyoo\Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Baoziyoo\Hyperf\DTO\Exception\DtoException;
use Baoziyoo\Hyperf\DTO\JsonMapper;
use Baoziyoo\Hyperf\DTO\Validation\Annotation\Contracts\Valid;
use Hyperf\Di\Annotation\AnnotationReader;
use Hyperf\Di\MethodDefinitionCollectorInterface;
use Hyperf\Di\ReflectionManager;
use Hyperf\Di\ReflectionType;
use Psr\Container\ContainerInterface;
use ReflectionParameter;
use ReflectionProperty;
use Throwable;
use ReflectionClass;

class ScanAnnotation extends JsonMapper
{
    private static array $scanClassArray = [];

    public function __construct(
        private readonly ContainerInterface                 $container,
        private readonly MethodDefinitionCollectorInterface $methodDefinitionCollector
    )
    {
    }

    /**
     * 扫描控制器中的方法.
     */
    public function scan(string $className, string $methodName): void
    {
        $this->setMethodParameters($className, $methodName);
        $definitionArr = $this->methodDefinitionCollector->getParameters($className, $methodName);
        $class = new ReflectionClass($className);
        $action = $class->getMethod($methodName);
        $methodAnnotation = (new AnnotationReader())->getMethodAnnotation($action, ApiResponse::class);
        if (isset($methodAnnotation->type)) {
            $definitionArr[] = new ReflectionType($methodAnnotation->type);
        }
        foreach ($definitionArr as $definition) {
            $parameterClassName = $definition->getName();
            if ($this->container->has($parameterClassName)) {
                $this->scanClass($parameterClassName);
            }
        }
    }

    public function clearScanClassArray(): void
    {
        self::$scanClassArray = [];
    }

    /**
     * 扫描类.
     */
    public function scanClass(string $className, ?string $parentClassName = null, ?string $parentFieldName = null): void
    {
        if (in_array($className, self::$scanClassArray, true)) {
            return;
        }

        self::$scanClassArray[] = $className;
        $rc = ReflectionManager::reflectClass($className);
        $strNs = $rc->getNamespaceName();
        foreach ($rc->getProperties() ?? [] as $reflectionProperty) {
            $fieldName = $reflectionProperty->getName();
            $isSimpleType = true;
            $phpSimpleType = $propertyClassName = $arrSimpleType = $arrClassName = null;
            $type = $this->getTypeName($reflectionProperty);
            // php简单类型
            if ($this->isSimpleType($type)) {
                $phpSimpleType = $type;
            }
            // 数组类型
            $propertyEnum = PropertyEnum::get($type);
            if ($type === 'array') {
                $docblock = $reflectionProperty->getDocComment();
                $annotations = $this->parseAnnotationsNew($rc, $reflectionProperty, $docblock);
                if (!empty($annotations)) {
                    // support "@var type description"
                    [$varType] = explode(' ', $annotations['var'][0]);
                    $varType = $this->getFullNamespace($varType, $strNs);
                    // 数组类型
                    if ($this->isArrayOfType($varType)) {
                        $isSimpleType = false;
                        $arrType = substr($varType, 0, -2);
                        // 数组的简单类型 eg: int[]  string[]
                        if ($this->isSimpleType($arrType)) {
                            $arrSimpleType = $arrType;
                        } elseif (class_exists($arrType)) {
                            $arrClassName = $arrType;
                            PropertyManager::setNotSimpleClass($className);
                            $this->scanClass($arrType, $className, $fieldName);
                        }
                    }
                }
            } elseif ($propertyEnum) {
                $isSimpleType = false;
                PropertyManager::setNotSimpleClass($className);
            } elseif (class_exists($type)) {
                $this->scanClass($type, $className, $fieldName);
                $isSimpleType = false;
                $propertyClassName = $type;
                PropertyManager::setNotSimpleClass($className);
            }

            $property = PropertyManager::getProperty($className, $fieldName);
            if ($property === null) {
                $property = new Property();
            }
            $property->phpSimpleType = $phpSimpleType;
            $property->isSimpleType = $isSimpleType;
            $property->arrSimpleType = $arrSimpleType;
            $property->arrClassName = $arrClassName ? trim($arrClassName, '\\') : null;
            $property->className = $propertyClassName ? trim($propertyClassName, '\\') : null;
            $property->enum = $propertyEnum;
            $property->parentClassName = $parentClassName ? trim($parentClassName, '\\') : null;
            $property->parentFieldName = $parentFieldName ?? null;
            $property->currentClassName = $className ? trim($className, '\\') : null;;
            PropertyManager::setProperty($className, $fieldName, $property);

            if ($parentClassName && $parentFieldName) {
                $parentProperty = PropertyManager::getProperty($parentClassName, $parentFieldName);
                if ($parentProperty === null) {
                    $parentProperty = new Property();
                }
                $parentProperty->children[$fieldName] = $property;
                PropertyManager::setProperty($parentClassName, $parentFieldName, $parentProperty);
            }

            if (!$parentClassName && !$parentFieldName) {
                $this->registerValidation($className, $fieldName);
            }
        }
    }

    protected function registerValidation(string $className, string $fieldName): void
    {
    }

    /**
     * 获取PHP类型.
     */
    protected function getTypeName(ReflectionProperty $rp): string
    {
        try {
            $type = $rp->getType()->getName();
        } catch (Throwable) {
            $type = 'string';
        }

        return $type;
    }

    /**
     * 设置方法中的参数.
     */
    private function setMethodParameters(string $className, string $methodName): void
    {
        // 获取方法的反射对象
        $ref = ReflectionManager::reflectMethod($className, $methodName);
        // 获取方法上指定名称的全部注解
        /** @var ReflectionParameter $attributes */
        $attributes = $ref->getParameters();
        $methodMark = $headerMark = $total = 0;

        foreach ($attributes as $attribute) {
            $methodParameters = new MethodParameter();
            $paramName = $attribute->getName();
            $mark = 0;

            if ($attribute->getAttributes(RequestQuery::class)) {
                $methodParameters->setIsRequestQuery(true);
                ++$mark;
                ++$total;
            }
            if ($attribute->getAttributes(RequestFormData::class)) {
                $methodParameters->setIsRequestFormData(true);
                ++$mark;
                ++$methodMark;
                ++$total;
            }
            if ($attribute->getAttributes(RequestBody::class)) {
                $methodParameters->setIsRequestBody(true);
                ++$mark;
                ++$methodMark;
                ++$total;
            }
            if ($attribute->getAttributes(RequestHeader::class)) {
                $methodParameters->setIsRequestHeader(true);
                ++$headerMark;
                ++$total;
            }
            if (class_exists(Valid::class) && $attribute->getAttributes(Valid::class)) {
                $methodParameters->setIsValid(true);
            }
            if ($mark > 1) {
                throw new DtoException("Parameter annotation [RequestQuery RequestFormData RequestBody] cannot exist simultaneously [{$className}::{$methodName}:{$paramName}]");
            }
            if ($headerMark > 1) {
                throw new DtoException("Parameter annotation [RequestHeader] can only exist [{$className}::{$methodName}:{$paramName}]");
            }
            if ($total > 0) {
                MethodParametersManager::setContent($className, $methodName, $paramName, $methodParameters);
            }
        }

        if ($methodMark > 1) {
            throw new DtoException("Method annotation [RequestFormData RequestBody] cannot exist simultaneously [{$className}::{$methodName}]");
        }
    }
}
