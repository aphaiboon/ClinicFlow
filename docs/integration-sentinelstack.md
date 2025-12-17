# SentinelStack Integration Guide

**Audience:** Application developers, platform engineers, security teams  
**Purpose:** Integrate ClinicFlow with SentinelStack for observability, security, and compliance

---

## 1. What Is SentinelStack?

SentinelStack is a **domain-aware observability and security platform**.

It ingests **structured application events** and converts them into:

- Operational insights (health, performance, reliability)
- Security signals (auth patterns, suspicious behavior)
- Compliance-ready audit trails (who did what, when, and why)
- Actionable incidents (correlated, contextual, explainable)

SentinelStack operates at the **application and business domain level**, not raw OS logs.

---

## 2. Integration Philosophy

### Core Principles
1. Structured events over raw logs  
2. Asynchronous delivery (never block business logic)  
3. Idempotent ingestion  
4. Business context over volume  
5. Security and compliance by default  

### High-Level Flow
Application → Structured Events → Queue → SentinelStack Client → SentinelStack API

---

## 3. Required Event Envelope (MANDATORY)

All events MUST include the following envelope:

```json
{
  "event_id": "evt_01J7MZK6ZQ9K4B3N9K6H9WQF3A",
  "event_type": "audit_log",
  "timestamp": "2025-12-17T21:30:20Z",
  "service": {
    "service_id": "clinicflow",
    "version": "1.12.0",
    "instance_id": "app-server-01",
    "region": "us-west-2"
  },
  "environment": "production",
  "tenant_id": "tenant_123",
  "actor": {
    "user_id": "user_123",
    "user_email": "user@example.com",
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0..."
  },
  "correlation": {
    "request_id": "req_abc",
    "trace_id": "trace_xyz",
    "session_id": "sess_123"
  },
  "payload": {}
}
```

**Envelope Fields:**
- `event_id`: ULID with "evt_" prefix (e.g., `evt_01J7MZK6ZQ9K4B3N9K6H9WQF3A`)
- `event_type`: One of the supported event types (see Section 4)
- `timestamp`: ISO 8601 UTC format (e.g., `2025-12-17T21:30:20Z`)
- `service`: Service metadata (service_id, version, instance_id, region)
- `environment`: Environment name (e.g., "production", "staging", "development")
- `tenant_id`: Tenant identifier (for multi-tenant scenarios)
- `actor`: User context (user_id, user_email, ip_address, user_agent)
- `correlation`: Request correlation IDs (request_id, trace_id, session_id)
- `payload`: Event-specific data

---

## 4. Supported Event Types

### health_status
Used for availability and dependency checks.

### service_metrics
Aggregated performance and throughput metrics.

### incident
Operational failures or degradations.

### audit_log
Compliance-grade record of sensitive actions. Used for HIPAA compliance tracking.

### access_control
Authentication and authorization events.

### domain_event
Business-meaningful actions (key differentiator). ClinicFlow uses this for:
- `patient.created`, `patient.updated`
- `appointment.scheduled`, `appointment.updated`, `appointment.cancelled`
- `room.assigned`

---

## 5. Delivery Guarantees

- Idempotent ingestion via event_id
- Exponential backoff retries
- Dead-letter queue required
- Never block application execution

---

## 6. Ingest API Contract

```
POST /api/v1/ingest/events
```

- Accepts single or batch events
- Returns 202 Accepted on success
- Processing is asynchronous

---

## 7. Security Requirements

- HTTPS/TLS required
- API keys stored securely
- Keys scoped by service and environment
- No PHI in payloads (HIPAA compliance)

---

## 8. Configuration Example

```env
SENTINELSTACK_ENABLED=true
SENTINELSTACK_API_URL=https://api.sentinelstack.com
SENTINELSTACK_API_KEY=sk_live_xxx
SENTINELSTACK_SERVICE_ID=clinicflow
SENTINELSTACK_ENVIRONMENT=production
SENTINELSTACK_SERVICE_VERSION=1.12.0
SENTINELSTACK_INSTANCE_ID=app-server-01
SENTINELSTACK_REGION=us-west-2
SENTINELSTACK_TENANT_ID=tenant_123
SENTINELSTACK_QUEUE_CONNECTION=redis
```

---

## 9. Integration Checklist

- Structured events only
- Required envelope enforced
- Async queue delivery
- Retry + DLQ implemented
- Domain events implemented
- Audit logs are PHI-safe

---

## 10. ClinicFlow Implementation

ClinicFlow implements SentinelStack integration through:

1. **Event ID Generation**: `EventIdGenerator` service generates ULID-based event IDs with "evt_" prefix
2. **Request Context Middleware**: `CaptureRequestContext` middleware captures request_id, trace_id, and session_id globally
3. **Event Envelope Builder**: `EventEnvelopeBuilder` service builds standardized event envelopes with all required fields
4. **SentinelStack Client**: `SentinelStackClientInterface` with `ingestEvent()` and `ingestEvents()` methods
5. **Domain Event Forwarding**: `ForwardToSentinelStack` listener forwards domain events as `domain_event` type
6. **Audit Log Forwarding**: Audit logs forwarded as `audit_log` event type for compliance

All integration uses Laravel's queue system (`ShouldQueue`) to ensure asynchronous, non-blocking delivery.

---

**SentinelStack turns application behavior into understanding.**
