# PJH-ERP

PJH-ERP is a proprietary Enterprise Resource Planning system built exclusively for **Internal Office Use**. It is designed to digitize, streamline, and centralize internal business operations ranging from human resources to corporate finance and operational workflows.

> **Note**: This application is strictly confidential. It is not intended for public deployment or external use.

## Core Features and Modules

- **Human Resources & Payroll**: End-to-end management of employee databases, annual leave quotas, internal loan/cash advances, and automated monthly payroll processing.
- **Finance & Accounting**: Robust financial management including budgeting, chart of accounts (GL), multi-currency vendor payments, petty cash tracking, internal transfers, journal entries, and automated tax calculations.
- **Internal Requests (Pengajuan)**: Digital processing of reimbursements, operational funds, and cash advances. Features a fully dynamic, multi-tier approval workflow system (Workflow Approval).
- **Revenue & Sales**: Module for managing customer databases and generating sales invoices with automatic tax computations.
- **Fixed Asset Management**: Tracking of company physical assets and calculation of periodic depreciation.
- **Task Management & IT Helpdesk**: Internal task delegation system (Kanban/Table views) and an IT ticketing system for tracking technical issues.
- **Utility Tools**: Built-in PDF manipulation (Merge/Split) using `iio/libmergepdf` and image compression tools for optimizing document uploads.
- **System Administration**: Granular Role-Based Access Control (RBAC) and comprehensive system activity logging.

## Technology Stack

<p align="left">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP" />
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel" />
  <img src="https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D" alt="Vue.js" />
  <img src="https://img.shields.io/badge/Inertia.js-9553E9?style=for-the-badge&logo=inertia&logoColor=white" alt="Inertia.js" />
  <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS" />
  <img src="https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL" />
</p>
### Backend
- **Framework**: Laravel 12.0 (PHP 8.2+)
- **Authentication**: Laravel Sanctum / Laravel Breeze
- **Role Management**: Spatie Laravel Permission
- **Activity Logging**: Spatie Laravel Activitylog
- **Document Generation**: 
  - PDF: `barryvdh/laravel-dompdf`, `iio/libmergepdf`
  - Excel: `maatwebsite/excel`
  - Word: `phpoffice/phpword`
- **Real-time Events**: Pusher PHP Server
- **Utilities**: `simplesoftwareio/simple-qrcode`

### Frontend
- **Framework**: Vue.js 3
- **Routing & State**: Inertia.js (`inertiajs/inertia-laravel`)
- **Styling**: Tailwind CSS
- **Icons**: Heroicons

## Prerequisites

Ensure the following dependencies are installed in your development environment before proceeding:
- PHP >= 8.2
- Composer
- Node.js and NPM
- MySQL or PostgreSQL database server

## Installation and Setup

Follow these steps to set up the project on your local machine.

1. Clone the repository and navigate into the project directory:
   ```bash
   git clone https://github.com/kadalmeinside/persija-erp.git
   cd persija-erp
   ```

2. Install PHP dependencies using Composer:
   ```bash
   composer install
   ```

3. Install JavaScript dependencies using NPM:
   ```bash
   npm install
   ```

4. Duplicate the environment configuration file:
   ```bash
   cp .env.example .env
   ```

5. Generate the application key:
   ```bash
   php artisan key:generate
   ```

6. Configure your database credentials in the `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password
   ```

7. Run database migrations and seeders to populate initial configurations, roles, and master data:
   ```bash
   php artisan migrate --seed
   ```

8. Build the frontend assets for production:
   ```bash
   npm run build
   ```

## Running the Application

To run the application locally, start the Laravel backend server:
```bash
php artisan serve
```

For frontend development with hot-module replacement (HMR), run Vite concurrently:
```bash
npm run dev
```

The application will be accessible at `http://localhost:8000`.

## Security Guidelines

- **Internal Network Only**: The production environment should ideally be placed behind a VPN or corporate firewall.
- **Search Engine Blocking**: Default `robots.txt` and meta tags are configured to `noindex, nofollow` to prevent accidental indexing of the administrative portal by search engines.
- **Debug Mode**: Ensure `APP_DEBUG=false` in the production `.env` file to prevent exposure of sensitive stack traces.

## License

This project is proprietary and confidential. Unauthorized copying of this project, via any medium, is strictly prohibited. All rights reserved by the organization.
