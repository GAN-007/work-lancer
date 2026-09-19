# GALIKA Work-Lancer — Implementation Status

## Purpose
GALIKA Work-Lancer is intended to fuse the existing Laravel freelance marketplace with an autonomous, evidence-driven career opportunity engine. The system must continuously discover opportunities, canonicalize and deduplicate them, verify freshness and eligibility, prepare truthful application materials, route applications through supported channels, capture submission proof, reconcile delivery and inbound responses, schedule follow-up, and learn from outcomes.

## Non-negotiable lifecycle
DISCOVER -> CANONICALIZE -> DEDUPE -> REVERIFY -> QUALIFY -> MATERIAL-ANSWER GATE -> ROUTE HEALTH -> APPLY -> PROOF -> DELIVERY RECONCILIATION -> INBOUND MONITOR -> FOLLOW-UP -> LEARNING

## Required product surfaces
- Candidate onboarding and evidence-backed career profile
- Career goals, target roles, geography, remote/relocation preferences, compensation preferences
- CV/resume and reusable document library
- Opportunity inbox with source, freshness, match evidence and deduplication
- Application ledger with exact state and proof
- Decision Queue for material questions that cannot be answered truthfully from known evidence
- Platform/route health and circuit-breaker visibility
- Delivery and inbound-response timeline
- Follow-up planner
- Analytics: discovery volume, qualified rate, submit rate, confirmed-delivery rate, response rate, interview rate, offer rate, time-to-submit and failure taxonomy
- Admin/integration settings with secrets stored only in deployment secret stores

## Required execution capabilities
- Continuous scheduled discovery and queue workers
- Source adapters for supported job feeds and browser-capable discovery routes
- ATS/browser application adapters with idempotency and retry controls
- OpenAI-backed qualification and natural application writing constrained to verified candidate facts
- Airtable synchronization with the GAN Wealth OS operational tables
- Gmail delivery reconciliation, DSN/bounce handling, acknowledgements and recruiter-response ingestion
- Route registry that suppresses hard-bounced or invalid routes
- Platform health, exponential backoff, concurrency limits and circuit breakers
- Durable execution work items with leases, attempts, correlation IDs and idempotency keys
- Structured learning log and outcome feedback

## Truth and safety gates
GALIKA must never invent work authorization, visa/sponsorship status, certifications, years of experience, salary commitments, EEO/medical answers, criminal-history answers or other material candidate facts. Unknown material answers enter the Decision Queue. Duplicate applications must be prevented. A SENT email is not equivalent to delivered; hard DSN evidence supersedes SENT evidence.

## Deployment topology
GitHub Pages is only suitable for a static public/status/portfolio surface. Autonomous execution requires a backend runtime capable of Laravel scheduler/queue workers, PostgreSQL and Redis, plus deployment-time secrets/OAuth. GitHub Actions should run tests and deploy the backend. No API key, password, cookie, access token or OTP belongs in this repository.

## Production acceptance gates
The platform is not production-complete until all of the following pass:
1. migrations on a clean database;
2. unit and feature tests;
3. queue retry/idempotency tests;
4. duplicate-application tests;
5. material-answer Decision Queue tests;
6. source adapter contract tests;
7. application-route contract tests;
8. Gmail hard/soft bounce reconciliation tests;
9. Airtable sync tests;
10. browser/ATS synthetic tests for supported routes;
11. scheduler/worker soak test;
12. UI tests for candidate, opportunity, application, decision and analytics journeys;
13. deployment health checks with real secret injection;
14. a controlled real-world canary proving discovery -> qualification -> submission -> proof -> reconciliation without duplicate or fabricated answers.

## Current repository state
The GALIKA branch contains initial domain contracts and lifecycle states. This document deliberately does not claim the full system is implemented or tested until the acceptance gates above are evidenced by code and CI results.
