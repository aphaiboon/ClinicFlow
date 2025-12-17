# ClinicFlow

<div align="center">
  <img src="public/images/clinicflow-text-logo.png" alt="ClinicFlow" width="300"/>
  
  <p>Clinic management system for healthcare staff</p>
</div>

ClinicFlow is a demonstration outpatient clinic application showcasing integration with [SentinelStack](https://github.com/aphaiboon/sentinelstack) for operational visibility, security enforcement, and compliance-ready audit trails. Built exclusively with synthetic data for portfolio and educational purposes.

## Features

- Patient registration and management
- Appointment scheduling and management
- Exam room assignment and tracking
- Role-based access control
- Immutable audit logging
- SentinelStack integration for monitoring and compliance

## Tech Stack

- **Backend:** Laravel 12 (PHP 8.3)
- **Frontend:** React 19 + Inertia.js v2
- **Styling:** Tailwind CSS v4
- **Database:** SQLite (dev) / PostgreSQL (prod)
- **Auth:** Laravel Fortify with 2FA
- **Testing:** Pest PHP v4

## Quick Start

```bash
# Clone repository
git clone https://github.com/yourusername/clinicflow.git
cd clinicflow

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build

# Start development server
composer run dev
```

Visit `http://localhost:8000` or `http://clinicflow.test` (if using Laravel Herd).

## Documentation

- [Architecture](docs/architecture.md) - System architecture and components
- [Domain Model](docs/domain-model.md) - Entity relationships
- [Data Flow](docs/data-flow.md) - Operational sequences
- [Compliance & Security](docs/compliance-security.md) - HIPAA, HL7 FHIR considerations
- [SentinelStack Integration](docs/integration-sentinelstack.md) - Integration points and event schemas
- [Setup Guide](docs/setup.md) - Detailed installation and configuration
- [Development Guide](docs/development-guide.md) - Coding standards and contribution guidelines

## Contributing

Contributions are welcome! Please review the [Development Guide](docs/development-guide.md) for coding standards, testing requirements, and contribution guidelines.

## License

MIT License. See [LICENSE](LICENSE) for details.

## Related Projects

- [SentinelStack](https://github.com/aphaiboon/sentinelstack) - Healthcare-ready monitoring and audit platform
