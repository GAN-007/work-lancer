# GALIKA Wealth OS architecture

## Goal

GALIKA transforms Work Lancer from a manually operated Laravel freelance marketplace into an evidence-driven opportunity operating system. It continuously ingests legitimate opportunity signals, canonicalizes and deduplicates them, verifies authoritative vacancy state and posting age, qualifies them against truthful candidate knowledge, prepares humanized application packages, executes authorized application routes, captures proof, reconciles email delivery, follows up, and ingests outcomes.

## Critical path

```text
source event/feed/poll
  -> discovery ingest
  -> canonicalize + dedupe
  -> application ledger
  -> VERIFY || QUALIFY
  -> readiness fan-in
  -> package + Application Answers
  -> APPLY
  -> confirmation evidence
  -> reconciliation
  -> follow-up
  -> inbound outcomes
  -> learning/scorecard
```

Hourly/periodic tasks are watchdogs only. They are not the primary latency mechanism. Event-capable sources should push. Sources without events use provider-safe adaptive polling with rate-limit backoff and jitter; the system must not hammer third-party services once per second.

## Freshness SLA

* <=12h authoritative posting age: primary universe.
* >12h and <=24h: fallback only after primary universe is exhausted.
* >24h: never apply.
* Unknown posting age: verify before application.

## Evidence rules

A label is never proof. ATS/browser submissions require confirmation page, application ID, authoritative application history, or equivalent evidence. Email SENT means `SENT_PENDING_RECONCILIATION`, not delivery. A hard bounce supersedes SENT, is appended to immutable delivery history, marks the route do-not-retry when appropriate, and triggers evidence-backed rerouting.

## Integrations

Credentials are environment/runtime secrets, never committed. Adapters should cover Airtable, Gmail, OpenAI, TinyFish/browser execution, LinkedIn signals/session, Jobicy, Joblet, Greenhouse, Lever, Ashby, Workday and other authorized sources. Source adapters emit normalized requisitions into one canonical ingestion service so provider failure cannot stop other providers.

## Existing Work Lancer preservation

The existing employer/freelancer/admin marketplace remains a supported surface. GALIKA is added as a bounded module under `App\\Galika` and should progressively replace manual opportunity discovery while preserving current user journeys until migrations/UI are explicitly switched.

## Success measures

Measure source coverage, authoritative verification rate, <=12h exhaustion, discovery-to-terminal latency, discovery-to-submission latency, bounce-to-reroute latency, duplicate prevention, confirmation rate, response rate, assessment/interview/offer rate, and controllable-loss reasons. Raw application count is secondary to evidence-backed conversion.
