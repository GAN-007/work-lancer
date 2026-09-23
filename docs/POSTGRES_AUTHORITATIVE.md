# GALIKA PostgreSQL-authoritative execution

PostgreSQL is the system of record. Airtable is a recoverable asynchronous projection only.

Execution path: discovery -> PostgreSQL opportunity -> durable work item -> application model verification/qualification -> browser/ATS worker -> explicit confirmation evidence -> PostgreSQL application/event ledger -> Gmail reconciliation -> asynchronous Airtable projection.

No Airtable read, AI action, quota, or automation is required to qualify or submit. Failed external effects remain in PostgreSQL queues/outbox and retry with backoff. GitHub Actions is watchdog/failover only; the Render daemon is the immediate always-on executor.

A submission is confirmed only when the ATS/browser adapter returns explicit confirmation evidence. Attempts, SENT mail, labels and configuration never imply submission.

Airtable catches up from galika_outbox after recovery. PostgreSQL remains authoritative during and after replica outages.
