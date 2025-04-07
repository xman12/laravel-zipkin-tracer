<?php

namespace ZipkinTracer\Services;

use ZipkinTracer\DTO\CustomSpansDTO;
use ZipkinTracer\Exceptions\BaseException;

class CustomSpanService
{
    /** @var CustomSpansDTO[]  */
    private array $spans = [];

    /**
     * Method for create custom spans, $callback needed return array formats
     * [
     *   'tag' => 'result'
     * ]
     *
     * @param string $name
     * @param \Closure $callback
     * @param array<int, CustomSpansDTO>|null $childSpans
     * @return CustomSpansDTO
     */
    public function createSpan(string $name, \Closure $callback, ?array $childSpans = null): CustomSpansDTO
    {
        $trace = Trace::getFromDebugBackTrace();
        $executeFile = $trace->resolveExecuteFile();

        $start = microtime(true);
        $exceptionSpan = null;
        try {
            $result = $callback();
        }catch (\Throwable $exception) {
            $result = '';
            $exceptionSpan = new BaseException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getFile(),
                $exception->getLine(),
            );
        }
        $finish = microtime(true) - $start;

        return new CustomSpansDTO(
            $name,
            $result,
            $start,
            $finish,
            $executeFile->getFile(),
            $executeFile->getLine(),
            $exceptionSpan,
            $childSpans
        );
    }

    /**
     * @return CustomSpansDTO[]
     */
    public function getSpans(): array
    {
        return $this->spans;
    }

    public function addSpan(CustomSpansDTO $span): self
    {
        $this->spans[] = $span;

        return $this;
    }
}
