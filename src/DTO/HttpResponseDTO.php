<?php

namespace ZipkinTracer\DTO;

class HttpResponseDTO
{
    public function __construct(
        private int $statusCode,
        private array $headers,
        private ?string $body,
        private ?string $content
    )
    {
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->statusCode,
            'headers' => $this->headers,
            'body' => $this->body,
            'content' => $this->content,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['status'],
            $data['headers'],
            $data['body'] ?? null,
            $data['content'] ?? null
        );
    }
}
