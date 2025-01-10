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
        // Initialize environment variables
        putenv('OTEL_SERVICE_NAME=php-laravel-app');
        putenv('OTEL_EXPORTER_OTLP_INSECURE=true');

        // Create the transport for OTLP Export
        $transport = (new OtlpHttpTransportFactory())->create('http://ingest.prayoga-apm.jagamaya.com:4318/v1/traces', 'application/x-protobuf', [
            'Authorization' => 'Basic aW5nZXN0OjVlR0J2VmsyOU9DVlVUSQ==',
        ]);
        $exporter = new SpanExporter($transport);

        // Set up the Tracer provider
        $tracerProvider = new TracerProvider(new SimpleSpanProcessor($exporter));
        $tracer = $tracerProvider->getTracer('io.signoz.laravel.php');

        // Start the root span and set it as the active span
        $rootSpan = $tracer->spanBuilder('root')->startSpan();
        $rootScope = $rootSpan->activate();

        // Start the request span and register events
        $span = $tracer->spanBuilder('request')->startSpan();
        $spanScope = $span->activate();  // This will also need to be deactivated at the end

        // Add events for the request
        $span->addEvent('request_payload', [
            'url' => $request->url(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        // Get the response after the request has been handled
        $response = $next($request);

        // Add events for the response sent
        $span->addEvent('response_sent', [
            'status_code' => $response->status(),
            'response_time' => microtime(true) - LARAVEL_START,
        ]);

        // End the request span and its scope
        $span->end();
        $spanScope->detach();  // Properly detach the span's scope

        // End the root span and its scope
        $rootSpan->end();
        $rootScope->detach();  // Properly detach the root span's scope

        // Shut down the tracer provider
        $tracerProvider->shutdown();

        return $response;
    }
}
