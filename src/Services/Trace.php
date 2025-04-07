<?php

namespace ZipkinTracer\Services;

readonly class Trace
{
    /**
     * @param array<int, TraceObject> $frames
     */
    public function __construct(private array $frames)
    {
    }

    /**
     * @return self
     */
    public static function getFromDebugBackTrace(): self
    {
        $trace = debug_backtrace();
        $basePath = static::resolveBasePath();
        $vendorPath = static::resolveVendorPath();

        return new static(array_map(static function ($frame, $index) use ($basePath, $vendorPath, $trace) {
            return new TraceObject(
                static::fixFrame($frame, $trace, $index), $basePath, $vendorPath
            );
        }, $trace, array_keys($trace)));
    }

    protected static function resolveBasePath(): string
    {
        return substr(__DIR__, 0, strpos(__DIR__, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR));
    }

    protected static function resolveVendorPath(): string
    {
        return static::resolveBasePath() . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR;
    }

    protected static function fixFrame(array $frame, array $trace, $index): array
    {
        if (isset($frame['file'])) return $frame;

        $nextFrame = $trace[$index + 1] ?? null;

        if (null === $nextFrame || !in_array($nextFrame['function'], ['call_user_func', 'call_user_func_array'])) return $frame;

        $frame['file'] = $nextFrame['file'];
        $frame['line'] = $nextFrame['line'];

        return $frame;
    }

    public function resolveExecuteFile(): ?TraceObject
    {
        $appPath = app_path();
        foreach ($this->frames as $frame) {
            if (
                preg_match('#' . $appPath . '(.*)#', $frame->getShortPath())
                || preg_match('#' . $appPath . '(.*)#', $frame->getFile())
                || 'createSpan' === $frame->getFunction()
                || 'Illuminate\Support\Facades\Facade' === $frame->getClass()
                || '/vendor/laravel/framework/src/Illuminate/Session/Store.php' === $frame->getShortPath()
            ) {
                return $frame;
            }
        }

        return null;
    }
}
