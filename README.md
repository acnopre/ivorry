# HPDAI - Healthcare Plan Dental Administration Interface

A comprehensive dental healthcare management system built with Laravel 12 and Filament 3.

## Features

### Account Management
- Account creation, renewal, and amendments
- Policy tracking with effective/expiration dates
- HIP (Health Insurance Provider) integration
- MBL (Maximum Benefit Limit) management (Procedural/Fixed)
- Bulk import via Excel

### Member Management
- Member registration and tracking
- Card number generation
- Dependent management
- MBL balance tracking

### Clinic & Dentist Management
- Clinic accreditation tracking
- Dentist specializations
- Service provider management

### Claims & Approvals
- Procedure tracking
- Claim processing
- Approval workflows
- SOA (Statement of Accounts) generation

### System Features
- Role-based access control (Spatie Permission)
- Activity logging (Spatie Activity Log)
- Excel import/export (Maatwebsite Excel)
- PDF generation (DomPDF)
- QR code generation
- Queue-based processing

## Tech Stack

- **Framework**: Laravel 12
- **Admin Panel**: Filament 3.2
- **PHP**: 8.3+
- **Database**: SQLite (default) / MySQL / PostgreSQL
- **Queue**: Database driver
- **Cache**: Database driver

## Installation

### Prerequisites
- PHP 8.3 or higher
- Composer
- Node.js & NPM

### Setup

```bash
# Clone repository
git clone <repository-url>
cd hpdai

# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
touch database/database.sqlite
php artisan migrate --seed

# Build assets
npm run build

# Start development server
php artisan serve
```

### Default Admin Account
After seeding, check `database/seeders/SuperAdminSeeder.php` for credentials

## Development

### Run Development Environment
```bash
composer dev
```
This starts: Laravel server, Queue worker, Log viewer, Vite dev server

### Run Tests
```bash
# All tests
php artisan test

# Unit tests only
php artisan test --testsuite=Unit

# With coverage
php artisan test --coverage
```

See [TESTING_GUIDE.md](TESTING_GUIDE.md) for detailed testing documentation.

## Project Structure

```
app/
├── Filament/          # Admin panel resources
├── Models/            # Eloquent models
├── Services/          # Business logic
├── Jobs/              # Queue jobs
├── Imports/           # Excel imports
└── Exports/           # Excel exports

tests/
├── Unit/             # Unit tests
└── Feature/          # Integration tests
```

## Key Modules

- **Account Module**: Healthcare account management with renewals/amendments
- **Member Module**: Member registration and benefit tracking
- **Procedure Module**: Dental procedure recording and fee calculation
- **Import System**: Excel-based bulk imports with validation

## Configuration

### Queue Worker
```bash
php artisan queue:work
```

### Mail (Production)
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
```

## Security

- Role-based permissions via Filament Shield
- Activity logging for audit trails
- Soft deletes for data recovery
- Input validation and sanitization

## License

MIT License
