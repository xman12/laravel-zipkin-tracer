<?php

namespace ZipkinTracer\DTO;

class HttpRequestDTO
{
    public function __construct(
        private string $method,
        private string $url,
        private array $headers,
        private string $body,
        private string $content,
        private float $startTime,
        private float $durationTime,
        private ?string $error = null,
        private ?HttpResponseDTO $response = null,
    )
    {
    }

    public function setResponse(?HttpResponseDTO $response): static
    {
        $this->response = $response;

        return $this;
    }

    public function setDuration(float $duration): static
    {
        $this->durationTime = $duration;

        return $this;
    }

    public function setError(string $error): static
    {
        $this->error = $error;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getDurationTime(): float
    {
        return $this->durationTime;
    }

    public function getResponse(): ?HttpResponseDTO
    {
        return $this->response;
    }

    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'url' => $this->url,
            'headers' => $this->headers,
            'body' => $this->body,
            'content' => $this->content,
            'startTime' => $this->startTime,
            'durationTime' => $this->durationTime,
            'error' => $this->error,
            'response' => null !== $this->response ? $this->response->toArray() : null,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['method'],
            $data['url'],
            $data['headers'],
            $data['body'],
            $data['content'],
            $data['startTime'],
            $data['durationTime'],
            $data['error'] ?? null,
            isset($data['response']) ? HttpResponseDTO::fromArray($data['response']) : null,
        );
    }
}
