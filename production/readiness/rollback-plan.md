# Production Rollback Plan

1. Stop new deployments.
2. Enable maintenance mode.
3. Record the failing release.
4. Restore the previous release.
5. Roll back only reversible migrations.
6. Restore the database only when required.
7. Restart all services.
8. Run the health endpoint.
9. Run authentication, search, listing, payment, and support smoke tests.
10. Disable maintenance mode.
11. Document the incident.
