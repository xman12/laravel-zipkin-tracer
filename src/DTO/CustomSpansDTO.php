<?php

namespace ZipkinTracer\DTO;

use ZipkinTracer\Exceptions\BaseException;

readonly class CustomSpansDTO
{
    /**
     * @param string $name
     * @param array<string, string> $tagsWithResult
     * @param float $startTime
     * @param float $durationTime
     * @param string $executeFile
     * @param int $executeFileLine
     * @param BaseException|null $exception
     * @param array<int, CustomSpansDTO> $childSpans
     */
    public function __construct(
        private string $name,
        private array $tagsWithResult,
        private float $startTime,
        private float $durationTime,
        private string $executeFile,
        private int $executeFileLine,
        private ?BaseException $exception = null,
        private ?array $childSpans = null,
    )
    {
    }

    public function toArray():array
    {
        $childrenSpans = [];
        if (null !== $this->childSpans) {
            foreach ($this->childSpans as $childSpan) {
                $childrenSpans[] = $childSpan->toArray();
            }
        }

        return [
            'name' => $this->name,
            'tags_with_result' => $this->tagsWithResult,
            'start_time' => $this->startTime,
            'duration_time' => $this->durationTime,
            'execute_file' => $this->executeFile,
            'execute_file_line' => $this->executeFileLine,
            'exception' => $this->exception?->toArray(),
            'child_spans' => count($childrenSpans) > 0 ? $childrenSpans : null,
        ];
    }

    public static function fromArray(array $data): self
    {
        $childrenSpans = [];
        if (isset($data['child_spans'])) {
            foreach ($data['child_spans'] as $childSpan) {
                $childrenSpans[] = self::fromArray($childSpan);
            }
        }

        return new self(
            $data['name'],
            $data['tags_with_result'],
            $data['start_time'],
            $data['duration_time'],
            $data['execute_file'],
            $data['execute_file_line'],
            isset($data['exception']) ? BaseException::fromArray($data['exception']) : null,
            count($childrenSpans) > 0 ? $childrenSpans : null,
        );
    }

    public function getException(): ?BaseException
    {
        return $this->exception;
    }

    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function getDurationTime(): float
    {
        return $this->durationTime;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public function getTagsWithResult(): array
    {
        return $this->tagsWithResult;
    }

    public function getExecuteFileLine(): int
    {
        return $this->executeFileLine;
    }

    public function getExecuteFile(): string
    {
        return $this->executeFile;
    }

    public function getChildSpans(): ?array
    {
        return $this->childSpans;
    }
}
