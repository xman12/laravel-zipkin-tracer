<?php

namespace ZipkinTracer\Middleware;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Response;
use Zipkin\Endpoint;
use Zipkin\Reporters\Http;
use Zipkin\Samplers\BinarySampler;
use ZipkinTracer\Exceptions\BaseException;
use Zipkin\TracingBuilder;
use ZipkinTracer\Services\{DataCollectorService, EloquentSourceManagerData, HttpRequestManagerData};

class ZipkinTracerMiddlerware
{
    public function __construct(protected Application $app)
    {
    }

    public function handle($request, \Closure $next)
    {
        /** @var HttpRequestManagerData $httRequestManagerData */
        $httRequestManagerData = $this->app[HttpRequestManagerData::class];
        $httRequestManagerData->setStartTime(microtime(true));
        $baseException = null;

        try {
            /** @var Response $response */
            $response = $next($request);
        }catch (\Throwable $exception) {
            $this->app[ExceptionHandler::class]->report($e);
            $response = $this->app[ExceptionHandler::class]->render($request, $e);
        }

        if (null !== $response->exception) {
            $baseException = new BaseException(
                $response->exception->getMessage(),
                $response->exception->getCode(),
                $response->exception->getFile(),
                $response->exception->getLine()
            );
        }

        $httRequestManagerData->putRequestData(
            $this->app,
            microtime(true) - $httRequestManagerData->getStartTime(),
            $response->getStatusCode(),
            mb_strlen($response->getContent(), '8bit'),
            $baseException
        );

        return $response;
    }

    public function terminate()
    {
        /** @var DataCollectorService $dataCollector */
        $dataCollector = $this->app[DataCollectorService::class];
        $dataCollector->store();
    }
}
