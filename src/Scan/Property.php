<?php

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO\Scan;

class Property
{
    /**
     * 是否为简单类型.
     */
    public bool $isSimpleType = true;

    /**
     * PHP简单类型.
     * @var null|string 'string' 'boolean' 'bool' 'integer' 'int' 'double' 'float' 'array' 'object'
     */
    public ?string $phpSimpleType = null;

    /**
     * 普通类名称.
     */
    public ?string $className = null;

    /**
     * 数组 中 复杂 类的名称.
     */
    public ?string $arrClassName = null;

    /**
     * 数组 中 简单类型  eg: int[]  string[].
     */
    public ?string $arrSimpleType = null;

    /**
     * 枚举类.
     */
    public ?PropertyEnum $enum = null;

    public function isSimpleArray(): bool
    {
        return $this->isSimpleType && $this->phpSimpleType === 'array';
    }

    public function isSimpleTypeArray(): bool
    {
        return !$this->isSimpleType && $this->phpSimpleType === 'array' && $this->arrSimpleType !== null;
    }

    public function isClassArray(): bool
    {
        return !$this->isSimpleType && $this->phpSimpleType === 'array' && $this->arrClassName !== null;
    }
}
