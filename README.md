# Persija ERP

Persija ERP is a comprehensive Enterprise Resource Planning system designed to manage and streamline internal business processes. The application handles various operational requirements including human resources, finance, internal requests, and multi-level workflow approvals.

## Technology Stack

- Backend: Laravel
- Frontend: Vue.js 3, Inertia.js
- Styling: Tailwind CSS
- Database: MySQL / PostgreSQL

## Core Modules

- Human Resources and Payroll: Management of employee data, leave requests, loan tracking, and automated payroll processing.
- Finance and Accounting: Budgeting, chart of accounts, general ledger, financial reporting, and petty cash management.
- Internal Requests: Processing of reimbursements and cash advances integrated with a dynamic approval workflow.
- Task Management: Assignment and progress tracking of internal operational tasks.
- IT Support: Internal ticketing system for technical issues and asset management.
- Access Control: Role-based access control and system configuration for different user types.

## Prerequisites

Ensure the following software is installed before proceeding:
- PHP >= 8.2
- Composer
- Node.js and NPM
- MySQL or PostgreSQL

## Installation

Follow these steps to set up the project locally.

1. Clone the repository and navigate into the project directory:
   git clone https://github.com/kadalmeinside/persija-erp.git
   cd persija-erp

2. Install PHP dependencies:
   composer install

3. Install JavaScript dependencies:
   npm install

4. Configure the environment variables:
   cp .env.example .env

5. Generate the application key:
   php artisan key:generate

6. Update your database configuration in the `.env` file:
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password

7. Run database migrations and seeders:
   php artisan migrate --seed

8. Compile frontend assets for production:
   npm run build

## Local Development

To run the application in a local development environment, start the Laravel server:
php artisan serve

If you are modifying frontend assets, run the Vite development server concurrently:
npm run dev

The application will be available at http://localhost:8000.

## Security Guidelines

This application contains sensitive financial and HR data. Ensure that debug mode (`APP_DEBUG`) is set to false in production environments. Default search engine indexing has been disabled via `robots.txt` and meta tags to prevent accidental exposure of the administrative portal.

## License

This software is proprietary and confidential. Unauthorized copying, distribution, or modification is strictly prohibited.
