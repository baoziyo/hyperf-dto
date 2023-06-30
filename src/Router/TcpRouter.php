<?php
/*
 * Copyright (c) 2023. ogg. Inc. All Rights Reserved.
 * ogg sit down and start building bugs in sunny weather.
 * Author: Ogg <baoziyoo@gmail.com>.
 * LastChangeTime: 2023-01-16 05:16:17
 * ChangeTime: 2023-04-26 10:21:32
 */

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO\Router;

use Hyperf\JsonRpc\TcpServer;
use Hyperf\Rpc\Protocol;
use Hyperf\RpcServer\Router\DispatcherFactory;
use Psr\Container\ContainerInterface;

class TcpRouter
{
    private TcpServer $tcpServer;

    private $protocol;

    public function __construct(ContainerInterface $container)
    {
        $this->tcpServer = $container->get(TcpServer::class);
    }

    public function getRouter($serverName)
    {
        $data = make(DispatcherFactory::class, [
            'pathGenerator' => $this->getProtocol()->getPathGenerator(),
        ]);
        return $data->getRouter($serverName);
    }

    protected function getProtocol(): Protocol
    {
        $getResponseBuilder = function () {
            return $this->protocol;
        };
        return $getResponseBuilder->call($this->tcpServer);
    }
}
