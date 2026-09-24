# GALIKA production runbook

PostgreSQL is authoritative. Airtable is an asynchronous replica and must never gate discovery, qualification, submission, Gmail reconciliation, or follow-up.

## Required runtime
An always-on PHP 8.2+ host runs Supervisor using `deploy/supervisor/galika-worker.conf`. The scheduler, application worker, and outbox worker autorestart independently. GitHub Actions is watchdog/failover, not the primary worker.

Required deployment secrets include `APP_KEY`, `DATABASE_URL`, OpenAI credentials, TinyFish/browser execution credentials and endpoint, and per-user Gmail OAuth credentials in the encrypted connection vault. Do not commit them.

## Deployment
1. Deploy current `main`.
2. Inject production secrets.
3. Run `php artisan migrate --force`.
4. Run `php artisan galika:doctor`.
5. Install/reload Supervisor configuration.
6. Run `php artisan galika:watchdog`.
7. Verify worker heartbeat and `/galika-health`.
8. Run the controlled career canary only with a real, appropriate vacancy and the candidate's configured autonomous-apply policy.

## Proof standard
A real application is successful only when the external ATS/browser route actually submits and explicit confirmation evidence is persisted as `SUBMITTED_CONFIRMED` in PostgreSQL. An attempted form is not a submission. Gmail acknowledgement, DSN, assessment, recruiter reply, interview and offer are reconciled independently after submission. Airtable catches up from canonical PostgreSQL state when available.

Never manufacture a canary confirmation. If production credentials, browser capacity, an eligible live role, or candidate material answers are unavailable, leave work queued/blocked and report the exact gate.
