<?php

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO;

use Baoziyoo\Hyperf\DTO\Aspect\CoreMiddlewareAspect;
use Baoziyoo\Hyperf\DTO\Listener\BeforeServerListener;

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
                CoreMiddlewareAspect::class,
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
