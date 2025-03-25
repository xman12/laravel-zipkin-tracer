<?php

namespace ZipkinTracer\Exceptions;

class BaseException extends \Exception
{
    public function __construct(
        string $message = "",
        int $code = 0,
        string $file,
        int $line,
        ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->file = $file;
        $this->line = $line;
    }

    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }

    public static function fromArray(array $array): self
    {
        return new self(
            $array['message'],
            $array['code'],
            $array['file'],
            $array['line']
        );
    }
}
