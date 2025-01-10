<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;

class TraceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        putenv('OTEL_SERVICE_NAME=php-laravel-app');
        putenv('OTEL_EXPORTER_OTLP_INSECURE=true');

        $transport = (new OtlpHttpTransportFactory())->create('http://ingest.prayoga-apm.jagamaya.com:4318/v1/traces', 'application/x-protobuf', [
            'Authorization' => 'Basic aW5nZXN0OjVlR0J2VmsyOU9DVlVUSQ==',
        ]);
        $exporter = new SpanExporter($transport);

        $tracerProvider = new TracerProvider(new SimpleSpanProcessor($exporter));
        $tracer = $tracerProvider->getTracer('io.signoz.laravel.php');

        $rootSpan = $tracer->spanBuilder('root')->startSpan();
        $rootScope = $rootSpan->activate();

        $span = $tracer->spanBuilder('request')->startSpan();
        $spanScope = $span->activate();

        $span->addEvent('request_payload', [
            'url' => $request->url(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        $response = $next($request);

        $span->addEvent('response_sent', [
            'status_code' => $response->status(),
            'response_time' => microtime(true) - LARAVEL_START,
        ]);

        $span->end();
        $spanScope->detach();

        $rootSpan->end();
        $rootScope->detach();

        $tracerProvider->shutdown();

        return $response;
    }
}
