<?php

namespace ZipkinTracer\Services;

class TraceObject
{
    private ?string $call;
    private string $function;
    private int $line;
    private string $file;
    private ?string $class = null;
    private object $object;
    private string $type;
    private array $args = [];
    private ?string $shortPath;
    private ?string $vendor;

    public function __construct(array $data = [], string $basePath = '', string $vendorPath = '')
    {
        $this->setParams($data);
        $this->call = null !== $this->class ? "$this->class$this->type$this->function()" : "$this->function()";
        $this->shortPath = $this->file ? str_replace($basePath, '', $this->file) : null;
        $this->vendor = ($this->file && str_starts_with($this->file, $vendorPath))
            ? explode(DIRECTORY_SEPARATOR, str_replace($vendorPath, '', $this->file))[0] : null;
    }

    private function setParams(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function getCall(): ?string
    {
        return $this->call;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getObject()
    {
        return $this->object;
    }

    public function getShortPath(): ?string
    {
        return $this->shortPath;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getVendor(): ?string
    {
        return $this->vendor;
    }
}
