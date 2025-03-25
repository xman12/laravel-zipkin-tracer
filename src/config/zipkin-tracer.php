<?php

return [
    'enable' => env('ZIPKIN_TRACER_ENABLE', false),
    'storage_path' => env('ZIPKIN_TRACER_STORAGE_PATH', storage_path('zipkin_tracer')),
    'service_name' => env('ZIPKIN_TRACER_SERVICE_NAME', 'Zipkin-service'),
    'endpoint_url' => env('ZIPKIN_TRACER_ENDPOINT', 'http://127.0.0.1:9411/api/v2/spans'),
];