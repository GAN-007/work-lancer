# PostgreSQL-authoritative GALIKA runtime

PostgreSQL is the system of record for opportunities, applications, answers, events, work items, delivery state, projections, incidents, and audit history. No external spreadsheet/database service is required for qualification, application submission, reconciliation, or recovery.

Execution path: discovery -> PostgreSQL opportunity -> durable work item -> verification/qualification -> Open Web Agent browser worker -> explicit confirmation evidence -> PostgreSQL application/event ledger -> Gmail reconciliation -> asynchronous projection.

The browser/search/fetch execution plane is self-hosted. Worklancer calls the local Open Web Agent sidecar, which combines Browser Use + Playwright for interactive execution, Crawl4AI for dynamic page retrieval, SearXNG for metasearch, and a local model through Ollama or an OpenAI-compatible self-hosted endpoint.

Baserow is optional and never authoritative. When enabled, galika_outbox events are materialized first into galika_projection_records in PostgreSQL and then mirrored to Baserow asynchronously. If Baserow is unavailable, the canonical event and projection remain durable in PostgreSQL and the outbox retry/dead-letter flow preserves the failed side effect for recovery.

The migration 2026_10_01_070000_replace_airtable_with_open_source_projection.php converts pending legacy Airtable outbox destinations to Baserow and renames old Airtable credential rows to airtable_legacy* so historical configuration is retained for audit without remaining active.

No TinyFish or Airtable token, quota, automation, or API call is required by the live application path.
