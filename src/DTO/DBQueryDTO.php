<?php

namespace ZipkinTracer\DTO;

readonly class DBQueryDTO
{
    public function __construct(
        private string $query,
        private float $duration,
        private string $connection,
        private float $startTime,
        private string $executeFile,
        private int $executeFileLine
    )
    {
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    public function getDuration(): float
    {
        return $this->duration;
    }

    public function getExecuteFile(): string
    {
        return $this->executeFile;
    }

    public function getExecuteFileLine(): int
    {
        return $this->executeFileLine;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'duration' => $this->duration,
            'connection' => $this->connection,
            'start_time' => $this->startTime,
            'execute_file' => $this->executeFile,
            'execute_file_line' => $this->executeFileLine,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['query'],
            $data['duration'],
            $data['connection'],
            $data['start_time'],
            $data['execute_file'],
            $data['execute_file_line']
        );
    }
}
