<?php
/*
 * Copyright (c) 2023. ogg. Inc. All Rights Reserved.
 * ogg sit down and start building bugs in sunny weather.
 * Author: Ogg <baoziyoo@gmail.com>.
 * LastChangeTime: 2023-02-07 04:16:22
 * ChangeTime: 2023-04-26 10:21:32
 */

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO;

use Baoziyoo\Hyperf\DTO\Aspect\CoreMiddlewareAspect;
use Baoziyoo\Hyperf\DTO\Listener\BeforeServerListener;
use Baoziyoo\Hyperf\DTO\Middleware\ResponseMiddleware;
use Hyperf\HttpServer\CoreMiddleware;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
            ],
            'listeners' => [
                BeforeServerListener::class,
            ],
            'aspects' => [
            ],
            'middlewares' => [
                'http' => [
                    ResponseMiddleware::class,
                ],
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
            ],
        ];
    }
}
