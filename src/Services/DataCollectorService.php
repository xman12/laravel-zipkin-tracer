<?php

namespace ZipkinTracer\Services;

class DataCollectorService
{
    public function __construct(
        private string $storagePath,
        private HttpRequestManagerData    $httpRequestManagerData,
        private EloquentSourceManagerData $eloquentSourceManagerData,
        private HttpClientManagerData     $httpClientManagerData,
        private CustomSpanService       $CustomSpanService,
    )
    {
    }

    public function store(): void
    {
        $path = $this->storagePath;
        if (false === is_dir($path)) {
            if (false === @mkdir($path, 0700, true)) {
                throw new \RuntimeException('Unable to create zipkin-tracer directory');
            }
        }
        $data['queries'] = [];
        foreach ($this->eloquentSourceManagerData->getQueries() as $query) {
            $data['queries'][] = $query->toArray();
        }
        $data['http_data'] = $this->httpRequestManagerData->getRequest()->toArray();
        $data['http_client_data'] = [];
        foreach ($this->httpClientManagerData->getRequests() as $request) {
            $data['http_client_data'][] = $request->toArray();
        }
        $data['custom_spans'] = [];
        foreach ($this->CustomSpanService->getSpans() as $span) {
            $data['custom_spans'][] = $span->toArray();
        }

        $fileName = $this->httpRequestManagerData->getRequest()->getRequestId() . '.json';
        $filePath = sprintf('%s/%s', $path, $fileName);

        file_put_contents("{$path}/.gitignore", "*.json\n*.json.gz\nindex\n");
        file_put_contents($filePath, @json_encode($data, JSON_PARTIAL_OUTPUT_ON_ERROR) . PHP_EOL);
    }
}
