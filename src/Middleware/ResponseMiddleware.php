<?php
/*
 * Copyright (c) 2023. ogg. Inc. All Rights Reserved.
 * ogg sit down and start building bugs in sunny weather.
 * Author: Ogg <baoziyoo@gmail.com>.
 * LastChangeTime: 2023-02-07 04:16:16
 * ChangeTime: 2023-04-26 10:21:24
 */

declare(strict_types=1);

namespace Baoziyoo\Hyperf\DTO\Middleware;

use App\Annotation\ResponseFilter\ResponseFilter;
use App\Core\Biz\Container\Biz;
use Baoziyoo\Hyperf\ApiDocs\Annotation\ApiResponse;
use Hyperf\Codec\Json;
use Hyperf\Context\Context;
use Hyperf\Contract\Arrayable;
use Hyperf\Contract\Jsonable;
use Hyperf\Di\Annotation\AnnotationReader;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hyperf\HttpServer\Router\Dispatched;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;

class ResponseMiddleware implements MiddlewareInterface
{
    protected ContainerInterface $container;

    protected RequestInterface $request;

    protected HttpResponse $response;

    public function __construct(ContainerInterface $container, HttpResponse $response, RequestInterface $request)
    {
        $this->container = $container;
        $this->response = $response;
        $this->request = $request;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if ($response->getStatusCode() === 200) {
            $response = $this->annotationReader($request, $response);
        }

        return $response;
    }

    private function annotationReader(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $content = $response->getBody()->getContents();
        [$className, $methodName] = $this->getClassAndMethod($request);
        if (!$className || !$methodName) {
            return $response;
        }
        $class = new ReflectionClass($className);
        $action = $class->getMethod($methodName);
        $annotationMethodResponseFilter = (new AnnotationReader())->getMethodAnnotation($action, ApiResponse::class);

        if ($annotationMethodResponseFilter === null) {
            return $response;
        }

        return match (get_class($annotationMethodResponseFilter)) {
            ApiResponse::class => $this->responseFilter($response, $annotationMethodResponseFilter, $content, $className),
            default => $response,
        };
    }

    private function responseFilter(ResponseInterface $response, mixed $annotation, string $content, string $className): ResponseInterface
    {
        $class = $annotation->type;
        $mode = $annotation->mode;
        $fieldFilter = new $class();

        if ($mode) {
            $fieldFilter->setMode($mode);
        }

        $content = $fieldFilter->filter($content);

//        $response->withBody(new SwooleStream((string) $content));

        $response = Context::get(ResponseInterface::class);
        $response->getBody()->write($content);
        Context::set(ResponseInterface::class, $response);

        return $response->withAddedHeader('Content-Type', 'application/json');
    }

    private function getClassAndMethod(ServerRequestInterface $request): array|null
    {
        $name = $request->getAttribute(Dispatched::class)->handler->callback;
        if (!is_string($name)) {
            return $name;
        }

        [$class, $method] = explode('@', $name);

        return [$class, $method];
    }
}
