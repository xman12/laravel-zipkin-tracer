<?php

namespace ZipkinTracer\Services;

use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use ZipkinTracer\DTO\HttpRequestDTO;
use ZipkinTracer\DTO\HttpResponseDTO;

class HttpClientManagerData
{
    private array $requests = [];

    public function handleSendingRequest(RequestSending $event): void
    {
        $request = $event->request;
        $httpRequestDTO = new HttpRequestDTO(
            method: $request->method(),
            url: $request->url(),
            headers: $request->headers(),
            body: $request->body(),
            content: json_encode($request->data()),
            startTime: microtime(true),
            durationTime: 0.0
        );
        $this->requests[spl_object_hash($request)] = $httpRequestDTO;
    }

    public function handleResponseReceived(ResponseReceived $event): void
    {
        $objectKey = spl_object_hash($event->request);
        /** @var HttpRequestDTO|null $httpRequestDTO */
        $httpRequestDTO = $this->requests[$objectKey] ?? null;
        if (null === $httpRequestDTO) {
            return;
        }

        $httpResponseDTO = new HttpResponseDTO(
            statusCode: $event->response->status(),
            content: $event->response->json(),
            body: $event->response->body(),
            headers: $event->response->headers()
        );
        $httpRequestDTO->setDuration(microtime(true) - $httpRequestDTO->getStartTime());
        $httpRequestDTO->setResponse($httpResponseDTO);
        $this->requests[$objectKey] = $httpRequestDTO;
    }

    public function handleConnectionFailed(ConnectionFailed $event)
    {
        $objectKey = spl_object_hash($event->request);
        /** @var HttpRequestDTO|null $httpRequestDTO */
        $httpRequestDTO = $this->requests[$objectKey] ?? null;
        if (null === $httpRequestDTO) {
            return;
        }

        $httpRequestDTO->setDuration(microtime(true) - $httpRequestDTO->getStartTime());
        $httpRequestDTO->setError('Connection failed');

    }
    /**
     * @return HttpRequestDTO[]
     */
    public function getRequests(): array
    {
        return $this->requests;
    }
}
