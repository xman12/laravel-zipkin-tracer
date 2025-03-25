<?php

namespace ZipkinTracer\DTO;

use ZipkinTracer\Exceptions\BaseException;

class RequestDTO
{
    /**
     * @param string $requestId
     * @param string $method
     * @param string $url
     * @param array $headers
     * @param array $cookies
     * @param array $query
     * @param resource|string|null $content
     */
    public function __construct(
        private string  $requestId,
        private string  $method,
        private string  $url,
        private array   $headers,
        private array   $cookies,
        private array   $query,
        private         $content,
        private float   $startTimestamp,
        private float   $time,
        private int     $statusCode,
        private int $requestSize,
        private int $responseSize,
        private ?string $routeName = null,
        private ?BaseException  $exception = null,
    )
    {
    }

    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * @return resource|string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function getTime(): float
    {
        return $this->time;
    }

    public function getStartTimestamp(): float
    {
        return $this->startTimestamp;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getRequestSize(): int
    {
        return $this->requestSize;
    }

    public function getResponseSize(): int
    {
        return $this->responseSize;
    }

    public function getException(): ?BaseException
    {
        return $this->exception;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['request_id'],
            $data['method'],
            $data['url'],
            $data['headers'],
            $data['cookies'],
            $data['query'],
            $data['content'],
            $data['start_timestamp'],
            $data['time'],
            $data['status_code'],
            $data['request_size'],
            $data['response_size'],
            $data['route_name'] ?? null,
            isset($data['exception']) ? BaseException::fromArray($data['exception']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'request_id' => $this->requestId,
            'method' => $this->method,
            'url' => $this->url,
            'headers' => $this->headers,
            'cookies' => $this->cookies,
            'query' => $this->query,
            'content' => $this->content,
            'start_timestamp' => $this->startTimestamp,
            'time' => $this->time,
            'status_code' => $this->statusCode,
            'request_size' => $this->requestSize,
            'response_size' => $this->responseSize,
            'route_name' => $this->routeName,
            'exception' => null !== $this->exception ? $this->exception->toArray() : null,
        ];
    }

}
