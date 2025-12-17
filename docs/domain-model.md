# ClinicFlow Domain Model

## Overview

The ClinicFlow domain model represents the core business entities and their relationships within an outpatient clinic management system. All entities are designed to support synthetic data generation and demonstrate healthcare application patterns without handling real Protected Health Information (PHI).

## Core Entities

### Patient

Represents a registered patient in the clinic system. Contains synthetic demographic information for demonstration purposes.

**Key Attributes:**
- `id`: Unique identifier
- `medical_record_number`: Unique medical record identifier
- `first_name`: Patient first name
- `last_name`: Patient last name
- `date_of_birth`: Patient date of birth
- `gender`: Patient gender (enum)
- `phone`: Contact phone number
- `email`: Contact email address (optional)
- `address_line_1`: Primary address line
- `address_line_2`: Secondary address line (optional)
- `city`: City
- `state`: State or province
- `postal_code`: Postal or ZIP code
- `country`: Country code
- `created_at`: Record creation timestamp
- `updated_at`: Record last update timestamp

**Business Rules:**
- Medical record number must be unique
- Date of birth must be in the past
- Email, if provided, must be valid format
- All address fields required except address_line_2

### Appointment

Represents a scheduled appointment between a patient and a clinician.

**Key Attributes:**
- `id`: Unique identifier
- `patient_id`: Foreign key to Patient
- `user_id`: Foreign key to User (clinician/staff)
- `exam_room_id`: Foreign key to ExamRoom (nullable, assigned later)
- `appointment_date`: Scheduled date
- `appointment_time`: Scheduled time
- `duration_minutes`: Expected appointment duration
- `appointment_type`: Type of appointment (enum: routine, follow_up, consultation, etc.)
- `status`: Appointment status (enum: scheduled, in_progress, completed, cancelled, no_show)
- `notes`: Additional appointment notes (optional)
- `cancelled_at`: Cancellation timestamp (nullable)
- `cancellation_reason`: Reason for cancellation (nullable)
- `created_at`: Record creation timestamp
- `updated_at`: Record last update timestamp

**Business Rules:**
- Appointment date and time must be in the future when created
- Duration must be positive
- Status transitions must be valid (scheduled → in_progress → completed, or scheduled → cancelled)
- Cannot schedule overlapping appointments for same clinician
- Cannot assign multiple appointments to same exam room at overlapping times
- Cancellation requires cancellation_reason if status is cancelled

### ExamRoom

Represents a physical exam room within the clinic.

**Key Attributes:**
- `id`: Unique identifier
- `room_number`: Unique room identifier (string)
- `name`: Display name for the room
- `floor`: Floor number where room is located
- `equipment`: JSON array of available equipment
- `capacity`: Maximum occupancy
- `is_active`: Whether room is currently available for use
- `notes`: Additional room information (optional)
- `created_at`: Record creation timestamp
- `updated_at`: Record last update timestamp

**Business Rules:**
- Room number must be unique
- Capacity must be positive
- Only active rooms can be assigned to appointments
- Equipment list stored as JSON for flexibility

### User

Represents a system user (clinician or administrative staff). Extends Laravel's base User model with additional clinic-specific attributes.

**Key Attributes:**
- `id`: Unique identifier
- `name`: Full name
- `email`: Email address (unique)
- `role`: User role (enum: admin, clinician, receptionist)
- `email_verified_at`: Email verification timestamp
- `password`: Hashed password
- `two_factor_secret`: Two-factor authentication secret (encrypted)
- `two_factor_recovery_codes`: Recovery codes (encrypted)
- `two_factor_confirmed_at`: 2FA confirmation timestamp
- `created_at`: Record creation timestamp
- `updated_at`: Record last update timestamp

**Business Rules:**
- Email must be unique
- Role must be valid enum value
- Password must meet security requirements (handled by Laravel)
- Two-factor authentication optional but recommended for sensitive roles

### AuditLog

Represents an immutable audit log entry for compliance and accountability.

**Key Attributes:**
- `id`: Unique identifier
- `user_id`: Foreign key to User (who performed action)
- `action`: Action performed (enum: create, read, update, delete, login, logout, etc.)
- `resource_type`: Type of resource affected (e.g., Patient, Appointment)
- `resource_id`: ID of affected resource
- `changes`: JSON object representing state changes (before/after)
- `ip_address`: IP address of request
- `user_agent`: User agent string
- `metadata`: Additional context as JSON
- `created_at`: Action timestamp (immutable)

**Business Rules:**
- All audit logs are append-only (no updates or deletes)
- Changes field captures before/after state for update actions
- IP address and user agent recorded for security auditing
- Metadata field allows extensibility without schema changes

## Entity Relationships

```mermaid
erDiagram
    Patient ||--o{ Appointment : "has"
    User ||--o{ Appointment : "schedules"
    ExamRoom ||--o{ Appointment : "assigned to"
    User ||--o{ AuditLog : "performs"
    
    Patient {
        bigint id PK
        string medical_record_number UK
        string first_name
        string last_name
        date date_of_birth
        enum gender
        string phone
        string email
        string address_line_1
        string address_line_2
        string city
        string state
        string postal_code
        string country
        timestamp created_at
        timestamp updated_at
    }
    
    Appointment {
        bigint id PK
        bigint patient_id FK
        bigint user_id FK
        bigint exam_room_id FK
        date appointment_date
        time appointment_time
        integer duration_minutes
        enum appointment_type
        enum status
        text notes
        timestamp cancelled_at
        string cancellation_reason
        timestamp created_at
        timestamp updated_at
    }
    
    ExamRoom {
        bigint id PK
        string room_number UK
        string name
        integer floor
        json equipment
        integer capacity
        boolean is_active
        text notes
        timestamp created_at
        timestamp updated_at
    }
    
    User {
        bigint id PK
        string name
        string email UK
        enum role
        timestamp email_verified_at
        string password
        text two_factor_secret
        text two_factor_recovery_codes
        timestamp two_factor_confirmed_at
        timestamp created_at
        timestamp updated_at
    }
    
    AuditLog {
        bigint id PK
        bigint user_id FK
        enum action
        string resource_type
        bigint resource_id
        json changes
        string ip_address
        text user_agent
        json metadata
        timestamp created_at
    }
```

## Relationship Details

### Patient → Appointment (One-to-Many)

- A patient can have multiple appointments over time
- Appointments are retained even after patient record updates (historical integrity)
- Cascade delete not recommended; mark patient as inactive instead

### User → Appointment (One-to-Many)

- A user (clinician/staff) can be assigned to multiple appointments
- Supports tracking clinician workload and availability
- Foreign key enforces referential integrity

### ExamRoom → Appointment (One-to-Many)

- An exam room can be assigned to multiple appointments over time
- Supports room utilization tracking
- Nullable foreign key allows scheduling before room assignment
- Business logic prevents overlapping room assignments

### User → AuditLog (One-to-Many)

- Every user action generates an audit log entry
- Supports accountability and compliance auditing
- Foreign key ensures user cannot be deleted if audit logs exist

## Business Rules and Constraints

### Appointment Scheduling

1. **Time Validation**
   - Appointment time must be during clinic operating hours
   - Appointment date cannot be in the past
   - Duration must be reasonable (minimum 15 minutes, maximum 240 minutes)

2. **Availability Constraints**
   - Clinician cannot have overlapping appointments
   - Exam room cannot be double-booked for overlapping times
   - Room assignment optional at scheduling time but required before appointment start

3. **Status Transitions**
   - Valid transitions: `scheduled → in_progress → completed`
   - Valid transitions: `scheduled → cancelled` (with reason)
   - Valid transitions: `scheduled → no_show` (marked by staff)
   - No reverse transitions (completed cannot become scheduled)

### Data Integrity

1. **Referential Integrity**
   - All foreign keys enforced at database level
   - Soft deletes considered but not implemented (hard deletes with audit trail)

2. **Uniqueness Constraints**
   - Medical record number unique per patient
   - Room number unique per exam room
   - Email unique per user
   - No duplicate appointments (same patient, clinician, date, time)

3. **Audit Trail Requirements**
   - All create, update, delete operations logged
   - Read operations logged for sensitive data access
   - Authentication events (login, logout) logged
   - Changes captured as before/after JSON for updates

## Data Generation Guidelines

Since ClinicFlow uses only synthetic data:

1. **Patient Data**
   - Use Faker library for name generation
   - Generate realistic but fake dates of birth
   - Use randomly generated phone numbers and emails
   - Ensure medical record numbers are unique and non-sequential

2. **Temporal Relationships**
   - Appointments should align with patient registration dates
   - Appointment history should show realistic patterns
   - Room assignments should follow logical clinic workflows

3. **Consistency**
   - Maintain referential integrity in generated data
   - Ensure realistic data volumes for demonstration
   - Generate data that supports typical clinic workflows

