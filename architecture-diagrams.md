# Диаграммы архитектуры Laravel Zipkin Tracer

## 1. Общая архитектура системы

```mermaid
graph TB
    subgraph "Laravel Application"
        A[HTTP Request] --> B[ZipkinTracerMiddleware]
        B --> C[EventSubscriber]
        C --> D[EloquentSourceManagerData]
        C --> E[HttpClientManagerData]
        B --> F[DataCollectorService]
        D --> F
        E --> F
        G[CustomSpanService] --> F
        F --> H[Local Storage JSON Files]
    end
    
    subgraph "Background Process"
        I[Cron Job] --> J[SyncDataCommand]
        J --> H
        J --> K[Zipkin Server]
    end
    
    subgraph "Zipkin Ecosystem"
        K --> L[Zipkin UI]
        K --> M[Zipkin API]
    end
    
    style A fill:#e1f5fe
    style K fill:#c8e6c9
    style H fill:#fff3e0
    style L fill:#f3e5f5
```

## 2. Детальная схема сбора данных

```mermaid
flowchart TD
    A[HTTP Request] --> B{Request Processing}
    B --> C[Start Timer]
    C --> D[EventSubscriber Listeners]
    
    D --> E[Database Events]
    D --> F[HTTP Client Events]
    D --> G[Custom Spans]
    
    E --> H[QueryExecuted]
    E --> I[TransactionBeginning]
    E --> J[TransactionCommitted]
    E --> K[TransactionRolledBack]
    
    F --> L[RequestSending]
    F --> M[ResponseReceived]
    F --> N[ConnectionFailed]
    
    H --> O[EloquentSourceManagerData]
    I --> O
    J --> O
    K --> O
    
    L --> P[HttpClientManagerData]
    M --> P
    N --> P
    
    G --> Q[CustomSpanService]
    
    O --> R[DataCollectorService]
    P --> R
    Q --> R
    
    R --> S[Create JSON File]
    S --> T[Store in Local Storage]
    
    style A fill:#e1f5fe
    style T fill:#fff3e0
    style R fill:#e8f5e8
```

## 3. Процесс синхронизации с Zipkin

```mermaid
sequenceDiagram
    participant Cron as Cron Job
    participant Command as SyncDataCommand
    participant Storage as Local Storage
    participant Zipkin as Zipkin Server
    participant UI as Zipkin UI

    Cron->>Command: Execute zipkin-tracer:sync_data
    Command->>Storage: Read JSON files
    Storage-->>Command: Return file list
    
    loop For each JSON file
        Command->>Storage: Read file content
        Storage-->>Command: Return JSON data
        
        Command->>Command: Parse data (HTTP, SQL, Custom spans)
        Command->>Command: Create Zipkin spans
        
        Command->>Zipkin: Send spans via HTTP POST
        Zipkin-->>Command: 202 Accepted
        
        Command->>Storage: Delete processed file
    end
    
    Command->>Command: Log completion
    
    Note over UI: User can view traces
    UI->>Zipkin: Query traces
    Zipkin-->>UI: Return trace data
```

## 4. Сравнение с OpenTelemetry

```mermaid
graph LR
    subgraph "Laravel Zipkin Tracer"
        A1[Simple Setup] --> A2[Laravel Native]
        A2 --> A3[Zipkin Only]
        A3 --> A4[File Storage]
        A4 --> A5[Async Sync]
    end
    
    subgraph "OpenTelemetry"
        B1[Complex Setup] --> B2[Framework Agnostic]
        B2 --> B3[Multiple Backends]
        B3 --> B4[Direct Export]
        B4 --> B5[Real-time]
    end
    
    A1 -.->|"Easier"| B1
    A2 -.->|"Better Laravel Integration"| B2
    A3 -.->|"Limited"| B3
    A4 -.->|"Buffered"| B4
    A5 -.->|"Delayed"| B5
    
    style A1 fill:#e8f5e8
    style B1 fill:#ffebee
```

## 5. Структура данных трейсинга

```mermaid
classDiagram
    class RequestDTO {
        +string method
        +string url
        +int statusCode
        +int requestSize
        +int responseSize
        +float time
        +string requestId
        +BaseException exception
    }
    
    class DBQueryDTO {
        +string query
        +float duration
        +float startTime
        +string executeFile
        +int executeFileLine
    }
    
    class HttpRequestDTO {
        +string method
        +string url
        +array headers
        +float durationTime
        +int statusCode
        +string error
    }
    
    class CustomSpansDTO {
        +string name
        +array result
        +float startTime
        +float durationTime
        +string executeFile
        +int executeFileLine
        +BaseException exception
        +array childSpans
    }
    
    class DataCollectorService {
        +store()
    }
    
    DataCollectorService --> RequestDTO
    DataCollectorService --> DBQueryDTO
    DataCollectorService --> HttpRequestDTO
    DataCollectorService --> CustomSpansDTO
```

## 6. Жизненный цикл запроса

```mermaid
stateDiagram-v2
    [*] --> RequestReceived
    RequestReceived --> MiddlewareProcessing
    MiddlewareProcessing --> EventListening
    EventListening --> DataCollection
    DataCollection --> ResponseGeneration
    ResponseGeneration --> DataStorage
    DataStorage --> RequestComplete
    
    state DataCollection {
        [*] --> CollectHTTP
        CollectHTTP --> CollectSQL
        CollectSQL --> CollectCustom
        CollectCustom --> [*]
    }
    
    state DataStorage {
        [*] --> CreateJSON
        CreateJSON --> WriteFile
        WriteFile --> [*]
    }
    
    RequestComplete --> [*]
```

## 7. Архитектура хранения данных

```mermaid
graph TD
    subgraph "Application Layer"
        A[HTTP Request] --> B[Data Collection]
        B --> C[JSON File Creation]
    end
    
    subgraph "Storage Layer"
        C --> D[Local File System]
        D --> E[Request ID Files]
        E --> F[req_123.json]
        E --> G[req_124.json]
        E --> H[req_125.json]
    end
    
    subgraph "Sync Layer"
        I[Cron Job] --> J[File Scanner]
        J --> K[File Processor]
        K --> L[Zipkin Sender]
        L --> M[File Cleanup]
    end
    
    F --> J
    G --> J
    H --> J
    
    style D fill:#fff3e0
    style I fill:#e8f5e8
```

## 8. Сравнение производительности

```mermaid
graph LR
    subgraph "Laravel Zipkin Tracer"
        A1[Minimal Overhead] --> A2[File I/O Only]
        A2 --> A3[Async Processing]
        A3 --> A4[No Network Blocking]
    end
    
    subgraph "OpenTelemetry"
        B1[Higher Overhead] --> B2[Direct Network Calls]
        B2 --> B3[Real-time Export]
        B3 --> B4[Network Blocking]
    end
    
    A1 -.->|"Faster"| B1
    A2 -.->|"Local"| B2
    A3 -.->|"Background"| B3
    A4 -.->|"Non-blocking"| B4
    
    style A1 fill:#e8f5e8
    style B1 fill:#ffebee
``` 