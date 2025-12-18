# ClinicFlow Data Flow

## Overview

This document describes the data flows and operational sequences within ClinicFlow, including normal operation flows, incident handling, and integration with SentinelStack for monitoring and audit purposes.

## Normal Operation Flows

### Patient Authentication Flow

```mermaid
sequenceDiagram
    participant Patient as Patient
    participant Frontend as React Frontend
    participant Backend as Laravel Backend
    participant AuthService as PatientAuthService
    participant Cache as Cache Layer
    participant Email as Email Service
    participant DB as Database

    Patient->>Frontend: Enter email address
    Frontend->>Backend: POST /patient/login (email)
    Backend->>Backend: Validate email exists
    Backend->>AuthService: sendMagicLink(email)
    AuthService->>DB: Find patient by email
    DB-->>AuthService: Patient entity
    AuthService->>AuthService: Generate secure token
    AuthService->>Cache: Store token (30 min expiration)
    AuthService->>Email: Send magic link email
    Email-->>Patient: Magic link email
    Backend-->>Frontend: Success message
    Frontend->>Patient: Display "Check your email"
    
    Patient->>Email: Click magic link
    Email->>Backend: GET /patient/verify/{token}
    Backend->>AuthService: verifyMagicLink(token)
    AuthService->>Cache: Retrieve patient ID
    Cache-->>AuthService: Patient ID
    AuthService->>Cache: Remove token (single-use)
    AuthService->>DB: Load patient
    DB-->>AuthService: Patient entity
    AuthService->>Backend: Authenticate patient
    Backend->>Backend: Create session
    Backend-->>Frontend: Redirect to dashboard
    Frontend->>Patient: Display patient portal
```

**Key Steps:**
1. Patient enters email address on login page
2. System validates email exists in database
3. Service generates secure token and stores in cache (30-minute expiration)
4. Magic link email sent to patient
5. Patient clicks link in email
6. System verifies token and retrieves patient ID
7. Token removed from cache (single-use)
8. Patient authenticated and session created
9. Patient redirected to dashboard

### Patient Portal Access Flow

```mermaid
sequenceDiagram
    participant Patient as Patient
    participant Frontend as React Frontend
    participant Backend as Laravel Backend
    participant Middleware as Auth Middleware
    participant Controller as Patient Controller
    participant Service as Patient Service
    participant DB as Database

    Patient->>Frontend: Navigate to portal route
    Frontend->>Backend: GET /patient/dashboard
    Backend->>Middleware: Check auth:patient guard
    alt Not authenticated
        Middleware-->>Frontend: Redirect to /patient/login
        Frontend->>Patient: Show login page
    else Authenticated
        Middleware->>Controller: Proceed to controller
        Controller->>Service: Get patient data
        Service->>DB: Query patient appointments
        DB-->>Service: Appointment data
        Service-->>Controller: Formatted data
        Controller->>Frontend: Inertia response
        Frontend->>Patient: Display portal page
    end
```

**Key Steps:**
1. Patient attempts to access portal route
2. Middleware checks `auth:patient` guard
3. If not authenticated, redirect to login
4. If authenticated, load patient data and appointments
5. Return Inertia response with data
6. Frontend renders patient portal

### Patient Appointment Management Flow

```mermaid
sequenceDiagram
    participant Patient as Patient
    participant Frontend as React Frontend
    participant Backend as Laravel Backend
    participant Service as PatientAppointmentService
    participant DB as Database
    participant Audit as Audit Logger

    Patient->>Frontend: View appointments
    Frontend->>Backend: GET /patient/appointments
    Backend->>Service: getPatientAppointments(patientId)
    Service->>DB: Query patient appointments
    DB-->>Service: Appointment collection
    Service-->>Backend: Filtered appointments
    Backend->>Frontend: Return appointments
    Frontend->>Patient: Display appointment list

    Patient->>Frontend: Cancel appointment
    Frontend->>Backend: POST /patient/appointments/{id}/cancel
    Backend->>Service: cancelAppointment(patient, appointment, reason)
    Service->>Service: Check cancellation rules (24-hour window)
    alt Can cancel
        Service->>DB: Update appointment status
        Service->>Audit: Log cancellation
        DB-->>Service: Updated appointment
        Service-->>Backend: Success
        Backend->>Frontend: Success message
        Frontend->>Patient: Display confirmation
    else Cannot cancel
        Service-->>Backend: Error (too close to appointment)
        Backend->>Frontend: Error message
        Frontend->>Patient: Display error
    end
```

**Key Steps:**
1. Patient views their appointments (filtered by patient ID)
2. Patient attempts to cancel appointment
3. Service checks business rules (24-hour window, status)
4. If allowed, appointment status updated to cancelled
5. Audit log created for cancellation
6. Success or error response returned

### Patient Profile Management Flow

```mermaid
sequenceDiagram
    participant Patient as Patient
    participant Frontend as React Frontend
    participant Backend as Laravel Backend
    participant Service as PatientProfileService
    participant DB as Database
    participant Audit as Audit Logger

    Patient->>Frontend: View profile
    Frontend->>Backend: GET /patient/profile
    Backend->>Service: getEditableFields()
    Service-->>Backend: Editable fields list
    Backend->>Frontend: Return profile data
    Frontend->>Patient: Display profile

    Patient->>Frontend: Update profile
    Frontend->>Backend: PUT /patient/profile (data)
    Backend->>Service: updatePatientProfile(patient, data)
    Service->>Service: Remove immutable fields
    Service->>Service: Validate editable fields
    alt Valid data
        Service->>DB: Update patient record
        Service->>Audit: Log profile update
        DB-->>Service: Updated patient
        Service-->>Backend: Success
        Backend->>Frontend: Success message
        Frontend->>Patient: Display confirmation
    else Invalid data
        Service-->>Backend: Validation errors
        Backend->>Frontend: Error messages
        Frontend->>Patient: Display errors
    end
```

**Key Steps:**
1. Patient views their profile
2. Patient updates editable fields (email, phone, address)
3. Service removes immutable fields (MRN, DOB, name)
4. Service validates remaining fields
5. If valid, patient record updated
6. Audit log created for profile update
7. Success or validation errors returned

### Patient Registration Flow

```mermaid
sequenceDiagram
    participant User as Clinic Staff
    participant Frontend as React Frontend
    participant Backend as Laravel Backend
    participant Service as Patient Service
    participant DB as Database
    participant Audit as Audit Logger
    participant Sentinel as SentinelStack

    User->>Frontend: Enter patient information
    Frontend->>Backend: POST /patients (form submission)
    Backend->>Backend: Validate request data
    Backend->>Service: createPatient(data)
    Service->>DB: Generate medical record number
    Service->>DB: Create patient record
    DB-->>Service: Patient entity
    Service->>Audit: logAction(create, Patient, patientId)
    Audit->>DB: Create audit log entry
    Audit->>Sentinel: Send audit event
    Service-->>Backend: Patient entity
    Backend->>Frontend: Return patient data (Inertia response)
    Frontend->>User: Display confirmation
    Backend->>Sentinel: Send metrics (patient_created)
```

**Key Steps:**
1. User enters patient demographic information
2. Frontend validates client-side and submits to backend
3. Backend validates and authorizes request
4. Service layer generates unique medical record number
5. Patient record created in database
6. Audit log entry created for compliance
7. Event forwarded to SentinelStack for monitoring
8. Success response returned to frontend

### Appointment Scheduling Flow

```mermaid
sequenceDiagram
    participant User as Clinic Staff
    participant Frontend as React Frontend
    participant Backend as Laravel Backend
    participant Service as Appointment Service
    participant DB as Database
    participant Audit as Audit Logger
    participant Sentinel as SentinelStack

    User->>Frontend: Select patient, clinician, date/time
    Frontend->>Backend: POST /appointments
    Backend->>Backend: Validate request
    Backend->>Service: scheduleAppointment(data)
    Service->>DB: Check clinician availability
    Service->>DB: Check room availability (if specified)
    alt Conflict detected
        Service-->>Backend: Conflict error
        Backend-->>Frontend: Validation error
        Frontend->>User: Display error message
    else Available
        Service->>DB: Create appointment record
        DB-->>Service: Appointment entity
        Service->>Audit: logAction(create, Appointment, appointmentId)
        Audit->>DB: Create audit log
        Audit->>Sentinel: Send audit event
        Service-->>Backend: Appointment entity
        Backend->>Frontend: Return appointment data
        Frontend->>User: Display confirmation
        Backend->>Sentinel: Send metrics (appointment_scheduled)
    end
```

**Key Steps:**
1. User selects patient, clinician, and appointment time
2. System validates availability constraints
3. Conflicts checked against existing appointments
4. If available, appointment created
5. Audit log entry created
6. Event sent to SentinelStack
7. Success or error response returned

### Exam Room Assignment Flow

```mermaid
sequenceDiagram
    participant User as Clinic Staff
    participant Frontend as React Frontend
    participant Backend as Laravel Backend
    participant Service as Appointment Service
    participant DB as Database
    participant Audit as Audit Logger
    participant Sentinel as SentinelStack

    User->>Frontend: View appointment, select room
    Frontend->>Backend: PATCH /appointments/{id}/assign-room
    Backend->>Service: assignRoom(appointmentId, roomId)
    Service->>DB: Load appointment and room
    Service->>DB: Check room availability at appointment time
    alt Room unavailable
        Service-->>Backend: Conflict error
        Backend-->>Frontend: Validation error
    else Room available
        Service->>DB: Update appointment.exam_room_id
        Service->>DB: Load updated appointment
        Service->>Audit: logAction(update, Appointment, appointmentId)
        Audit->>DB: Create audit log with changes
        Audit->>Sentinel: Send audit event
        Service-->>Backend: Updated appointment
        Backend->>Frontend: Return updated data
        Frontend->>User: Display room assignment
        Backend->>Sentinel: Send metrics (room_assigned)
    end
```

**Key Steps:**
1. User selects exam room for existing appointment
2. System verifies room is active and available
3. Checks for scheduling conflicts at appointment time
4. Updates appointment with room assignment
5. Audit log captures before/after state
6. Event forwarded to SentinelStack
7. Updated appointment returned to frontend

## Incident Detection and Handling

### Error Detection Flow

```mermaid
sequenceDiagram
    participant App as Application
    participant Handler as Exception Handler
    participant Logger as Application Logger
    participant DB as Database
    participant Audit as Audit Logger
    participant Sentinel as SentinelStack

    App->>App: Exception thrown
    App->>Handler: Catch exception
    Handler->>Logger: Log error with context
    Logger->>DB: Store error log entry
    Handler->>Audit: logAction(error, Exception, exceptionId)
    Audit->>Sentinel: Send incident event
    
    alt Critical error
        Sentinel->>Sentinel: Create incident record
        Sentinel->>Sentinel: Trigger alert (if configured)
    else Non-critical error
        Sentinel->>Sentinel: Aggregate metrics
    end
    
    Handler->>App: Return error response
```

**Incident Types:**
- **Application Errors**: Exceptions, validation failures, business rule violations
- **Database Errors**: Connection failures, constraint violations, query timeouts
- **External Service Errors**: SentinelStack integration failures, third-party API errors
- **Security Events**: Failed authentication attempts, authorization violations, suspicious activity

### Audit Logging Flow

```mermaid
sequenceDiagram
    participant Service as Service Layer
    participant Audit as Audit Service
    participant DB as Database
    participant Sentinel as SentinelStack

    Service->>Audit: logAction(action, resource, resourceId, changes)
    Audit->>Audit: Serialize audit data
    Audit->>DB: Insert audit log (transaction)
    Audit->>Audit: Format event payload
    Audit->>Sentinel: Send audit event (async)
    
    alt DB transaction succeeds
        DB-->>Audit: Audit log created
        Audit-->>Service: Success
    else DB transaction fails
        DB-->>Audit: Rollback
        Audit->>Sentinel: Send failure event
        Audit-->>Service: Error
    end
```

**Audit Log Characteristics:**
- **Immutable**: Once created, audit logs cannot be modified or deleted
- **Complete**: Captures who, what, when, where, and why
- **Tamper-evident**: Timestamps and cryptographic hashing (future enhancement)
- **Queryable**: Indexed for efficient retrieval during audits

## SentinelStack Integration Flow

### Metrics Collection Flow

```mermaid
sequenceDiagram
    participant App as Application
    participant Middleware as Metrics Middleware
    participant Collector as Metrics Collector
    participant Sentinel as SentinelStack

    App->>Middleware: HTTP request received
    Middleware->>Collector: startTimer(requestId)
    App->>App: Process request
    App->>Middleware: HTTP response ready
    Middleware->>Collector: stopTimer(requestId, statusCode)
    Collector->>Collector: Calculate latency
    Collector->>Collector: Aggregate metrics
    Collector->>Sentinel: Send batch metrics (periodic)
    Sentinel->>Sentinel: Store metrics
    Sentinel->>Sentinel: Analyze trends
```

**Metrics Collected:**
- Request latency (p50, p95, p99)
- Error rates by endpoint
- Request volume by endpoint
- Database query performance
- Cache hit/miss ratios

### Event Forwarding Flow

```mermaid
sequenceDiagram
    participant App as Application
    participant Event as Event System
    participant Listener as SentinelStack Listener
    participant Queue as Job Queue
    participant Worker as Queue Worker
    participant Sentinel as SentinelStack

    App->>Event: Dispatch event (e.g., PatientCreated)
    Event->>Listener: Handle event
    Listener->>Listener: Format event payload
    Listener->>Queue: Queue SentinelStack event job
    Queue-->>Listener: Job queued
    Listener-->>Event: Event handled
    
    Worker->>Queue: Dequeue job
    Worker->>Sentinel: HTTP POST /api/events
    alt Success
        Sentinel-->>Worker: 200 OK
        Worker->>Queue: Mark job complete
    else Failure
        Sentinel-->>Worker: Error response
        Worker->>Queue: Retry job (with backoff)
    end
```

**Event Types:**
- **Audit Events**: All user actions (create, update, delete, read sensitive data)
- **Security Events**: Authentication, authorization failures, suspicious activity
- **Business Events**: Appointment scheduled, room assigned, patient registered
- **System Events**: Errors, performance degradation, health status changes

### Health Status Reporting

```mermaid
sequenceDiagram
    participant Scheduler as Scheduled Task
    participant Health as Health Check Service
    participant DB as Database
    participant Cache as Cache Layer
    participant Sentinel as SentinelStack

    Scheduler->>Health: Run health check (every 60s)
    Health->>DB: Test database connection
    DB-->>Health: Connection status
    Health->>Cache: Test cache connection
    Cache-->>Health: Connection status
    Health->>Health: Aggregate health status
    Health->>Sentinel: POST /api/health/status
    
    alt All systems healthy
        Health->>Sentinel: status: healthy
        Sentinel->>Sentinel: Update service status
    else Degraded service
        Health->>Sentinel: status: degraded
        Sentinel->>Sentinel: Create incident
        Sentinel->>Sentinel: Trigger alert
    else Service down
        Health->>Sentinel: status: down
        Sentinel->>Sentinel: Create critical incident
        Sentinel->>Sentinel: Trigger urgent alert
    end
```

**Health Check Components:**
- Database connectivity and query performance
- Cache availability and response time
- Disk space and system resources
- External service dependencies
- Application memory and performance metrics

## Data Flow Patterns

### Request-Response Pattern

Standard synchronous request handling:
1. Client sends HTTP request
2. Middleware processes (auth, logging, metrics)
3. Controller handles request
4. Service executes business logic
5. Database operations (transactional)
6. Audit logging
7. Response formatted and returned
8. Metrics updated

### Event-Driven Pattern

Asynchronous event processing:
1. Business event occurs
2. Event dispatched to listeners
3. Listeners process asynchronously
4. Queue jobs for external integrations
5. Workers process jobs with retry logic
6. External systems notified (SentinelStack)

### Audit-First Pattern

All mutations go through audit logging:
1. Before mutation: Capture current state
2. Execute mutation
3. After mutation: Capture new state
4. Create audit log entry with before/after
5. Forward audit event to SentinelStack
6. Return mutation result

This ensures complete audit trail even if mutation succeeds but audit fails (transaction rollback).

## Performance Considerations

### Batch Processing

- Audit events batched and sent periodically (every 5 seconds or 100 events)
- Metrics aggregated and sent in batches (every 30 seconds)
- Reduces network overhead and SentinelStack load

### Async Processing

- Non-critical events processed asynchronously via queue
- Prevents blocking user requests for external integrations
- Retry logic handles transient failures

### Caching Strategy

- Frequently accessed data cached (patient lookup, room availability)
- Cache invalidation on data mutations
- Reduces database load for read-heavy operations

