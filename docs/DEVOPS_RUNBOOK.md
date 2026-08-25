# DevOps Runbook

## Daily
- Review health checks.
- Review failed jobs.
- Review error rates.
- Review disk and database capacity.

## Weekly
- Verify backups.
- Review slow queries.
- Review dependency updates.
- Review security alerts.

## Monthly
- Run a restore drill.
- Review service-level objectives.
- Rotate operational secrets.
- Test rollback procedures.

## Deployment
1. Merge only after CI passes.
2. Tag the release.
3. Run environment validation.
4. Build immutable images.
5. Run migrations.
6. Start services.
7. Run smoke tests.
8. Monitor errors and latency.
