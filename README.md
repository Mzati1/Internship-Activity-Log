# CSIT-Internship-Activity-Log

A Laravel app for managing internship activity logs, synced with Excel workbooks.

## Features

- **Dashboard overview**: Track progress across 16 internship weeks.
- **Smart statuses**:
  - `Pending`: No logs yet.
  - `In Progress`: Partial logs added.
  - `Completed`: 5 daily logs (Mon–Fri) plus a weekly summary.
- **Sequential logging**: Weeks unlock only after the previous week is completed.
- **Student profile**: Name, Reg No, Company, and Supervisor details via the UI.
- **Excel sync**: Reads and writes the internship Excel sheets at the project root.

## File locations

Keep these two workbooks in the **project root** (they are gitignored):

- `CSIT-Internship Activity Log - 1.xlsx` — Cover page / student profile and weekly summaries.
- `Daily_Reports.xlsx` — Daily activity logs (headers: Week, Date, Activity).

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker Engine + Compose)
- Composer (host) for the initial dependency install / Sail bootstrap

## Setup with Laravel Sail

```bash
git clone <repository-url>
cd Internship-Activity-Log

# Install PHP dependencies (publishes Sail binaries)
composer install

# Environment
cp .env.example .env
# If compose.yaml is missing, publish Sail services once:
# php artisan sail:install --with=mysql,redis,mailpit
php artisan key:generate

# Place Excel templates in the project root (see above), then start the stack
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Open [http://localhost](http://localhost) for the dashboard.

Optional Sail alias (add to your shell profile):

```bash
alias sail='./vendor/bin/sail'
```

## Useful Sail commands

```bash
./vendor/bin/sail up -d          # start containers
./vendor/bin/sail down           # stop containers
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test
./vendor/bin/sail npm run build
```

Mailpit UI (when running): [http://localhost:8025](http://localhost:8025)

## Troubleshooting

- **"Resource temporarily unavailable"**: Close the Excel file in another app and retry.
- **Missing file**: Ensure both `.xlsx` workbooks live in the project root (same directory as `artisan`).
- **Port already in use**: Set `APP_PORT` / `FORWARD_DB_PORT` in `.env` before `sail up`.
