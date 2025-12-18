# Patient Portal Documentation

## Overview

The ClinicFlow Patient Portal provides patients with secure access to view their appointments, manage their profile, and interact with the clinic system. The portal uses passwordless authentication via magic links and maintains strict separation from staff functionality through separate authentication guards.

## Features

### Dashboard
- View upcoming appointments (next 5)
- View recent appointments (last 5)
- Quick access to appointments and profile

### Appointment Management
- View all appointments with filtering by status
- View detailed appointment information
- Cancel appointments (with 24-hour advance notice requirement)
- See appointment cancellation eligibility

### Profile Management
- View personal information
- Update contact information (email, phone, address)
- View medical record number (read-only)
- Cannot modify immutable fields (name, DOB, MRN, gender)

## Patient Authentication

### Magic Link Authentication Flow

Patients authenticate using passwordless magic links:

1. **Request Magic Link**
   - Patient enters email address on login page
   - System validates email exists in database
   - Secure token generated and stored in cache (30-minute expiration)
   - Magic link email sent to patient

2. **Verify Magic Link**
   - Patient clicks link in email
   - System verifies token and retrieves patient ID
   - Token removed from cache (single-use)
   - Patient authenticated and session created
   - Patient redirected to dashboard

### Authentication Configuration

**Guard Configuration** (`config/auth.php`):
```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'patient' => [
        'driver' => 'session',
        'provider' => 'patients',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
    'patients' => [
        'driver' => 'eloquent',
        'model' => App\Models\Patient::class,
    ],
],
```

**Service**: `App\Services\PatientAuthService`
- `sendMagicLink(string $email)`: Generates and sends magic link
- `verifyMagicLink(string $token)`: Verifies token and returns patient
- `generateMagicLinkToken(Patient $patient)`: Generates secure token

**Notification**: `App\Notifications\PatientMagicLinkNotification`
- Sends email with magic link
- Link expires after 30 minutes
- Token is single-use

## Patient Portal Features

### Dashboard

**Route**: `GET /patient/dashboard`

**Controller**: `App\Http\Controllers\Patient\PatientDashboardController`

**Features**:
- Displays upcoming appointments (next 5, excluding cancelled)
- Displays recent appointments (last 5)
- Shows appointment details (date, time, clinician, status)

**Data Provided**:
- `upcomingAppointments`: Collection of upcoming appointments
- `recentAppointments`: Collection of past appointments

### Appointment Management

#### View Appointments

**Route**: `GET /patient/appointments`

**Controller**: `App\Http\Controllers\Patient\PatientAppointmentController@index`

**Features**:
- Lists all patient appointments
- Filter by status (scheduled, completed, cancelled)
- Shows appointment details (date, time, type, clinician, status)

**Service**: `App\Services\PatientAppointmentService`
- `getPatientAppointments(Patient $patient, array $filters)`: Retrieves filtered appointments

#### View Appointment Details

**Route**: `GET /patient/appointments/{appointment}`

**Controller**: `App\Http\Controllers\Patient\PatientAppointmentController@show`

**Features**:
- Shows full appointment details
- Displays cancellation eligibility
- Shows clinician information
- Shows exam room assignment (if assigned)

**Authorization**:
- Patient can only view their own appointments
- Returns 403 if patient attempts to view another patient's appointment

#### Cancel Appointment

**Route**: `POST /patient/appointments/{appointment}/cancel`

**Controller**: `App\Http\Controllers\Patient\PatientAppointmentController@cancel`

**Business Rules**:
- Patient can only cancel their own appointments
- Appointment must be at least 24 hours in the future
- Appointment must be in cancellable status (scheduled)
- Cancellation reason is optional but recommended

**Service**: `App\Services\PatientAppointmentService`
- `canCancelAppointment(Patient $patient, Appointment $appointment)`: Checks cancellation eligibility
- `cancelAppointment(Patient $patient, Appointment $appointment, ?string $reason)`: Cancels appointment

**Validation**:
- Reason is optional (max 500 characters)
- Returns error if cancellation not allowed (too close to appointment time or already cancelled/completed)

### Profile Management

#### View Profile

**Route**: `GET /patient/profile`

**Controller**: `App\Http\Controllers\Patient\PatientProfileController@show`

**Features**:
- Displays all patient information
- Shows editable and read-only fields
- Medical record number is read-only

#### Edit Profile

**Route**: `GET /patient/profile/edit`

**Controller**: `App\Http\Controllers\Patient\PatientProfileController@edit`

**Features**:
- Form to edit patient information
- Only editable fields are shown
- Immutable fields are hidden or disabled

#### Update Profile

**Route**: `PUT /patient/profile`

**Controller**: `App\Http\Controllers\Patient\PatientProfileController@update`

**Editable Fields**:
- `phone`: Contact phone number
- `email`: Email address (must be unique)
- `address_line_1`: Primary address
- `address_line_2`: Secondary address (optional)
- `city`: City
- `state`: State or province
- `postal_code`: Postal code
- `country`: Country code

**Immutable Fields** (cannot be changed):
- `medical_record_number`: Medical record number
- `date_of_birth`: Date of birth
- `first_name`: First name
- `last_name`: Last name
- `gender`: Gender
- `organization_id`: Organization assignment

**Service**: `App\Services\PatientProfileService`
- `getEditableFields()`: Returns list of editable fields
- `getImmutableFields()`: Returns list of immutable fields
- `updatePatientProfile(Patient $patient, array $data)`: Updates profile with validation

**Validation Rules**:
- Email: required, valid format, unique (except current patient)
- Phone: required, max 20 characters
- Address fields: required (except address_line_2)
- City: required, max 100 characters
- State: required, max 50 characters
- Postal code: required, max 20 characters
- Country: optional, max 2 characters

## Patient Permissions and Access Control

### Authorization Policies

**PatientPolicy** (`App\Policies\PatientPolicy`):
- `patientView(Patient $authPatient, Patient $patient)`: Patient can view own profile
- `patientUpdate(Patient $authPatient, Patient $patient)`: Patient can update own profile

**AppointmentPolicy** (`App\Policies\AppointmentPolicy`):
- `patientView(Patient $authPatient, Appointment $appointment)`: Patient can view own appointments
- `patientCancel(Patient $authPatient, Appointment $appointment)`: Patient can cancel own cancellable appointments

### Guard Separation

- **Staff Routes**: Use `auth` middleware (defaults to `web` guard)
- **Patient Routes**: Use `auth:patient` middleware
- **Complete Separation**: Patients cannot access staff routes, staff cannot access patient routes

### Route Protection

All patient routes are protected by `auth:patient` middleware:

```php
Route::middleware('auth:patient')->prefix('patient')->name('patient.')->group(function () {
    Route::get('dashboard', [PatientDashboardController::class, 'index'])->name('dashboard');
    Route::get('appointments', [PatientAppointmentController::class, 'index'])->name('appointments.index');
    Route::get('appointments/{appointment}', [PatientAppointmentController::class, 'show'])->name('appointments.show');
    Route::post('appointments/{appointment}/cancel', [PatientAppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::get('profile', [PatientProfileController::class, 'show'])->name('profile.show');
    Route::get('profile/edit', [PatientProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [PatientProfileController::class, 'update'])->name('profile.update');
});
```

## Patient-Specific Business Rules

### Appointment Cancellation Rules

1. **24-Hour Window**: Patients can only cancel appointments if at least 24 hours before the appointment time
2. **Status Requirement**: Appointment must be in `scheduled` status
3. **Ownership**: Patient can only cancel their own appointments
4. **Single Cancellation**: Once cancelled, appointment cannot be cancelled again

**Implementation**:
```php
// In PatientAppointmentService
private const CANCELLATION_HOURS_REQUIRED = 24;

public function canCancelAppointment(Patient $patient, Appointment $appointment): bool
{
    if ($appointment->patient_id !== $patient->id) {
        return false;
    }
    
    if (! $appointment->status->isCancellable()) {
        return false;
    }
    
    $appointmentDateTime = Carbon::parse($appointment->appointment_date->toDateString().' '.$appointment->appointment_time);
    $hoursUntilAppointment = now()->diffInHours($appointmentDateTime, false);
    
    return $hoursUntilAppointment >= self::CANCELLATION_HOURS_REQUIRED;
}
```

### Profile Field Restrictions

**Editable Fields**:
- Contact information (email, phone)
- Address information (all address fields)

**Immutable Fields**:
- Medical record number
- Date of birth
- First name
- Last name
- Gender
- Organization assignment

**Implementation**:
```php
// In PatientProfileService
public function getEditableFields(): array
{
    return [
        'phone',
        'email',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'postal_code',
        'country',
    ];
}

public function getImmutableFields(): array
{
    return [
        'medical_record_number',
        'date_of_birth',
        'first_name',
        'last_name',
        'gender',
        'organization_id',
    ];
}
```

## Security Considerations

### Authentication Security

1. **Token Security**: Magic link tokens are cryptographically secure and single-use
2. **Token Expiration**: Tokens expire after 30 minutes
3. **Email Verification**: Email addresses must exist in database
4. **Session Security**: Standard Laravel session security applies

### Authorization Security

1. **Guard Separation**: Complete separation between patient and staff authentication
2. **Policy Enforcement**: All patient actions checked via policies
3. **Resource Ownership**: Patients can only access their own resources
4. **Route Protection**: All patient routes protected by middleware

### Data Protection

1. **Audit Logging**: All patient actions logged in audit system
2. **Input Validation**: All patient inputs validated
3. **SQL Injection Prevention**: Uses Eloquent ORM
4. **XSS Protection**: React's built-in escaping

## UI/UX Guidelines

### Patient Portal Layout

- **Header**: Patient name, navigation links, logout button
- **Navigation**: Dashboard, Appointments, Profile
- **Content Area**: Main content for each page
- **Responsive**: Mobile-friendly design

### Patient Login Page

- **Simple Form**: Email input only
- **Clear Instructions**: "Enter your email to receive a magic link"
- **Link to Staff Login**: "Are you a staff member? Log in here"
- **Status Messages**: Clear success/error messages

### Dashboard

- **Upcoming Appointments**: Next 5 appointments
- **Recent Appointments**: Last 5 appointments
- **Quick Actions**: Links to appointments and profile

### Appointments List

- **Status Filters**: All, Scheduled, Completed, Cancelled
- **Table View**: Date, time, type, clinician, status
- **Actions**: View details button

### Appointment Details

- **Full Information**: All appointment details displayed
- **Cancellation Button**: Only shown if cancellation allowed
- **Cancellation Dialog**: Modal with reason input
- **Status Badge**: Visual status indicator

### Profile

- **View Mode**: Read-only display of all information
- **Edit Mode**: Form with editable fields only
- **Validation Errors**: Clear error messages
- **Success Messages**: Confirmation of updates

## Testing Patient Portal Features

### Feature Tests

**Location**: `tests/Feature/Patient/`

**Test Files**:
- `PatientDashboardTest.php`: Dashboard access and data display
- `PatientAppointmentTest.php`: Appointment viewing and cancellation
- `PatientProfileTest.php`: Profile viewing and updating
- `AuthenticationTest.php`: Magic link authentication

**Test Patterns**:
```php
test('patient can view their own appointments', function () {
    $patient = Patient::factory()->create();
    Appointment::factory()->for($patient)->count(3)->create();
    
    $response = $this->actingAs($patient, 'patient')
        ->get(route('patient.appointments.index'));
    
    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('appointments', 3)
        );
});
```

### E2E Browser Tests

**Location**: `tests/Browser/Patient/`

**Test File**: `PatientPortalTest.php`

**Test Coverage**:
- Complete login flow
- Dashboard navigation
- Appointment viewing
- Appointment cancellation
- Profile viewing and editing
- Logout flow
- JavaScript error checking

**Test Patterns**:
```php
it('can complete patient login flow with magic link', function () {
    Notification::fake();
    $patient = Patient::factory()->create(['email' => 'patient@example.com']);
    
    $page = visit('/patient/login');
    $page->type('email', $patient->email)
        ->press('Send Magic Link')
        ->assertSee('We have sent you a magic link');
    
    Notification::assertSentTo($patient, PatientMagicLinkNotification::class);
});
```

### Policy Tests

**Location**: `tests/Feature/Policies/`

**Test Files**:
- `PatientPolicyTest.php`: Patient profile permissions
- `AppointmentPolicyTest.php`: Patient appointment permissions

**Test Patterns**:
```php
it('allows patient to view their own profile', function () {
    $patient = Patient::factory()->create();
    $policy = new PatientPolicy();
    
    expect($policy->patientView($patient, $patient))->toBeTrue();
});
```

## Related Documentation

- [Domain Model](domain-model.md) - Patient entity and relationships
- [Architecture](architecture.md) - Patient authentication architecture
- [Data Flow](data-flow.md) - Patient authentication and portal flows
- [Development Guide](development-guide.md) - Patient development guidelines

## Implementation Files

### Controllers
- `App\Http\Controllers\Patient\PatientAuthController`
- `App\Http\Controllers\Patient\PatientDashboardController`
- `App\Http\Controllers\Patient\PatientAppointmentController`
- `App\Http\Controllers\Patient\PatientProfileController`

### Services
- `App\Services\PatientAuthService`
- `App\Services\PatientAppointmentService`
- `App\Services\PatientProfileService`

### Policies
- `App\Policies\PatientPolicy`
- `App\Policies\AppointmentPolicy`

### Models
- `App\Models\Patient`

### Notifications
- `App\Notifications\PatientMagicLinkNotification`

### Frontend Pages
- `resources/js/pages/Patient/Auth/Login.tsx`
- `resources/js/pages/Patient/Dashboard.tsx`
- `resources/js/pages/Patient/Appointments/Index.tsx`
- `resources/js/pages/Patient/Appointments/Show.tsx`
- `resources/js/pages/Patient/Profile/Show.tsx`
- `resources/js/pages/Patient/Profile/Edit.tsx`

### Layouts
- `resources/js/layouts/patient-layout.tsx`

