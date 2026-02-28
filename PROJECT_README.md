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
After seeding, login with:
- Check `database/seeders/SuperAdminSeeder.php` for credentials

## Development

### Run Development Environment
```bash
composer dev
```
This starts:
- Laravel server (port 8000)
- Queue worker
- Log viewer (Pail)
- Vite dev server

### Run Tests
```bash
# All tests
php artisan test

# Unit tests only
php artisan test --testsuite=Unit

# With coverage
php artisan test --coverage
```

## Project Structure

```
app/
├── Filament/          # Admin panel resources
│   ├── Resources/     # CRUD resources
│   ├── Pages/         # Custom pages
│   └── Widgets/       # Dashboard widgets
├── Models/            # Eloquent models
├── Services/          # Business logic
├── Jobs/              # Queue jobs
├── Imports/           # Excel imports
└── Exports/           # Excel exports

database/
├── migrations/        # Database schema
└── seeders/          # Sample data

tests/
├── Unit/             # Unit tests
└── Feature/          # Integration tests
```

## Key Modules

### Account Module
- Create/manage healthcare accounts
- Handle renewals and amendments
- Import bulk accounts via Excel
- Track endorsement status (NEW/RENEWAL/AMENDMENT)

### Member Module
- Register members under accounts
- Track member benefits and MBL balance
- Manage dependents
- Generate member cards

### Procedure Module
- Record dental procedures
- Track service utilization
- Calculate applied fees
- MBL balance deduction

### Import System
- Excel-based bulk imports
- Validation and error tracking
- Queue-based processing
- Import log with detailed status

## Configuration

### Queue Configuration
Edit `.env`:
```env
QUEUE_CONNECTION=database
```

Run queue worker:
```bash
php artisan queue:work
```

### Mail Configuration
For production, configure SMTP:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
```

## Testing

Comprehensive unit tests covering:
- Job processing
- Service logic
- Model methods
- Import validations

See [TESTING_GUIDE.md](TESTING_GUIDE.md) for details.

## Security

- Role-based permissions via Filament Shield
- Activity logging for audit trails
- Soft deletes for data recovery
- Input validation and sanitization

## License

MIT License

## Support

For issues and questions, contact the development team.
