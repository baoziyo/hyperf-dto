<?php

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO\Aspect;

use Hyperf\Context\Context;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Codec\Json;
use Hyperf\Utils\Contracts\Arrayable;
use Hyperf\Utils\Contracts\Jsonable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CoreMiddlewareAspect extends AbstractAspect
{
    /** @var array|string[] */
    public array $classes = [
        'Hyperf\HttpServer\CoreMiddleware::transferToResponse',
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint): ResponseInterface
    {
        $arguments = $proceedingJoinPoint->arguments['keys'];

        return $this->transferToResponse($arguments['response'], $arguments['request']);
    }

    protected function transferToResponse(object|array|string|null $response, ServerRequestInterface $request): ResponseInterface
    {
        $responseObj = Context::get(ResponseInterface::class);
        if (is_string($response)) {
            return $responseObj->response()->withAddedHeader('content-type', 'text/plain')->withBody(new SwooleStream($response));
        }

        if (is_array($response) || $response instanceof Arrayable) {
            return $responseObj->response()
                ->withAddedHeader('content-type', 'application/json')
                ->withBody(new SwooleStream(Json::encode($response)));
        }

        if ($response instanceof Jsonable) {
            return $responseObj->response()
                ->withAddedHeader('content-type', 'application/json')
                ->withBody(new SwooleStream((string)$response));
        }

        if (is_object($response)) {
            return $responseObj->response()
                ->withAddedHeader('content-type', 'application/json')
                ->withBody(new SwooleStream(Json::encode($response)));
        }

        return $responseObj->response()->withAddedHeader('content-type', 'text/plain')->withBody(new SwooleStream((string)$response));
    }
}
