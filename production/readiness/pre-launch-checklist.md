# YellowPages.so Pre-Launch Checklist

## Application
- All backend tests pass.
- Frontend lint, type checking, and build pass.
- APP_ENV is production.
- APP_DEBUG is false.
- No pending migrations.
- Queue workers run under supervision.
- Scheduler runs every minute.
- Health endpoint reports ok.

## Security
- TLS is valid.
- HTTPS redirect is active.
- Secrets stay outside source control.
- Redis authentication is enabled.
- Admin MFA is enabled.
- Rate limiting covers authentication and public APIs.
- CORS allows approved origins only.
- Upload validation is active.
- Stack traces are hidden.

## Data and recovery
- Database backup succeeds.
- Storage backup succeeds.
- Restore drill succeeds.
- Offsite retention is configured.
- Recovery targets are documented.

## Operations
- Uptime alerts are active.
- Error alerts are active.
- Queue alerts are active.
- Payment alerts are active.
- Disk and database alerts are active.
- Incident contacts are documented.

## Launch
- DNS is ready.
- CDN rules are ready.
- Sitemap and robots.txt are correct.
- Privacy policy and terms are published.
- Support channels are ready.
- Payment providers use production mode.
- Test accounts and seed data are removed.
- Final smoke tests pass.
