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

composer install
cp .env.example .env
php artisan key:generate

# First-time only (already present in this repo):
# php artisan sail:install --with=mysql,redis,mailpit

# Place Excel templates in the project root, then:
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

Open **[http://localhost:8080](http://localhost:8080)** (default `APP_PORT=8080` so it does not clash with Laravel Herd / local nginx on port 80).

Optional Sail alias:

```bash
alias sail='./vendor/bin/sail'
```

## Useful Sail commands

```bash
./vendor/bin/sail up -d
./vendor/bin/sail down
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test
./vendor/bin/sail npm run build
```

- App: [http://localhost:8080](http://localhost:8080)
- Mailpit: [http://localhost:8025](http://localhost:8025)

## Troubleshooting

- **"Resource temporarily unavailable"**: Close the Excel file in another app and retry.
- **Missing file**: Ensure both `.xlsx` workbooks live next to `artisan`.
- **Port already in use**: Change `APP_PORT`, `FORWARD_DB_PORT`, or `FORWARD_REDIS_PORT` in `.env`, then `sail up -d` again.
- **Profile shows Not Set**: Fill the COVER-PAGE fields in the activity log workbook (or use the profile form in the UI).
