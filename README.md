# Easy Zipkin tracer for Laravel

The tracing for Zipkin for your application based on Laravel

## Getting Started
`composer require xman12/laravel-zipkin-tracer`

## Requirements
- PHP ^8.2
- Laravel (^10)
- openzipkin/zipkin

## Configuration

For getting start work with library add 
`ZipkinTracerProvider` to `app/bootstrap/providers.php`

Example:
```php 
return [
    App\Providers\AppServiceProvider::class,
    ZipkinTracerProvider::class,
];
```

after that you need to add command `zipkin-tracer:sync_data` to cronjob
how often to call the command you decide yourself

## Schema collect metric data 

![workflow](workflow.png)

## Sending metric data to Zipkin server

ZipkinTracer command collect all metric data
from files prepares them and sending to zipkin server 

For sending metric data we use `openzipkin/zipkin` library