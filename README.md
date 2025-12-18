<div align="center">
  <img src="public/images/clinicflow-icon-logo.png" alt="ClinicFlow" width="200"/>
</div>

# ClinicFlow

<div align="center">

![Status](https://img.shields.io/badge/status-active-success.svg)
![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-8.3.16-blue.svg)
![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red.svg)
![React Version](https://img.shields.io/badge/React-19.x-blue.svg)

[![Tests](https://img.shields.io/badge/tests-passing-brightgreen.svg)](https://github.com/aphaiboon/clinicflow)
[![Code Style](https://img.shields.io/badge/code%20style-Laravel%20Pint-orange.svg)](https://laravel.com/docs/pint)

</div>

ClinicFlow is a demonstration outpatient clinic application designed to showcase integration with [SentinelStack](https://github.com/aphaiboon/sentinelstack), a healthcare-ready cloud platform for system monitoring, security controls, and immutable audit trails. It simulates core clinic operations using synthetic data exclusively and is not intended for production healthcare use.

## Features

- **Multi-Tenant Organization Support** - Organizations with role-based access control
- **Patient Management** - Registration, search, and profile management
- **Appointment Scheduling** - Schedule, update, and cancel appointments
- **Exam Room Management** - Track and assign exam rooms
- **Audit Logging** - Comprehensive activity tracking
- **SentinelStack Integration** - Domain event-driven observability and compliance

## Technical Stack

- **Backend**: Laravel 12 (PHP 8.3)
- **Frontend**: React 19 with Inertia.js v2
- **Styling**: Tailwind CSS v4
- **Database**: SQLite (development) / PostgreSQL (production-ready)
- **Authentication**: Laravel Fortify
- **Testing**: Pest PHP v4
- **Code Quality**: Laravel Pint, ESLint, Prettier

## Quick Start

1. Clone the repository:
   ```bash
   git clone https://github.com/aphaiboon/clinicflow.git && cd clinicflow
   ```

2. Install dependencies:
   ```bash
   composer install && npm install
   ```

3. Set up environment:
   ```bash
   cp .env.example .env && php artisan key:generate
   ```

4. Run migrations:
   ```bash
   php artisan migrate
   ```

5. Build frontend assets:
   ```bash
   npm run build
   ```

6. Start development server:
   ```bash
   composer run dev
   ```

The application will be available at `http://localhost:8000`.

## Documentation

- [Architecture](docs/architecture.md) - System architecture and design patterns
- [Domain Model](docs/domain-model.md) - Domain entities and relationships
- [Data Flow](docs/data-flow.md) - Data flow diagrams and processes
- [Compliance & Security](docs/compliance-security.md) - HIPAA considerations and security measures
- [SentinelStack Integration](docs/integration-sentinelstack.md) - Integration guide and event structure
- [Setup Guide](docs/setup.md) - Detailed setup instructions
- [Development Guide](docs/development-guide.md) - Development workflow and guidelines

## Contributing

Contributions are welcome. Please review the [Development Guide](docs/development-guide.md) for coding standards and contribution guidelines.

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

## Related Projects

- [SentinelStack](https://github.com/aphaiboon/sentinelstack) - Healthcare-ready observability platform
