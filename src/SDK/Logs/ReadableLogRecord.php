<?php

declare(strict_types=1);

namespace OpenTelemetry\SDK\Logs;

use OpenTelemetry\API\Logs\LogRecord;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContextInterface;
use OpenTelemetry\Context\Context;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\AttributesInterface;
use OpenTelemetry\SDK\Common\Instrumentation\InstrumentationScopeInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;

/**
 * @see https://github.com/open-telemetry/opentelemetry-specification/blob/main/specification/logs/data-model.md#log-and-event-record-definition
 * "Note: Typically this will be implemented with a new interface or (immutable) value type."
 */
class ReadableLogRecord extends LogRecord
{
    private InstrumentationScopeInterface $scope;
    private LoggerSharedState $loggerSharedState;
    protected AttributesInterface $convertedAttributes;
    protected SpanContextInterface $spanContext;

    public function __construct(InstrumentationScopeInterface $scope, LoggerSharedState $loggerSharedState, LogRecord $logRecord, bool $includeTraceContext)
    {
        $this->scope = $scope;
        $this->loggerSharedState = $loggerSharedState;

        parent::__construct($logRecord->body);
        $this->timestamp = $logRecord->timestamp;
        $this->observedTimestamp = $logRecord->observedTimestamp;
        $this->context = $logRecord->context;
        if ($includeTraceContext) {
            $context = $this->context ?? Context::getCurrent();
            $this->spanContext = Span::fromContext($context)->getContext();
        };
        $this->severityNumber = $logRecord->severityNumber;
        $this->severityText = $logRecord->severityText;

        //convert attributes now so that excess data is not sent to processors
        $this->convertedAttributes = $this->loggerSharedState
            ->getLogRecordLimits()
            ->getAttributeFactory()
            ->builder($logRecord->attributes)
            ->build();
    }

    public function getInstrumentationScope(): InstrumentationScopeInterface
    {
        return $this->scope;
    }

    public function getResource(): ResourceInfo
    {
        return $this->loggerSharedState->getResource();
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    public function getObservedTimestamp(): ?int
    {
        return $this->observedTimestamp;
    }

    public function getContext(): ?ContextInterface
    {
        return $this->context;
    }

    public function getSpanContext(): ?SpanContextInterface
    {
        return $this->spanContext;
    }

    public function getSeverityNumber(): ?int
    {
        return $this->severityNumber;
    }

    public function getSeverityText(): ?string
    {
        return $this->severityText;
    }

    /**
     * @return mixed|null
     */
    public function getBody()
    {
        return $this->body;
    }

    public function getAttributes(): AttributesInterface
    {
        return $this->convertedAttributes;
    }
}
