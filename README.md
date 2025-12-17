# ClinicFlow

<div align="center">

<picture>
  <source media="(prefers-color-scheme: dark)" srcset="https://img.shields.io/badge/status-under%20development-orange?style=for-the-badge&logo=github">
  <source media="(prefers-color-scheme: light)" srcset="https://img.shields.io/badge/status-under%20development-orange?style=for-the-badge&logo=github">
  <img src="https://img.shields.io/badge/status-under%20development-orange?style=for-the-badge&logo=github" alt="Under Development"/>
</picture>

</div>

ClinicFlow is a demonstration outpatient clinic application designed to showcase integration with [SentinelStack](https://github.com/aphaiboon/sentinelstack), a healthcare-ready cloud platform for system monitoring, security controls, and immutable audit trails. ClinicFlow simulates core clinic operations using synthetic data exclusively and is not intended for production healthcare use.

## Overview

ClinicFlow demonstrates how a healthcare application integrates with SentinelStack to provide operational visibility, security enforcement, and compliance-ready audit trails. The application simulates patient registration, appointment scheduling, and exam room assignment workflows.

**Important:** ClinicFlow handles only synthetic, generated data. No Protected Health Information (PHI) is stored or processed. This is a demonstration application for portfolio and educational purposes.

## Features

- **Patient Registration**: Register synthetic patients with demographic information
- **Appointment Scheduling**: Schedule, reschedule, and cancel patient appointments
- **Exam Room Assignment**: Assign patients to exam rooms based on availability
- **Role-Based Access Control**: Enforce access controls based on user roles
- **Audit Logging**: Maintain immutable logs of all system actions
- **SentinelStack Integration**: Send operational metrics and events for monitoring and incident tracking

## Technical Stack

- **Backend**: Laravel 12 (PHP 8.3)
- **Frontend**: React 19 with Inertia.js v2
- **Styling**: Tailwind CSS v4
- **Database**: SQLite (development) / PostgreSQL (production-ready)
- **Authentication**: Laravel Fortify with two-factor authentication support
- **Testing**: Pest PHP v4
- **Code Quality**: Laravel Pint, ESLint, Prettier

## SentinelStack Integration

ClinicFlow is designed to integrate with SentinelStack for:

- Service health monitoring (latency, errors, uptime metrics)
- Incident detection and tracking
- Audit log forwarding for compliance
- Access control event monitoring
- Operational analytics

See [Integration Documentation](docs/integration-sentinelstack.md) for details on integration points and event schemas.

## Compliance Considerations

While ClinicFlow is a demonstration application, it is designed with healthcare compliance awareness:

- **HIPAA-aware architecture**: Technical safeguards including encryption, access controls, and audit logging
- **HL7 FHIR considerations**: API design aligned with interoperability standards
- **Audit-first design**: All actions recorded in immutable logs
- **Synthetic data only**: Explicit policy preventing real PHI handling

For detailed compliance and security documentation, see [Compliance & Security](docs/compliance-security.md).

## Quick Start

### Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js 20+ and npm
- SQLite (for development) or PostgreSQL

### Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/clinicflow.git
cd clinicflow
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Set up environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Run migrations:
```bash
php artisan migrate
```

5. Build frontend assets:
```bash
npm run build
```

6. Start the development server:
```bash
composer run dev
```

The application will be available at `http://localhost:8000`.

For detailed setup instructions, see [Setup Guide](docs/setup.md).

## Documentation

- [Architecture](docs/architecture.md) - System architecture and component overview
- [Domain Model](docs/domain-model.md) - Entity relationships and data model
- [Data Flow](docs/data-flow.md) - Data flows and operational sequences
- [Compliance & Security](docs/compliance-security.md) - HIPAA, HL7 FHIR, and security considerations
- [SentinelStack Integration](docs/integration-sentinelstack.md) - Integration points and event schemas
- [Setup Guide](docs/setup.md) - Detailed installation and configuration
- [Development Guide](docs/development-guide.md) - Coding standards and contribution guidelines

## Status

- 🚧 **Under active development**
- 🧪 **Demonstration environment only**
- ⚠️ **Not for production healthcare use**
- 📊 **Designed for portfolio and educational purposes**

## Contributing

Contributions are welcome. Please review the [Development Guide](docs/development-guide.md) for coding standards, testing requirements, and contribution guidelines.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Related Projects

- [SentinelStack](https://github.com/aphaiboon/sentinelstack) - Healthcare-ready monitoring and audit platform

