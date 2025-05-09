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

class ZipkinTracerMiddleware
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

        if (isset($response->exception) && null !== $response->exception) {
            $code = is_int($response->exception->getCode()) ? $response->exception->getCode() : 500;

            $baseException = new BaseException(
                $response->exception->getMessage(),
                $code,
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
