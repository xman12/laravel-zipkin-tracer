<?php

namespace ZipkinTracer\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use ZipkinTracer\Console\SyncDataCommand;
use ZipkinTracer\Middleware\ZipkinTracerMiddleware;
use ZipkinTracer\Services\CustomSpanService;
use ZipkinTracer\Services\DataCollectorService;
use ZipkinTracer\Services\EloquentSourceManagerData;
use ZipkinTracer\Services\EventSubscriber;
use ZipkinTracer\Services\HttpClientManagerData;
use ZipkinTracer\Services\HttpRequestManagerData;

class ZipkinTracerProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->getConfig('enable')) {
            $this->app->booted(function () {
                $this->app['zipkin-tracer.event_subsriber']->listenToEvents();
            });
            $this->registerMiddleware();
        }
    }

    public function register()
    {
        $this->registerConfiguration();
        $this->registerServices();
        $this->registerCommands();
        $this->registerAliases();
    }

    protected function registerConfiguration()
    {
        $this->publishes([__DIR__ . '/../config/zipkin-tracer.php' => config_path('zipkin-tracer.php')], 'zipkin-tracer');
        $this->mergeConfigFrom(__DIR__ . '/../config/zipkin-tracer.php', 'zipkin-tracer');
    }

    protected function registerCommands()
    {
        $this->commands([
            SyncDataCommand::class
        ]);
    }

    // Register middleware
    protected function registerMiddleware()
    {
        $kernel = $this->app[Kernel::class];

        if (method_exists($kernel, 'hasMiddleware') && $kernel->hasMiddleware(ZipkinTracerMiddleware::class)) return;

        $kernel->prependMiddleware(ZipkinTracerMiddleware::class);
    }

    protected function registerServices()
    {
        $this->app->singleton('zipkin-tracer.eloquent_manager_data', function ($app) {
            return new EloquentSourceManagerData(
                $app['db'],
                $app['events']
            );
        });

        $this->app->singleton('zipkin-tracer.event_subsriber', function ($app) {
            return new EventSubscriber(
                $app['events'],
                $app['zipkin-tracer.eloquent_manager_data'],
                $app['zipkin-tracer.http_client_manager']
            );
        });

        $this->app->singleton('zipkin-tracer.http_request_manager_data', function ($app) {
            return (new HttpRequestManagerData($app));
        });

        $this->app->singleton('zipkin-tracer.http_client_manager', function ($app) {
            return new HttpClientManagerData();
        });

        $this->app->singleton('zipkin-tracer.custom_span_service', function ($app) {
            return new CustomSpanService();
        });

        $this->app->singleton('zipkin-tracer.data_collector', function ($app) {
            return new DataCollectorService(
                $this->getConfig('storage_path'),
                $app['zipkin-tracer.http_request_manager_data'],
                $app['zipkin-tracer.eloquent_manager_data'],
                $app['zipkin-tracer.http_client_manager'],
                $app['zipkin-tracer.custom_span_service']
            );
        });
    }

    protected function registerAliases()
    {
        $this->app->alias('zipkin-tracer.eloquent_manager_data', EloquentSourceManagerData::class);
        $this->app->alias('zipkin-tracer.event_subsriber', EventSubscriber::class);
        $this->app->alias('zipkin-tracer.http_request_manager_data', HttpRequestManagerData::class);
        $this->app->alias('zipkin-tracer.data_collector', DataCollectorService::class);
        $this->app->alias('zipkin-tracer.http_client_manager', HttpClientManagerData::class);
        $this->app->alias('zipkin-tracer.custom_span_service', CustomSpanService::class);
    }

    private function getConfig($key, $default = null)
    {
        return $this->app['config']->get("zipkin-tracer.$key", $default);
    }

    public function provides()
    {
        return ['zipkin-tracer'];
    }
}
