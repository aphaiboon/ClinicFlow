# Refactoring Plan: SOLID, DRY, KISS Principles

## Overview
This document outlines refactoring opportunities identified in the ClinicFlow codebase to improve adherence to SOLID, DRY, and KISS principles.

---

## 1. DRY Violations (Don't Repeat Yourself)

### 1.1 Duplicated Organization Data Fetching
**Location:** `app/Http/Controllers/AppointmentController.php`
**Issue:** Organization data fetching logic is duplicated across `index()`, `create()`, and `edit()` methods.

**Current Code Pattern:**
```php
$organization = auth()->user()->currentOrganization;
$clinicians = $organization?->users()
    ->wherePivotIn('role', [...])
    ->orderBy('name')
    ->get()
    ->values()
    ->toArray() ?? [];
```

**Refactoring:**
- Create `OrganizationDataService` or add methods to `OrganizationService`
- Extract to reusable methods: `getClinicians()`, `getPatients()`, `getExamRooms()`
- Methods should handle null organization gracefully

**Files to Modify:**
- `app/Http/Controllers/AppointmentController.php`
- `app/Services/OrganizationService.php` (or create new service)

---

### 1.2 Duplicated Time Parsing Logic
**Location:** `app/Services/AppointmentService.php`
**Issue:** Time parsing from string to Carbon is duplicated in multiple methods.

**Current Pattern:**
```php
$timeParts = explode(':', $data['appointment_time']);
$time = Carbon::createFromTime((int) $timeParts[0], (int) ($timeParts[1] ?? 0), (int) ($timeParts[2] ?? 0));
```

**Refactoring:**
- Create `TimeParser` utility class or add static method to `AppointmentService`
- Method signature: `parseTimeString(string $timeString): Carbon`

**Files to Modify:**
- `app/Services/AppointmentService.php`
- Consider: `app/Utils/TimeParser.php` (if used elsewhere)

---

### 1.3 Duplicated Conflict Checking Logic
**Location:** `app/Services/AppointmentService.php`
**Issue:** `checkRescheduleConflicts()` duplicates logic from `checkClinicianAvailability()` and `checkRoomAvailability()`.

**Current Issue:**
- `checkRescheduleConflicts()` has its own conflict detection logic
- `checkClinicianAvailability()` and `checkRoomAvailability()` have similar but different logic
- Overlap detection logic is duplicated

**Refactoring:**
- Extract overlap detection to a single method: `appointmentsOverlap(Carbon $start1, Carbon $end1, Carbon $start2, Carbon $end2): bool`
- Unify conflict checking to use the same underlying logic
- Create `ConflictChecker` service class to handle all conflict detection

**Files to Modify:**
- `app/Services/AppointmentService.php`
- Consider: `app/Services/AppointmentConflictService.php`

---

### 1.4 Duplicated Appointment Event Formatting
**Location:** `app/Http/Controllers/AppointmentController.php::calendar()`
**Issue:** Appointment to calendar event formatting logic is embedded in controller.

**Refactoring:**
- Extract to `AppointmentCalendarFormatter` service
- Method: `formatForCalendar(Appointment $appointment): array`
- Handles status color mapping, date formatting, extended props

**Files to Modify:**
- `app/Http/Controllers/AppointmentController.php`
- Create: `app/Services/AppointmentCalendarFormatter.php`

---

## 2. Single Responsibility Violations (SOLID - S)

### 2.1 AppointmentController::calendar() Does Too Much
**Location:** `app/Http/Controllers/AppointmentController.php::calendar()`
**Issues:**
- Builds complex query
- Applies role-based filtering
- Formats events for calendar
- Handles date range parsing

**Refactoring:**
- Extract query building to `AppointmentQueryBuilder` service
- Extract role-based filtering to `AppointmentFilterService`
- Extract formatting to `AppointmentCalendarFormatter` (see 1.4)
- Controller should only orchestrate and return response

**Files to Modify:**
- `app/Http/Controllers/AppointmentController.php`
- Create: `app/Services/AppointmentQueryBuilder.php`
- Create: `app/Services/AppointmentFilterService.php`

---

### 2.2 AppointmentService Has Multiple Responsibilities
**Location:** `app/Services/AppointmentService.php`
**Issues:**
- Handles scheduling
- Handles conflict checking
- Handles rescheduling
- Handles room assignment
- Handles status updates

**Refactoring:**
- Extract conflict checking to `AppointmentConflictService`
- Extract rescheduling to `AppointmentRescheduleService`
- Keep core CRUD operations in `AppointmentService`
- Use composition: `AppointmentService` depends on conflict/reschedule services

**Files to Modify:**
- `app/Services/AppointmentService.php`
- Create: `app/Services/AppointmentConflictService.php`
- Create: `app/Services/AppointmentRescheduleService.php`

---

### 2.3 AppointmentController::index() Has Query Logic
**Location:** `app/Http/Controllers/AppointmentController.php::index()`
**Issue:** Controller builds query with multiple filters instead of delegating.

**Refactoring:**
- Extract query building to `AppointmentQueryBuilder` service
- Controller passes request filters to service
- Service returns paginated results

**Files to Modify:**
- `app/Http/Controllers/AppointmentController.php`
- Create/Update: `app/Services/AppointmentQueryBuilder.php`

---

## 3. KISS Violations (Keep It Simple, Stupid)

### 3.1 Complex Nested Conditionals in Controllers
**Location:** `app/Http/Controllers/AppointmentController.php`
**Issue:** Multiple nested if statements make code hard to read.

**Refactoring:**
- Extract conditional logic to guard clauses
- Use early returns
- Extract complex conditions to named methods

**Example:**
```php
// Before
if ($request->has('status')) {
    if ($request->get('status') !== 'all') {
        $query->byStatus(...);
    }
}

// After
if ($this->shouldFilterByStatus($request)) {
    $query->byStatus(...);
}
```

---

### 3.2 Frontend Component Too Complex
**Location:** `resources/js/pages/Appointments/Index.tsx`
**Issue:** Component handles too many responsibilities:
- Filter state management
- Modal state management
- Event handling
- Calendar integration
- List view rendering

**Refactoring:**
- Extract filter logic to `useAppointmentFilters` hook
- Extract modal logic to `useAppointmentModals` hook
- Extract event handlers to `useAppointmentEvents` hook
- Split into smaller components

**Files to Modify:**
- `resources/js/pages/Appointments/Index.tsx`
- Create: `resources/js/hooks/useAppointmentFilters.ts`
- Create: `resources/js/hooks/useAppointmentModals.ts`
- Create: `resources/js/hooks/useAppointmentEvents.ts`

---

### 3.3 Overly Complex Conflict Detection
**Location:** `app/Services/AppointmentService.php::checkRescheduleConflicts()`
**Issue:** Method is long and handles multiple concerns.

**Refactoring:**
- Break into smaller methods: `checkClinicianConflicts()`, `checkRoomConflicts()`
- Extract conflict formatting to separate method
- Use strategy pattern if conflict types grow

---

## 4. Dependency Inversion Violations (SOLID - D)

### 4.1 Direct Carbon Usage Throughout
**Location:** Multiple services
**Issue:** Services directly use Carbon, making them hard to test.

**Refactoring:**
- Create `DateTimeInterface` abstraction
- Inject date/time provider
- Use Carbon as implementation

**Note:** This is lower priority unless testing becomes difficult.

---

## 5. Open/Closed Violations (SOLID - O)

### 5.1 Hard-coded Status Colors
**Location:** `app/Http/Controllers/AppointmentController.php::calendar()`
**Issue:** Status colors are hard-coded in controller.

**Refactoring:**
- Move to configuration or enum
- Create `AppointmentStatusColorMapper` service
- Allows easy extension without modifying controller

**Files to Modify:**
- `app/Http/Controllers/AppointmentController.php`
- Create: `app/Services/AppointmentStatusColorMapper.php`
- Or: Add to `App\Enums\AppointmentStatus` enum

---

## Implementation Priority

### Phase 1: High Impact, Low Risk (Start Here)
1. Extract organization data fetching (1.1)
2. Extract time parsing utility (1.2)
3. Extract appointment calendar formatting (1.4)
4. Simplify controller conditionals (3.1)

### Phase 2: Medium Impact, Medium Risk
1. Extract conflict checking service (1.3, 2.2)
2. Extract query builder service (2.1, 2.3)
3. Refactor frontend hooks (3.2)

### Phase 3: Lower Priority
1. Extract status color mapper (5.1)
2. DateTime abstraction (4.1)

---

## Testing Strategy

For each refactoring:
1. Write tests for new service/class first (TDD)
2. Ensure existing tests still pass
3. Add integration tests for new services
4. Update controller tests to mock new services

---

## Success Criteria

- [ ] No duplicated organization data fetching logic
- [ ] Single source of truth for time parsing
- [ ] Unified conflict checking logic
- [ ] Controllers are thin (delegate to services)
- [ ] Services have single responsibility
- [ ] Frontend components are composable
- [ ] All existing tests pass
- [ ] Code coverage maintained or improved

