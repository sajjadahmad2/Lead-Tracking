# Lead Tracking Dashboard

A Laravel dashboard for monitoring GoHighLevel CRM locations and lead/contact activity across company accounts. The application includes user administration, CRM-user/location mapping, company-level views, and location statistics.

## Main capabilities visible in the source

- Authenticated dashboard and profile management
- User administration and CRM-user association
- Company and CRM location management
- Location-level lead/contact totals and remaining-lead calculations
- GoHighLevel API synchronization and OAuth callback handling
- Settings and location-related statistics
- Dashboard filtering and CRM data retrieval

## Technology

- PHP `^8.0.2`
- Laravel `^9.19`
- Laravel Sanctum
- Guzzle HTTP client
- Stripe PHP SDK dependency
- Yajra Laravel DataTables
- Composer and Laravel frontend tooling

## Local setup

1. Install PHP 8.0.2 or newer, Composer, Node.js/npm, and MySQL/MariaDB.
2. Clone the repository and install dependencies:

   ```bash
   composer install
   npm install
   ```

3. Create a new local `.env` from a clean Laravel template or prepare one manually. Do not copy the tracked `.env` or `.env-example`; both are currently committed and have identical content.
4. Configure the database, application URL, mail, cache, queue, and required CRM integration settings.
5. Generate a fresh application key:

   ```bash
   php artisan key:generate
   ```

6. Run migrations:

   ```bash
   php artisan migrate
   ```

7. Build frontend assets and start the server:

   ```bash
   npm run dev
   php artisan serve
   ```

The repository's migration and seed state should be validated against a clean database before treating this as a repeatable deployment guide.

## Security warning

The repository tracks both `.env` and `.env-example`; they have the same Git blob. Treat any real secrets in either file as exposed, rotate them, and remove both from Git history. The current `.gitignore` only excludes `/vendor`.

See [the security checklist](docs/security-checklist.md).
