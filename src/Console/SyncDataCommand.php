<?php

namespace ZipkinTracer\Console;

use Illuminate\Console\Command;
use Zipkin\Endpoint;
use Zipkin\Reporters\Http;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;
use ZipkinTracer\DTO\{CustomSpansDTO, DBQueryDTO, HttpRequestDTO, RequestDTO};
use ZipkinTracer\Enums\Tags;
use Zipkin\Propagation\TraceContext;
use Zipkin\Tracer;

class SyncDataCommand extends Command
{
    protected $signature = 'zipkin-tracer:sync_data';
    protected $description = '';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->getConfig('storage_path');
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                $fileRequest = explode('.json', $entry);
                if (2 !== count($fileRequest)) {
                    continue;
                }
                $filePath = $path . '/' . $entry;
                $requestData = json_decode(file_get_contents($filePath), true);
                $httpData = RequestDTO::fromArray($requestData['http_data']);
                $queries = [];
                foreach ($requestData['queries'] as $query) {
                    $queries[] = DBQueryDTO::fromArray($query);
                }

                $httpClientData = [];
                foreach ($requestData['http_client_data'] as $httpRequest) {
                    $httpClientData[] = HttpRequestDTO::fromArray($httpRequest);
                }

                $customSpans = [];
                foreach ($requestData['custom_spans'] as $span) {
                    $customSpans[] = CustomSpansDTO::fromArray($span);
                }

                $this->sendData($httpData, $queries, $httpClientData, $customSpans);
                unlink($filePath);
            }
            closedir($handle);
        }
    }


    /**
     * @param RequestDTO $httpData
     * @param DBQueryDTO[] $queries
     * @param HttpRequestDTO[] $httpClientData
     * @param CustomSpansDTO[] $customSpans
     * @return void
     */
    private function sendData(
        RequestDTO $httpData,
        array      $queries,
        array      $httpClientData,
        array      $customSpans
    )
    {
        $serviceName = $this->getConfig('service_name');
        $endpoint = Endpoint::create($serviceName);
        $reporter = new Http(['endpoint_url' => $this->getConfig('endpoint_url')]);
        $sampler = BinarySampler::createAsAlwaysSample();
        $tracing = TracingBuilder::create()
            ->havingLocalEndpoint($endpoint)
            ->havingSampler($sampler)
            ->havingReporter($reporter)
            ->build();
        $tracer = $tracing->getTracer();

        $traceName = sprintf('%s: %s %s R_ID: %s',
            $serviceName,
            $httpData->getMethod(),
            $httpData->getUrl(),
            $httpData->getRequestId()
        );
        $span = $tracer->newTrace();
        $startTime = $httpData->getStartTimestamp();
        $span->start($startTime * 1000 * 1000);
        $span->setName($traceName);
        $span->setKind(\Zipkin\Kind\SERVER);
        $span->tag(Tags::HTTP_URL->value, $httpData->getUrl());
        $span->tag(Tags::HTTP_STATUS_CODE->value, (string)$httpData->getStatusCode());
        $span->tag(Tags::HTTP_METHOD->value, $httpData->getMethod());
        $span->tag(Tags::HTTP_REQUEST_SIZE->value, (string)$httpData->getRequestSize());
        $span->tag(Tags::HTTP_RESPONSE_SIZE->value, (string)$httpData->getResponseSize());
        if (null !== $httpData->getException()) {
            $span->setError($httpData->getException());
        }

        foreach ($queries as $query) {
            $filePath = str_replace(app_path(), '', $query->getExecuteFile());
            $queryName = sprintf('%s: %s -> %d', 'sql_query', $filePath, $query->getExecuteFileLine());
            $childSpan = $tracer->newChild($span->getContext());
            $now = $query->getStartTime();
            $childSpan->start($now * 1000 * 1000);
            $childSpan->setKind(\Zipkin\Kind\CLIENT);
            $childSpan->setName($queryName);
            $childSpan->tag(Tags::SQL_QUERY->value, $query->getQuery());
            $childSpan->finish(($now + $query->getDuration()) * 1000 * 1000);
        }

        /** @var HttpRequestDTO $httpRequest */
        foreach ($httpClientData as $httpRequest) {
            $childSpan = $tracer->newChild($span->getContext());
            $now = $httpRequest->getStartTime();
            $childSpan->start($now * 1000 * 1000);
            $childSpan->setKind(\Zipkin\Kind\CLIENT);
            $childSpan->setName('http_client_request');
            $childSpan->tag(Tags::HTTP_URL->value, $httpRequest->getUrl());
            $childSpan->tag(Tags::HTTP_METHOD->value, $httpRequest->getMethod());
            $response = $httpRequest->getResponse();
            if (null !== $response) {
                $childSpan->tag(Tags::HTTP_STATUS_CODE->value, $response->getStatusCode());
            }
            if (null !== $httpRequest->getError()) {
                $childSpan->setError(new \Exception($httpRequest->getError()));
            }

            $childSpan->tag('http.headers', json_encode($httpRequest->getHeaders()));
            $childSpan->finish(($now + $httpRequest->getDurationTime()) * 1000 * 1000);
        }

        // process custom spans
        foreach ($customSpans as $customSpan) {
            $this->processRecurciveCustormSpan($tracer, $customSpan, $span->getContext());
        }

        $span->finish(($startTime + $httpData->getTime()) * 1000 * 1000);
        $tracer->flush();
    }

    private function processRecurciveCustormSpan(Tracer $tracer, CustomSpansDTO $customSpan, TraceContext $context)
    {
        $filePath = str_replace(app_path(), '', $customSpan->getExecuteFile());
        $spanName = sprintf('%s: %s -> %d', $customSpan->getName(), $filePath, $customSpan->getExecuteFileLine());
        $childSpan = $tracer->newChild($context);
        $now = $customSpan->getStartTime();
        $childSpan->start($now * 1000 * 1000);
        $childSpan->setKind(\Zipkin\Kind\CLIENT);
        $childSpan->setName($spanName);
        foreach ($customSpan->getTagsWithResult() as $tag => $result) {
            $childSpan->tag($tag, $result);
        }
        if (null !== $customSpan->getException()) {
            $childSpan->setError($customSpan->getException());
        }
        $childSpan->finish(($now + $customSpan->getDurationTime()) * 1000 * 1000);

        if (null !== $customSpan->getChildSpans()) {
            foreach ($customSpan->getChildSpans() as $childCustomSpan) {
                $this->processRecurciveCustormSpan($tracer, $childCustomSpan, $childSpan->getContext());
            }
        }
    }

    private function getConfig($key, $default = null)
    {
        return app()['config']->get("zipkin-tracer.{$key}", $default);
    }
}
