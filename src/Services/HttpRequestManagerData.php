<?php

namespace ZipkinTracer\Services;

use Illuminate\Http\Client\Events\{ConnectionFailed, RequestSending, ResponseReceived};
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use ZipkinTracer\Exceptions\BaseException;
use ZipkinTracer\DTO\RequestDTO;

class HttpRequestManagerData
{
    protected float $startTime = 0;
    protected ?RequestDTO $request = null;

    public function putRequestData(
        Application $app,
        float       $finishTimestamp,
        int         $statusCode,
        int         $responseSize,
        ?BaseException  $exception = null,
    )
    {
        /** @var Request $requestObject */
        $requestObject = $app['request'];
        $headers = $requestObject->headers->all();
        $requestId = isset($headers['requestId']) ? $headers['requestId'] : $this->generateRequestId();

        $this->request = new RequestDTO(
            $requestId,
            $requestObject->method(),
            $this->removeAuthFromUrl($requestObject->url()),
            $requestObject->headers->all(),
            $requestObject->cookies->all(),
            $requestObject->query(),
            $requestObject->getContent(),
            $this->startTime,
            $finishTimestamp,
            $statusCode,
            mb_strlen($requestObject->getContent(), '8bit'),
            $responseSize,
            $requestObject->route()?->getName(),
            $exception
        );
    }

    protected function generateRequestId()
    {
        return str_replace('.', '-', sprintf('%.4F', microtime(true))) . '-' . mt_rand();
    }

    // Removes username and password from the URL
    protected function removeAuthFromUrl($url)
    {
        return preg_replace('#^(.+?://)(.+?@)(.*)$#', '$1$3', $url);
    }


    public function getRequest(): ?RequestDTO
    {
        return $this->request;
    }

    public function setStartTime(float $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }
}
