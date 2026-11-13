# Architecture Notes

## Application structure

The project is a Laravel MVC application. Its main domain areas include:

- Users and roles
- CRM users and company/location mappings
- Company locations and location settings
- Contacts and dashboard statistics
- Application settings and CRM authorization

## Main workflows

### Company and location reporting

The company-location controller calculates lead/contact totals for company and company-user views. It derives remaining leads from the configured lead and delivered/contact counters and returns tabular data for the dashboard.

### User and CRM mapping

The user controller associates local users with CRM users and uses role/company information to determine which locations and records are displayed.

### CRM synchronization

The application includes GoHighLevel API calls, an OAuth callback, and location/contact synchronization routines. API credentials and account-specific identifiers are environment/deployment configuration and must not be committed.

## Routing and background work

- `routes/web.php` contains the authenticated dashboard and administration routes.
- The same route file also contains several utility/synchronization routes outside the primary authenticated group. Review these before deployment.
- Laravel's queue and scheduler configuration should be checked against the actual deployment process; this documentation does not assume workers are currently running.

## Known gaps

- The current repository commits `.env` and `.env-example` with identical contents.
- `.gitignore` only excludes `/vendor`; it should also exclude local environment files, logs, caches, and generated files.
- No live CRM integration or clean-install test was performed.
