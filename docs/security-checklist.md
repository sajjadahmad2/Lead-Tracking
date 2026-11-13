# Security and Release Checklist

## Critical: environment files tracked

- Both `.env` and `.env-example` are committed and have the same blob content. Treat real credentials as exposed, rotate/revoke them, remove both files from the repository and Git history, and create a sanitized template.
- Expand `.gitignore` to exclude `.env`, `.env.*` (while allowing a sanitized example), `vendor/`, logs, caches, and generated artifacts.

## Route review

- `routes/web.php` defines `/fetch` outside the authenticated route group. It performs CRM API requests and updates location statistics; restrict it to a protected admin command or authorized route.
- `/cache` and `/test-cache` are public utility routes that clear application caches. Remove them from production or protect them.
- `/loginwith/{email?}` and `/backtoadmin` are outside the primary authenticated group and affect login/session identity. Enforce strict authentication, role checks, and audit logging.
- Review GET routes used for deletion or status changes and convert state-changing operations to appropriate HTTP methods.

## Data isolation and integration

- Verify company/location scoping for every dashboard query and mutation.
- Validate webhook and OAuth state, verify callback authenticity, and protect CRM credentials.
- Apply rate limits to authentication and externally reachable endpoints.
- Avoid logging personal contact information or tokens.

## General hardening

- Set production debug mode off and use HTTPS.
- Run `composer audit` and plan upgrades for unsupported dependencies.
- Add tests for role boundaries, company isolation, synchronization failures, and date/time calculations.
- Verify queue workers, scheduler configuration, backups, and monitoring.

## Verification status

Static source review only. No runtime or penetration testing was performed.
