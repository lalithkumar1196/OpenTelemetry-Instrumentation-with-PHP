<?php

use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Logs\EventLogger;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory; // Corrected class name
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Export\Stream\StreamTransportFactory;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;

require 'vendor/autoload.php';

$resource = ResourceInfoFactory::emptyResource()->merge(ResourceInfo::create(Attributes::create([
    ResourceAttributes::SERVICE_NAMESPACE => 'demo',
    ResourceAttributes::SERVICE_NAME => 'test-application',
    ResourceAttributes::SERVICE_VERSION => '0.1',
    ResourceAttributes::DEPLOYMENT_ENVIRONMENT => 'development',
])));

// Replace 'http://localhost:4318' with your actual collector endpoint
$otlpHttpEndpoint = 'http://localhost:4318';

// Create OTLP HTTP exporter for traces (spans)
$spanExporter = new SpanExporter(
    (new OtlpHttpTransportFactory())->create($otlpHttpEndpoint . '/v1/traces', 'application/json')
);

// Create OTLP HTTP exporter for logs
$logExporter = new LogsExporter(
    (new OtlpHttpTransportFactory())->create($otlpHttpEndpoint . '/v1/logs', 'application/json')
);

// Create OTLP HTTP exporter for metrics
$reader = new ExportingReader(
    new MetricExporter(
        (new OtlpHttpTransportFactory())->create($otlpHttpEndpoint . '/v1/metrics', 'application/json')
    )
);

// Create MeterProvider for metrics
$meterProvider = MeterProvider::builder()
    ->setResource($resource)
    ->addReader($reader)
    ->build();

// Create TracerProvider for traces (spans)
$tracerProvider = TracerProvider::builder()
    ->addSpanProcessor(
        new SimpleSpanProcessor($spanExporter)
    )
    ->setResource($resource)
    ->setSampler(new ParentBased(new AlwaysOnSampler()))
    ->build();

// Create LoggerProvider for logs
$loggerProvider = LoggerProvider::builder()
    ->setResource($resource)
    ->addLogRecordProcessor(
        new SimpleLogRecordProcessor($logExporter)
    )
    ->build();

// Build and register the global SDK
Sdk::builder()
    ->setTracerProvider($tracerProvider)
    ->setMeterProvider($meterProvider)
    ->setLoggerProvider($loggerProvider)
    ->setPropagator(TraceContextPropagator::getInstance())
    ->setAutoShutdown(true)
    ->buildAndRegisterGlobal();
$logger = $loggerProvider->getLogger('demo', '1.0', 'http://schema.url', [/*attributes*/]);
$eventLogger = new EventLogger($logger, 'my-domain');
$record = (new LogRecord('hello world'))
    ->setSeverityText('INFO')
    ->setAttributes([/*attributes*/]);

$eventLogger->logEvent('foo', $record);

$up_down = $meterProvider
    ->getMeter('demo_meter')
    ->createUpDownCounter('queued', 'jobs', 'The number of jobs enqueued');
//jobs come in
$up_down->add(5);
//job completed
$up_down->add(-1);
//more jobs come in
$up_down->add(2);

$meterProvider->forceFlush();

$tracer = $tracerProvider->getTracer(
  'instrumentation-library-name', //name (required)
  '1.0.0', //version
  'http://example.com/my-schema', //schema url
  ['foo' => 'bar'] //attributes
);
$span = $tracer->spanBuilder("my span")->startSpan();
//do some work
$span->end();

