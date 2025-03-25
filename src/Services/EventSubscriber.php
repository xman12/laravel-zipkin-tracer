<?php

namespace ZipkinTracer\Services;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Events\{ConnectionFailed, RequestSending, ResponseReceived};

class EventSubscriber
{
    public function __construct(
        private EventDispatcher           $eventDispatcher,
        private EloquentSourceManagerData $eloquentSourceManagerData,
        private HttpClientManagerData $httpClientManagerData,
    )
    {
    }

    public function listenToEvents(): void
    {
        $this->eventDispatcher->listen(QueryExecuted::class, function ($event) {
            $this->eloquentSourceManagerData->addQuery($event);
        });

        $this->eventDispatcher->listen(TransactionBeginning::class, function ($event) {
            $this->eloquentSourceManagerData->addTransactionQuery($event);
        });

        $this->eventDispatcher->listen(TransactionCommitted::class, function ($event) {
            $this->eloquentSourceManagerData->addTransactionQuery($event);
        });

        $this->eventDispatcher->listen(TransactionRolledBack::class, function ($event) {
            $this->eloquentSourceManagerData->addTransactionQuery($event);
        });

        $this->eventDispatcher->listen(ConnectionFailed::class, function ($event) {
            $this->httpClientManagerData->handleConnectionFailed($event);
        });
        $this->eventDispatcher->listen(RequestSending::class, function ($event) {
            $this->httpClientManagerData->handleSendingRequest($event);
        });
        $this->eventDispatcher->listen(ResponseReceived::class, function ($event) {
            $this->httpClientManagerData->handleResponseReceived($event);
        });
    }
}
