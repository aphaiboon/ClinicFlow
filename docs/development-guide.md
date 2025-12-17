# Development Guide

## Overview

This guide outlines the development standards, practices, and workflows for contributing to ClinicFlow. Following these guidelines ensures code quality, maintainability, and consistency across the codebase.

## Code Style

### PHP Code Style

ClinicFlow uses [Laravel Pint](https://laravel.com/docs/pint) for PHP code formatting, which is based on PHP-CS-Fixer and follows PSR-12 coding standards.

**Format code before committing:**
```bash
vendor/bin/pint
```

**Format only changed files:**
```bash
vendor/bin/pint --dirty
```

### TypeScript/JavaScript Code Style

Frontend code follows ESLint and Prettier configurations:

**Format code:**
```bash
npm run format
```

**Lint code:**
```bash
npm run lint
```

**Type check:**
```bash
npm run types
```

### Code Style Principles

- Use descriptive variable and method names
- Follow existing code patterns and conventions
- Keep methods focused and single-purpose
- Write self-documenting code (prefer clarity over comments)
- Use type hints for all method parameters and return types
- Use constructor property promotion in PHP 8+

## Testing Requirements

### Testing Framework

ClinicFlow uses [Pest PHP](https://pestphp.com) for all tests, built on top of PHPUnit.

### Test Structure

Tests are organized in two directories:
- `tests/Feature/` - Feature tests (HTTP requests, database interactions)
- `tests/Unit/` - Unit tests (isolated classes, pure functions)

### Writing Tests

**Feature Test Example:**
```php
<?php

use App\Models\User;
use App\Models\Patient;

it('creates a patient', function () {
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
        ->post('/patients', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-01-01',
            // ... other fields
        ]);
    
    $response->assertRedirect();
    $this->assertDatabaseHas('patients', [
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);
});
```

**Unit Test Example:**
```php
<?php

use App\Services\PatientService;

it('generates unique medical record number', function () {
    $service = app(PatientService::class);
    
    $mrn1 = $service->generateMedicalRecordNumber();
    $mrn2 = $service->generateMedicalRecordNumber();
    
    expect($mrn1)->not->toBe($mrn2);
});
```

### Test Requirements

- All new features must include tests
- Bug fixes must include regression tests
- Test coverage should be maintained above 80% for critical paths
- Tests must be deterministic (no flaky tests)
- Use factories for test data creation
- Clean up test data (RefreshDatabase trait)

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/PatientTest.php

# Run tests with filter
php artisan test --filter=creates_patient

# Run tests with coverage
php artisan test --coverage
```

## Git Workflow

### Branch Naming

Use descriptive branch names:
- `feature/patient-registration`
- `bugfix/appointment-scheduling-conflict`
- `docs/update-setup-guide`
- `refactor/audit-service`

### Commit Messages

Follow conventional commit format:

```
type(scope): subject

body (optional)

footer (optional)
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `refactor`: Code refactoring
- `test`: Test additions or changes
- `chore`: Maintenance tasks

**Examples:**
```
feat(patients): add patient registration form

Implements patient registration with validation and audit logging.

Closes #123
```

```
fix(appointments): resolve room assignment conflict detection

Fixes issue where overlapping room assignments were not properly detected.
```

### Pull Request Process

1. Create feature branch from `main`
2. Make changes with tests
3. Ensure all tests pass
4. Format code (Pint, Prettier)
5. Update documentation if needed
6. Create pull request with clear description
7. Address code review feedback
8. Squash commits before merging (if requested)

### Pull Request Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Documentation update
- [ ] Refactoring
- [ ] Test updates

## Testing
- [ ] Tests added/updated
- [ ] All tests passing
- [ ] Manual testing completed

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No breaking changes (or documented)
```

## Code Review Process

### For Authors

- Keep pull requests focused and reasonably sized
- Provide clear description of changes
- Link related issues
- Request specific reviewers if needed
- Respond to feedback promptly

### For Reviewers

- Review within 2 business days
- Focus on code quality, correctness, and maintainability
- Ask questions rather than making demands
- Approve when satisfied, or request changes with specific feedback
- Be constructive and respectful

### Review Criteria

- Code correctness and functionality
- Test coverage and quality
- Code style compliance
- Performance considerations
- Security implications
- Documentation completeness
- Breaking changes identified

## Architecture Guidelines

### Layered Architecture

Follow the established layered architecture:

1. **Controllers** - HTTP request handling, validation, authorization
2. **Services** - Business logic, domain operations
3. **Models** - Data access, relationships, scopes
4. **Events/Listeners** - Asynchronous processing, integrations

### Controller Guidelines

- Keep controllers thin (delegate to services)
- Handle request validation
- Handle authorization checks
- Return appropriate responses (Inertia or JSON)
- Don't put business logic in controllers

**Example:**
```php
public function store(StorePatientRequest $request, PatientService $service)
{
    $patient = $service->createPatient($request->validated());
    
    return redirect()->route('patients.show', $patient)
        ->with('success', 'Patient created successfully.');
}
```

### Service Guidelines

- Encapsulate business logic
- Use dependency injection
- Handle transactions
- Throw domain exceptions
- Return domain entities or DTOs

**Example:**
```php
public function createPatient(array $data): Patient
{
    return DB::transaction(function () use ($data) {
        $patient = Patient::create([
            'medical_record_number' => $this->generateMedicalRecordNumber(),
            ...$data,
        ]);
        
        $this->auditLogger->logAction('create', Patient::class, $patient->id);
        
        event(new PatientCreated($patient));
        
        return $patient;
    });
}
```

### Model Guidelines

- Define relationships clearly
- Use scopes for reusable queries
- Use accessors/mutators when appropriate
- Define casts for data types
- Use factories for test data

**Example:**
```php
public function scopeActive(Builder $query): Builder
{
    return $query->where('is_active', true);
}

public function upcomingAppointments(): HasMany
{
    return $this->appointments()
        ->where('appointment_date', '>=', now())
        ->where('status', 'scheduled');
}
```

## Documentation Standards

### Code Documentation

- Use PHPDoc blocks for classes and public methods
- Document complex algorithms or business rules
- Avoid obvious comments (code should be self-documenting)
- Keep comments up-to-date with code changes

**Example:**
```php
/**
 * Schedules an appointment with conflict checking.
 *
 * @param  array  $data  Appointment data including patient_id, user_id, date, time
 * @return Appointment
 * @throws AppointmentConflictException  If scheduling conflict detected
 */
public function scheduleAppointment(array $data): Appointment
```

### Documentation Files

- Update relevant documentation when adding features
- Keep architecture diagrams current
- Document breaking changes
- Include examples for new features

## Security Guidelines

### Input Validation

- Always validate user input using Form Requests
- Sanitize output to prevent XSS
- Use parameterized queries (Eloquent handles this)
- Validate file uploads

### Authentication & Authorization

- Use Laravel Fortify for authentication
- Use policies for authorization
- Check permissions at controller and service level
- Log authorization failures

### Sensitive Data

- Never log sensitive information (passwords, tokens)
- Encrypt sensitive fields at rest
- Use HTTPS in production
- Secure API keys and credentials

### Audit Logging

- Log all data modifications
- Log sensitive data access
- Include user context in logs
- Ensure audit logs are immutable

## Performance Guidelines

### Database Queries

- Use eager loading to prevent N+1 queries
- Add indexes for frequently queried columns
- Use pagination for large result sets
- Avoid querying in loops

**Example:**
```php
// Bad: N+1 queries
$appointments = Appointment::all();
foreach ($appointments as $appointment) {
    echo $appointment->patient->name; // Query executed here
}

// Good: Eager loading
$appointments = Appointment::with('patient')->get();
foreach ($appointments as $appointment) {
    echo $appointment->patient->name; // No additional queries
}
```

### Caching

- Cache expensive computations
- Cache frequently accessed data
- Use cache tags for invalidation
- Set appropriate cache TTL

### Frontend Performance

- Lazy load components when appropriate
- Optimize images
- Minimize bundle size
- Use code splitting

## Error Handling

### Exception Handling

- Use specific exception types
- Provide meaningful error messages
- Log exceptions with context
- Don't expose sensitive information to users

**Example:**
```php
if ($conflict = $this->detectConflict($appointment)) {
    throw new AppointmentConflictException(
        "Appointment conflicts with existing appointment: {$conflict->id}"
    );
}
```

### User-Friendly Errors

- Return appropriate HTTP status codes
- Provide clear error messages
- Handle validation errors gracefully
- Show helpful error pages

## Dependency Management

### Adding Dependencies

- Prefer Laravel ecosystem packages
- Evaluate security and maintenance status
- Consider bundle size impact
- Document why dependency is needed

### Updating Dependencies

- Update regularly for security patches
- Test thoroughly after updates
- Review changelogs for breaking changes
- Update lock files (composer.lock, package-lock.json)

## Continuous Integration

All pull requests should:

- Pass all tests
- Pass code style checks
- Pass type checking
- Maintain test coverage
- Have no security vulnerabilities

## Getting Help

- Review existing code for patterns
- Check Laravel and framework documentation
- Ask questions in pull request comments
- Discuss architectural decisions before implementation

## Additional Resources

- [Laravel Best Practices](https://laravel.com/docs)
- [Pest PHP Documentation](https://pestphp.com)
- [Inertia.js Best Practices](https://inertiajs.com)
- [React Best Practices](https://react.dev/learn)

