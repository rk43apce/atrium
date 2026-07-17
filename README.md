# Atrium Ledger

A production-minded, framework-free transaction processing application for PHP 8.1+. It streams CSV data into a normalized SQLite ledger, records independent validation failures, and provides an operational dashboard with search, filters, sorting, pagination, and transaction inspection.

## Quick start

Requirements: PHP 8.1+ with PDO SQLite.

```bash
cd web
php -d upload_max_filesize=50M -d post_max_size=52M -d max_execution_time=300 \
    -S 127.0.0.1:8080 -t public
```

Open `http://127.0.0.1:8080`, select **Import CSV**, and upload `../transactions_dirty.csv`.

For reproducible or large imports, use the CLI:

```bash
php bin/import.php ../transactions_dirty.csv
```

No dependency installation or database setup is required. On first request, the application creates `storage/database.sqlite`, applies the idempotent schema, and initializes structured JSON logs in `storage/logs/application.log`.

The explicit PHP options are important when using PHP's built-in CLI server: its default
upload limit is commonly 2 MB, while the supplied CSV is approximately 2.5 MB. For
Apache/FPM deployments, `public/.user.ini` supplies the same limits when user INI files
are enabled.

### Fixed web-root hosting

If the hosting provider forces the project `web/` directory to be the document root
and does not allow virtual-host changes, use the included root `index.php`:

```text
https://transactions.example.com/index.php
```

It delegates to `public/index.php` and adjusts the asset path automatically. The root
`.htaccess` disables directory listings and blocks HTTP access to application source,
configuration, SQL, storage, tests, views, dotfiles, logs, and local databases. The
root `.user.ini` applies the required upload and execution limits.

This compatibility mode requires Apache with `.htaccess` support. Using `public/` as
the real document root remains the preferred production deployment when server
configuration becomes available.

## MySQL 8

Create a dedicated database and least-privilege application user:

```sql
CREATE DATABASE atrium_ledger
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'atrium_app'@'localhost'
    IDENTIFIED BY 'replace-with-a-strong-password';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
    ON atrium_ledger.* TO 'atrium_app'@'localhost';
```

Ensure PHP has the `pdo_mysql` extension, then start the application from `web/`
by copying the environment template:

```bash
cp .env.example .env
```

Edit `.env` with the real MySQL password:

```dotenv
DB_DSN="mysql:host=127.0.0.1;port=3306;dbname=atrium_ledger;charset=utf8mb4"
DB_USERNAME=atrium_app
DB_PASSWORD=replace-with-a-strong-password
```

Then start the server normally:

```bash
php -d upload_max_filesize=50M -d post_max_size=52M -d max_execution_time=300 \
    -S 127.0.0.1:8080 -t public
```

The application detects MySQL and applies `database/schema.mysql.sql`
automatically. Existing SQLite data is not copied, so upload the CSV once after
switching databases. `.env` is ignored by Git. Environment variables supplied by a
deployment platform take precedence over values in `.env`.

## PostgreSQL

Create a database and application user:

```sql
CREATE USER atrium_app WITH PASSWORD 'replace-with-a-strong-password';
CREATE DATABASE atrium_ledger OWNER atrium_app;
```

Ensure PHP has the `pdo_pgsql` extension, then start the application with PostgreSQL
connection settings:

```bash
export DB_DSN='pgsql:host=127.0.0.1;port=5432;dbname=atrium_ledger'
export DB_USERNAME='atrium_app'
export DB_PASSWORD='replace-with-a-strong-password'

php -d upload_max_filesize=50M -d post_max_size=52M -d max_execution_time=300 \
    -S 127.0.0.1:8080 -t public
```

The application detects the PDO driver and applies
`database/schema.postgresql.sql` automatically. Existing SQLite data is not copied;
upload the CSV again after switching databases. In production, provide secrets through
the deployment environment or a secret manager rather than committing them.

## Design

The request path is intentionally thin:

```text
HTTP router → Controller → Service → Repository → PDO
                              ↓
                      Validator / DTO / Logger
```

- `Controllers` translate HTTP input into use cases.
- `CsvImportService` owns the streaming import transaction and orchestration.
- `Repositories` contain parameterized persistence and query logic.
- `TransactionValidator` normalizes and validates one row at a time.
- `TransactionData` moves validated, typed data across the boundary.
- `View` and escaping helpers keep presentation separate and XSS-safe.

## Import guarantees

- `SplFileObject` streams one CSV row at a time.
- The expected header schema is checked before processing.
- Each row is normalized and validated independently.
- Invalid rows and field-level errors remain queryable in `rejected_rows`.
- File SHA-256 checksums make repeated uploads a no-op.
- A unique constraint on `transaction_id` protects across different files.
- The entire accepted/rejected result is committed atomically.
- Imported, rejected, duplicate, skipped, duration, timestamp, name, and checksum metadata are recorded.
- Parameterized statements, strict upload limits, extension validation, output escaping, HttpOnly/SameSite sessions, and opaque card tokens reduce attack surface.

## Reports

The **Reports → Daily settlement** page groups transactions by occurrence date and
currency. It provides transaction, approved, declined, and reversed counts together
with gross, approved, and net settlement amounts. Net settlement treats approved
debits and adjustments as positive and approved credits and reversals as negative.
The report supports an inclusive date range.

## JSON API

The versioned API returns a consistent `{data, meta, error}` envelope. Collection
endpoints support bounded pagination; validation and missing-resource failures use
appropriate `4xx` status codes.

```text
GET  /index.php?route=api/v1/transactions
GET  /index.php?route=api/v1/transaction&id=123
GET  /index.php?route=api/v1/rejected-rows
GET  /index.php?route=api/v1/imports
GET  /index.php?route=api/v1/reports/daily
POST /index.php?route=api/v1/imports
```

Transaction query parameters are `page`, `per_page` (maximum 100), `search`,
`date_from`, `date_to`, `merchant_name`, `currency`, `status`, `transaction_type`,
`sort`, and `direction`. Daily reports accept `date_from` and `date_to`.

Example requests:

```bash
curl 'http://127.0.0.1:8080/index.php?route=api/v1/transactions&page=1&per_page=25&status=approved'

curl 'http://127.0.0.1:8080/index.php?route=api/v1/reports/daily&date_from=2026-07-01&date_to=2026-07-31'

curl -F 'csv=@../transactions_dirty.csv' \
    'http://127.0.0.1:8080/index.php?route=api/v1/imports'
```

Authentication is intentionally outside this assessment's single-user scope. A
production deployment should protect the API with short-lived credentials and
authorization policies before exposing it beyond a trusted network.

## Assumptions

- `transaction_id` is the business-level idempotency key across all imports.
- Exact file content is identified by its SHA-256 checksum, independent of filename.
- CSV timestamps are interpreted as UTC because the source contains no timezone.
- Amounts are positive decimal values and stored as integer minor units to avoid
  floating-point arithmetic in persistence and reporting.
- Card values in the supplied file are opaque tokens, not raw payment card numbers.
- Rejected rows belong to an import and remain available for operational review.
- A fully repeated file is a successful no-op rather than a validation failure.

## Validation and quality checks

```bash
find app public tests bin -name '*.php' -print0 | xargs -0 -n1 php -l
php -d zend.assertions=1 -d assert.exception=1 tests/TransactionValidatorTest.php
```

## What I would improve with additional time

The defaults optimize reviewer ergonomics, not infrastructure scale. In a multi-instance deployment, use PostgreSQL/MySQL, move imports to a supervised queue, persist uploads in private object storage, terminate TLS at the edge, add authentication/authorization, CSRF protection, rate limiting, centralized logs/metrics, backup policies, and schema migrations managed as versioned releases. Card values in this dataset are tokens; raw PAN data must never be stored without a PCI DSS compliant design.
# atrium
