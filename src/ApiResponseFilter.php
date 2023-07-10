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

use Hyperf\Utils\Codec\Json;

abstract class ApiResponseFilter
{
    /** @var string 数组模式 */
    public const SIMPLE_MODE = 'simple';

    /** @var string 列表模式 */
    public const COMPLEX_MODE = 'complex';

    protected string $mode = self::SIMPLE_MODE;

    protected string $fieldsName = 'fields';

    public function __construct()
    {
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode;
    }

    public function filter(string $json): string
    {
        $data = Json::decode($json);
        $dataObj = Mapper::mapDto($data['data'], $this);
        $dataObj = Json::encode($dataObj);
        $dataObj = Json::decode($dataObj);

        if ($this->mode === self::SIMPLE_MODE) {
            $data['data'] = $this->simple($dataObj);
        }
        if ($this->mode === self::COMPLEX_MODE) {
            $data['data'] = $this->complex($dataObj);
        }

        $data = $this->handleArrayToString($data);
        return Json::encode($data);
    }

    protected function handleArrayToString(array $data): array
    {
        foreach ($data as $key => $item) {
            if (is_array($item)) {
                $data[$key] = $this->handleArrayToString($item);
            }

            if (!is_array($item) && is_string($key) && stripos(strtolower($key), 'id') !== false) {
                $data[$key] = (string)$item;
            }
        }

        return $data;
    }

    protected function simpleFields(array $data): array
    {
        return $data;
    }

    protected function complexFields(array $data): array
    {
        return $data;
    }

    private function simple(array $data): array
    {
        $property = $this->mode . 'Fields';
        if (property_exists($this, $this->fieldsName) && $this->{$this->fieldsName}) {
            $data = $this->parts($data, $this->{$this->fieldsName});
        }
        if (method_exists($this, $property)) {
            $data = $this->{$property}($data);
        }

        return $data;
    }

    private function complex(array $data): array
    {
        $property = $this->mode . 'Fields';
        if (property_exists($this, $this->fieldsName) && $this->{$this->fieldsName}) {
            if (isset($data['list'])) {
                foreach ($data['list'] as &$item) {
                    $item = $this->parts($item, $this->{$this->fieldsName});
                }
                unset($item);

                $this->{$this->fieldsName} = array_merge($this->{$this->fieldsName}, ['count', 'list']);

                $data = $this->simple($data);
            } else {
                foreach ($data as &$item) {
                    $item = $this->parts($item, $this->{$this->fieldsName});
                }
                unset($item);
            }
        }

        if (method_exists($this, $property)) {
            $data = $this->{$property}($data);
        }

        return $data;
    }

    private function parts(array $array, array $keys, bool $strict = false): array
    {
        foreach (array_keys($array) as $key) {
            if (!in_array($key, $keys, $strict)) {
                unset($array[$key]);
            }
        }
        return $array;
    }
}
