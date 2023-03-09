# PHP Hyperf DTO

# 介绍

> 基于 [tw2066/dto](https://github.com/tw2066/dto) 框架改进而来，特别鸣谢tw2066/dto给的灵感

# 运行环境

* php >= 8.2
* hyperf >= 3.0

# 安装

```shell
composer require baoziyoo/hyperf-dto
```

# 使用

## 创建简单dto

```php
<?php

declare(strict_types=1);

namespace Baoziyoo\Hyperf\Example\DTO;

class Address
{
    public string $street;

    public float $float;
    
    public int $int;
    
    /** @var array<int,string> */
    public array $array;
    
    public LoginTokenTypeEnum $loginTokenTypeEnum;
    
    public ?City $city = null;
}

---

class City
{
    public string $name;
}

---

enum LoginTokenTypeEnum: string
{
    case jwt = 'jwt';
    
    case password = 'password';
}
```

## 引用

> 注意: 一个方法，不能同时注入RequestBody和RequestFormData

```php
use Baoziyoo\Hyperf\DTO\Annotation\Contracts\RequestBody;
use Baoziyoo\Hyperf\DTO\Annotation\Contracts\RequestQuery;
use Baoziyoo\Hyperf\DTO\Annotation\Contracts\RequestFormData;


// 获取Body参数
public function add(#[RequestBody] Address $request){}

// 获取GET参数
public function add(#[RequestQuery] Address $request){}

// 获取表单请求
public function fromData(#[RequestFormData] Address $formData){}

// 获取Body参数和GET参数
public function add(#[RequestBody] DemoBodyRequest $request, #[RequestQuery] DemoQuery $query){}
```

## 例子

```php
class DemoController extends AbstractController
{
    public function index(#[RequestQuery] DemoQuery $request): Contact
    {
        $contact = new Contact();
        $contact->name = $request->name;
        var_dump($request);
        return $contact;
    }

    public function add(#[RequestBody] DemoBodyRequest $request, #[RequestQuery] DemoQuery $query)
    {
        var_dump($query);
        return json_encode($request, JSON_UNESCAPED_UNICODE);
    }

    public function fromData(#[RequestFormData] DemoFormData $formData): bool
    {
        $file = $this->request->file('photo');
        var_dump($file);
        var_dump($formData);
        return true;
    }
}
```
