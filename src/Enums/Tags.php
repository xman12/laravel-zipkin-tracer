<?php

namespace ZipkinTracer\Enums;

enum Tags: string
{
    case HTTP_HOST = 'http.host';
    case HTTP_METHOD = 'http.method';
    case HTTP_URL = 'http.url';
    case HTTP_PATH = 'http.path';
    case HTTP_ROUTE = 'http.route';
    case HTTP_STATUS_CODE = 'http.status_code';
    case HTTP_REQUEST_SIZE = 'http.request.size';
    case HTTP_RESPONSE_SIZE = 'http.response.size';
    case SQL_QUERY = 'sql.query';
    case ERROR = 'error';
}
