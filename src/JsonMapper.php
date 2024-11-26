<?php
/*
 * Copyright (c) 2023. ogg. Inc. All Rights Reserved.
 * ogg sit down and start building bugs in sunny weather.
 * Author: Ogg <baoziyoo@gmail.com>.
 * LastChangeTime: 2023-02-03 05:42:21
 * ChangeTime: 2023-04-26 10:21:32
 */

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO;

use BackedEnum;
use Baoziyoo\Hyperf\DTO\Annotation\ArrayType;
use Baoziyoo\Hyperf\DTO\Scan\PropertyAliasMappingManager;
use InvalidArgumentException;
use JsonMapper_Exception;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Types\ContextFactory;
use Psr\Http\Message\UploadedFileInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

class JsonMapper extends \JsonMapper
{
    /**
     * Checks if the given type is a "simple type"
     *
     * @param string $type type name from gettype()
     *
     * @return boolean True if it is a simple PHP type
     *
     * @see isFlatType()
     */
    protected function isSimpleType($type)
    {
        return $type == 'string'
            || $type == 'boolean' || $type == 'bool'
            || $type == 'integer' || $type == 'int'
            || $type == 'double' || $type == 'float'
            || $type == 'array' || $type == 'object'
            || $type == UploadedFileInterface::class;
    }

    /**
     * Map data all data in $json into the given $object instance.
     *
     * @param array|object $json JSON object structure from json_decode()
     * @param object $object Object to map $json data into
     *
     * @return mixed mapped object is returned
     * @see    mapArray()
     */
    public function mapDto($json, $object)
    {
        if ($this->bEnforceMapType && !is_object($json)) {
            throw new InvalidArgumentException(
                'JsonMapper::map() requires first argument to be an object'
                . ', ' . gettype($json) . ' given.'
            );
        }
        if (!is_object($object)) {
            throw new InvalidArgumentException(
                'JsonMapper::map() requires second argument to be an object'
                . ', ' . gettype($object) . ' given.'
            );
        }

        $strClassName = get_class($object);
        $rc = new ReflectionClass($object);
        $strNs = $rc->getNamespaceName();
        $providedProperties = [];
        foreach ($json as $key => $jvalue) {
            // 修改
            $key = $this->aliasMapping($strClassName, $key);
            $key = $this->getSafeName($key);
            $providedProperties[$key] = true;

            // Store the property inspection results so we don't have to do it
            // again for subsequent objects of the same type
            if (!isset($this->arInspectedClasses[$strClassName][$key])) {
                $this->arInspectedClasses[$strClassName][$key]
                    = $this->inspectProperty($rc, $key);
            }

            [$hasProperty, $accessor, $type, $isNullable]
                = $this->arInspectedClasses[$strClassName][$key];

            if (!$hasProperty) {
                if ($this->bExceptionOnUndefinedProperty) {
                    throw new JsonMapper_Exception(
                        'JSON property "' . $key . '" does not exist'
                        . ' in object of type ' . $strClassName
                    );
                }
                if ($this->undefinedPropertyHandler !== null) {
                    call_user_func(
                        $this->undefinedPropertyHandler,
                        $object,
                        $key,
                        $jvalue
                    );
                } else {
                    $this->log(
                        'debug',
                        'Property {property} does not exist in {class}',
                        ['property' => $key, 'class' => $strClassName]
                    );
                }
                continue;
            }

            if ($accessor === null) {
                if ($this->bExceptionOnUndefinedProperty) {
                    throw new JsonMapper_Exception(
                        'JSON property "' . $key . '" has no public setter method'
                        . ' in object of type ' . $strClassName
                    );
                }
                $this->log(
                    'debug',
                    'Property {property} has no public setter method in {class}',
                    ['property' => $key, 'class' => $strClassName]
                );
                continue;
            }

            if ($isNullable || !$this->bStrictNullTypes) {
                if ($jvalue === null) {
                    $this->setProperty($object, $accessor, null);
                    continue;
                }
                $type = $this->removeNullable($type);
            } elseif ($jvalue === null) {
                throw new JsonMapper_Exception(
                    'JSON property "' . $key . '" in class "'
                    . $strClassName . '" must not be NULL'
                );
            }

            $type = $this->getFullNamespace($type, $strNs);
            $type = $this->getMappedType($type, $jvalue);

            if ($type === null || $type === 'mixed') {
                // no given type - simply set the json data
                $this->setProperty($object, $accessor, $jvalue);
                continue;
            }
            if ($this->isObjectOfSameType($type, $jvalue)) {
                $this->setProperty($object, $accessor, $jvalue);
                continue;
            }
            if ($this->isSimpleType($type)) {
                if ($type === 'string' && is_object($jvalue)) {
                    throw new JsonMapper_Exception(
                        'JSON property "' . $key . '" in class "'
                        . $strClassName . '" is an object and'
                        . ' cannot be converted to a string'
                    );
                }
                settype($jvalue, $type);
                $this->setProperty($object, $accessor, $jvalue);
                continue;
            }

            // FIXME: check if type exists, give detailed error message if not
            if ($type === '') {
                throw new JsonMapper_Exception(
                    'Empty type at property "'
                    . $strClassName . '::$' . $key . '"'
                );
            }

            $array = null;
            $subtype = null;
            if ($this->isArrayOfType($type)) {
                // array
                $array = [];
                $subtype = substr($type, 0, -2);
            } elseif (substr($type, -1) == ']') {
                [$proptype, $subtype] = explode('[', substr($type, 0, -1));
                if ($proptype == 'array') {
                    $array = [];
                } else {
                    $array = $this->createInstance($proptype, false, $jvalue);
                }
            } else {
                if (is_a($type, 'ArrayObject', true)) {
                    $array = $this->createInstance($type, false, $jvalue);
                }
            }

            if ($array !== null) {
                if (!is_array($jvalue) && $this->isFlatType(gettype($jvalue))) {
                    throw new JsonMapper_Exception(
                        'JSON property "' . $key . '" must be an array, '
                        . gettype($jvalue) . ' given'
                    );
                }

                $cleanSubtype = $this->removeNullable($subtype);
                $subtype = $this->getFullNamespace($cleanSubtype, $strNs);
                $child = $this->mapDtoArray($jvalue, $array, $subtype, $key);
            } elseif ($this->isFlatType(gettype($jvalue))) {
                // use constructor parameter if we have a class
                // but only a flat type (i.e. string, int)
                if ($this->bStrictObjectTypeChecking) {
                    throw new JsonMapper_Exception(
                        'JSON property "' . $key . '" must be an object, '
                        . gettype($jvalue) . ' given'
                    );
                }
                $child = $this->createInstance($type, true, $jvalue);
            } else {
                $child = $this->createInstance($type, false, $jvalue);
                $this->mapDto($jvalue, $child);
            }
            $this->setProperty($object, $accessor, $child);
        }

        if ($this->bExceptionOnMissingData) {
            $this->checkMissingData($providedProperties, $rc);
        }

        if ($this->bRemoveUndefinedAttributes) {
            $this->removeUndefinedAttributes($object, $providedProperties);
        }

        if ($this->postMappingMethod !== null
            && $rc->hasMethod($this->postMappingMethod)
        ) {
            $refDeserializePostMethod = $rc->getMethod(
                $this->postMappingMethod
            );
            $refDeserializePostMethod->setAccessible(true);
            $refDeserializePostMethod->invoke($object);
        }

        return $object;
    }

    /**
     * Try to find out if a property exists in a given class.
     * Checks property first, falls back to setter method.
     *
     * @param ReflectionClass $rc Reflection class to check
     * @param string $name Property name
     *
     * @return array First value: if the property exists
     *               Second value: the accessor to use (
     *               ReflectionMethod or ReflectionProperty, or null)
     *               Third value: type of the property
     *               Fourth value: if the property is nullable
     */
    protected function inspectProperty(ReflectionClass $rc, $name)
    {
        // try setter method first
        $setter = 'set' . $this->getCamelCaseName($name);

        if ($rc->hasMethod($setter)) {
            $rmeth = $rc->getMethod($setter);
            if ($rmeth->isPublic() || $this->bIgnoreVisibility) {
                $isNullable = false;
                $rparams = $rmeth->getParameters();
                if (count($rparams) > 0) {
                    $isNullable = $rparams[0]->allowsNull();
                    $ptype = $rparams[0]->getType();
                    if ($ptype !== null) {
                        if ($ptype instanceof ReflectionNamedType) {
                            $typeName = $ptype->getName();
                        }
                        if ($ptype instanceof ReflectionUnionType
                            || !$ptype->isBuiltin()
                        ) {
                            $typeName = '\\' . $typeName;
                        }
                        // allow overriding an "array" type hint
                        // with a more specific class in the docblock
                        if ($typeName !== 'array') {
                            return [
                                true, $rmeth,
                                $typeName,
                                $isNullable,
                            ];
                        }
                    }
                }

                $docblock = $rmeth->getDocComment();
                $annotations = static::parseAnnotations($docblock);

                if (!isset($annotations['param'][0])) {
                    return [true, $rmeth, null, $isNullable];
                }
                [$type] = explode(' ', trim($annotations['param'][0]));
                return [true, $rmeth, $type, $this->isNullable($type)];
            }
        }

        // now try to set the property directly
        // we have to look it up in the class hierarchy
        $class = $rc;
        $rprop = null;
        do {
            if ($class->hasProperty($name)) {
                $rprop = $class->getProperty($name);
            }
        } while ($rprop === null && $class = $class->getParentClass());

        if ($rprop === null) {
            // case-insensitive property matching
            foreach ($rc->getProperties() as $p) {
                if (strcasecmp($p->name, $name) === 0) {
                    $rprop = $p;
                    break;
                }
            }
        }
        if ($rprop !== null) {
            if ($rprop->isPublic() || $this->bIgnoreVisibility) {
                $docblock = $rprop->getDocComment();
                // 修改源码
                $annotations = $this->parseAnnotationsNew($rc, $rprop, $docblock);

                if (!isset($annotations['var'][0])) {
                    // If there is no annotations (higher priority) inspect
                    // if there's a scalar type being defined
                    if (PHP_VERSION_ID >= 70400 && $rprop->hasType()) {
                        $rPropType = $rprop->getType();
                        $propTypeName = $rPropType->getName();

                        if ($this->isSimpleType($propTypeName)) {
                            return [
                                true,
                                $rprop,
                                $propTypeName,
                                $rPropType->allowsNull(),
                            ];
                        }

                        return [
                            true,
                            $rprop,
                            '\\' . $propTypeName,
                            $rPropType->allowsNull(),
                        ];
                    }

                    return [true, $rprop, null, false];
                }

                // support "@var type description"
                [$type] = explode(' ', $annotations['var'][0]);

                return [true, $rprop, $type, $this->isNullable($type)];
            }
            // no setter, private property
            return [true, null, null, false];
        }

        // no setter, no property
        return [false, null, null, false];
    }

    /**
     * Copied from PHPUnit 3.7.29, Util/Test.php.
     *
     * @param false|string $docblock Full method docblock
     *
     * @return array Array of arrays.
     *               Key is the "@"-name like "param",
     *               each value is an array of the rest of the @-lines
     */
    protected function parseAnnotationsNew(ReflectionClass $rc, ReflectionProperty $reflectionProperty, $docblock): array
    {
        $annotations = [];
        /** @var ReflectionAttribute $arrayType */
        $arrayType = $reflectionProperty->getAttributes(ArrayType::class)[0] ?? [];
        if (!empty($arrayType)) {
            $type = $arrayType->getArguments()[0] ?? null;
            if (!empty($type)) {
                $isSimpleType = $this->isSimpleType($type);
                if ($isSimpleType) {
                    $annotations['var'][] = $type . '[]';
                } else {
                    $annotations['var'][] = '\\' . $type . '[]';
                }
                return $annotations;
            }
        }
        if (!is_string($docblock)) {
            return [];
        }
        $factory = DocBlockFactory::createInstance();
        $contextFactory = new ContextFactory();
        $context = $contextFactory->createForNamespace($rc->getNamespaceName(), file_get_contents($rc->getFileName()));
        $block = $factory->create($docblock, $context);
        foreach ($block->getTags() as $tag) {
            if ($tag instanceof Var_) {
                $annotations[$tag->getName()][] = $tag->getType()->__toString();
            }
        }
        return $annotations;
    }

    protected function createInstance($class, $useParameter = false, $jvalue = null)
    {
        if ($useParameter) {
            /* @phpstan-ignore-next-line */
            if (PHP_VERSION_ID >= 80100 && is_subclass_of($class, BackedEnum::class)) {
                return ($class)::from($jvalue);
            }
            return new $class($jvalue);
        }
        $reflectClass = new ReflectionClass($class);
        $constructor = $reflectClass->getConstructor();
        if ($constructor === null
            || $constructor->getNumberOfRequiredParameters() > 0
        ) {
            return $reflectClass->newInstanceWithoutConstructor();
        }
        return $reflectClass->newInstance();
    }

    /**
     * Map an array.
     *
     * @param array $json JSON array structure from json_decode()
     * @param mixed $array Array or ArrayObject that gets filled with
     *                     data from $json
     * @param string $class Class name for children objects.
     *                      All children will get mapped onto this type.
     *                      Supports class names and simple types
     *                      like "string" and nullability "string|null".
     *                      Pass "null" to not convert any values
     * @param string $parent_key defines the key this array belongs to
     *                           in order to aid debugging
     *
     * @return mixed Mapped $array is returned
     */
    protected function mapDtoArray($json, $array, $class = null, $parent_key = '')
    {
        $originalClass = $class;
        foreach ($json as $key => $jvalue) {
            $class = $this->getMappedType($originalClass, $jvalue);
            if ($class === null) {
                $array[$key] = $jvalue;
            } elseif ($this->isArrayOfType($class)) {
                $array[$key] = $this->mapDtoArray(
                    $jvalue,
                    [],
                    substr($class, 0, -2)
                );
            } elseif ($this->isFlatType(gettype($jvalue))) {
                // use constructor parameter if we have a class
                // but only a flat type (i.e. string, int)
                if ($jvalue === null) {
                    $array[$key] = null;
                } else {
                    if ($this->isSimpleType($class)) {
                        settype($jvalue, $class);
                        $array[$key] = $jvalue;
                    } else {
                        $array[$key] = $this->createInstance(
                            $class,
                            true,
                            $jvalue
                        );
                    }
                }
            } elseif ($this->isFlatType($class)) {
                throw new JsonMapper_Exception(
                    'JSON property "' . ($parent_key ? $parent_key : '?') . '"'
                    . ' is an array of type "' . $class . '"'
                    . ' but contained a value of type'
                    . ' "' . gettype($jvalue) . '"'
                );
            } elseif (is_a($class, 'ArrayObject', true)) {
                $array[$key] = $this->mapDtoArray(
                    $jvalue,
                    $this->createInstance($class)
                );
            } else {
                $array[$key] = $this->map(
                    $jvalue,
                    $this->createInstance($class, false, $jvalue)
                );
            }
        }
        return $array;
    }

    protected function aliasMapping($strClassName, $key)
    {
        $isAliasMappingClassname = PropertyAliasMappingManager::isAliasMappingClassname($strClassName);
        if (!$isAliasMappingClassname) {
            return $key;
        }
        return PropertyAliasMappingManager::getAliasMapping($key) ?? $key;
    }
}
