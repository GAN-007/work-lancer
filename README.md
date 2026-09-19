# Work-Lancer 2.0

Work-Lancer 2.0 is the fusion of the original Work-Lancer marketplace and GALIKA, an autonomous career opportunity, qualification, application, delivery-reconciliation, and follow-up engine.

It is designed to support two complementary career paths in one product:

1. **Marketplace path** — employers publish work directly inside Work-Lancer, freelancers discover and manage jobs, and administrators supervise the marketplace.
2. **GALIKA path** — authenticated users configure their career profile, policies, evidence, and autonomy level; GALIKA then continuously discovers external opportunities, qualifies them against verified evidence, blocks on material unknowns, applies through supported execution routes, requires proof of submission, reconciles delivery, schedules follow-up, and records outcomes.

The core design principle is simple:

> Automate aggressively where the system has evidence, stop where a material answer is unknown, and require proof before claiming success.

---

## Core lifecycle

```text
DISCOVER
  -> CANONICALIZE
  -> DEDUPE
  -> VERIFY / FRESHNESS CHECK
  -> POLICY + PROFILE GATE
  -> QUALIFY
  -> MATERIAL-ANSWER GATE
  -> PLATFORM / ROUTE HEALTH
  -> APPLY
  -> SUBMISSION PROOF
  -> DELIVERY RECONCILIATION
  -> FOLLOW-UP
  -> LEARNING
```

GALIKA application states include:

- `DISCOVERED`
- `VERIFIED`
- `SUBMITTING`
- `FAILED_RETRYING`
- `BLOCKED_REQUIRES_USER`
- `SUBMITTED_CONFIRMED`
- `DUPLICATE`
- `INELIGIBLE`
- `STALE`
- `CLOSED`

A submission is never considered confirmed only because a browser session ran or an email was sent. The platform requires explicit confirmation evidence.

---

## What is included

### Work-Lancer marketplace

The original marketplace functionality remains intact:

- user registration and authentication
- employer registration
- freelancer registration
- role-based dashboards
- job creation
- draft jobs
- published jobs
- job categories
- freelancer job browsing
- assigned, in-progress, and completed job views
- administrator job, category, employer, and freelancer management
- payment-oriented views retained from the legacy product

Legacy status inconsistencies found during the GALIKA integration were corrected so freelancer pending/assigned and employer/admin in-progress views use consistent states.

### GALIKA career operating system

Work-Lancer 2.0 adds authenticated GALIKA product surfaces for:

- **Career Command Center**
- **Career Profile & Autonomy**
- **Opportunity Inbox**
- **Application Ledger**
- **Decision Queue**
- **Career Funnel Analytics**

Each user has independent GALIKA state rather than relying on a single application-wide candidate profile.

Per-user data includes:

- career profile
- location and timezone
- minimum match score
- autonomous apply toggle
- global pause/kill switch
- review mode
- preferences
- policies
- verified career evidence
- CV/document assets
- integration records
- delivery route history
- application history
- Decision Queue state
- audit trail

---

## Automation and execution

### Discovery

The current discovery service supports Jobicy and is structured around a source-adapter pattern for additional sources.

Discovery records include:

- source
- external ID
- employer
- role
- location
- canonical URL
- publication time
- discovery time
- source evidence
- canonical fingerprint

### Cross-source canonicalization

`CanonicalizationService` creates stable fingerprints from:

- employer
- role title
- requisition/external ID
- location

This allows GALIKA to reason about the same opportunity across multiple feeds instead of treating every feed record as a new application.

### Qualification

`GalikaAIService` and `ApplicationEngine` evaluate opportunities against the user's verified evidence.

The qualification system is designed not to invent:

- work authorization
- visa or sponsorship status
- years of experience
- certifications
- salary commitments
- EEO answers
- medical information
- criminal-history answers
- technologies not supported by candidate evidence
- binding relocation commitments

If a material answer is not known, the application moves to the **Decision Queue** instead of fabricating a response.

### Policy engine

`PolicyEngine` evaluates per-user rules before an application can proceed.

Policies can be used to enforce user preferences such as:

- excluded industries
- blocked employers
- location restrictions
- role-family preferences
- compensation thresholds
- restricted job types

The user's global pause switch always takes precedence.

### Autonomous application

`ApplicationEngine` performs the full gate sequence before attempting submission:

1. verify the user has a GALIKA profile
2. require autonomous execution to be enabled
3. honor the global pause switch
4. reject stale opportunities
5. evaluate application policies
6. qualify against verified evidence
7. create Decision Queue items for unknown material answers
8. inspect platform circuit state
9. attempt application through the configured execution adapter
10. require explicit submission confirmation
11. schedule follow-up only after confirmed submission

The TinyFish adapter is wired as the browser-execution provider.

A route may return a failure class such as:

- `AUTH`
- `CAPTCHA`
- `UNKNOWN_ANSWER`
- `CV_UPLOAD`
- `SITE_ERROR`
- `RATE_LIMIT`
- `LOCATION`
- `CLOSED`
- `SESSION_EXPIRED`
- `OTHER`

Recoverable failures receive retry timing rather than being silently discarded.

---

## Delivery reconciliation

Email-route applications use a dedicated route and delivery model.

Work-Lancer 2.0 includes:

- Email Route Registry
- permanent hard-bounce suppression
- non-terminal temporary DSN handling
- immutable Delivery Events
- delivery-state reconciliation
- replacement-route metadata
- `do_not_retry` support

The delivery rules are strict:

### SENT is not delivery proof

A message present in Gmail SENT is only transport evidence.

### Temporary DSN

Temporary or delayed delivery remains:

```text
SENT_PENDING
```

The system does not resend while the provider's own retry is still active.

### Permanent DSN

A permanent failure:

- marks the route `HARD_BOUNCED`
- increments route hard-bounce history
- enables `do_not_retry`
- creates an immutable delivery event
- moves the application into recovery/retry handling

### No matching DSN

Only after a matching SENT record is found and no matching hard/soft DSN is present does the route become:

```text
DELIVERED_NO_BOUNCE
```

---

## Circuit breaking and retries

`PlatformCircuitBreaker` prevents GALIKA from repeatedly attacking an unhealthy external platform.

It records:

- platform health
- consecutive failures
- circuit state
- retry-after time
- last success
- last error

Repeated failures open the circuit and apply exponential delay.

`WorkQueueService` provides durable work-item behavior with:

- idempotency keys
- correlation IDs
- leases
- retry timing
- attempt counts
- status tracking

This is designed to support horizontally scaled workers without double-processing the same logical action.

---

## Follow-up

A confirmed application can be scheduled for follow-up through `FollowUpService`.

The application ledger stores:

- follow-up state
- follow-up due date
- submission timestamp
- discovery-to-submit latency
- delivery state
- inbound state
- confirmation evidence

Only confirmed submissions enter follow-up scheduling.

---

## GALIKA user journeys

### New user

```text
Register / log in
  -> open /galika
  -> configure Career Profile & Autonomy
  -> add verified evidence and documents
  -> configure integrations
  -> define policies/preferences
  -> enable autonomous apply
  -> monitor Command Center
```

### Autonomous opportunity flow

```text
source discovers job
  -> canonical fingerprint
  -> duplicate check
  -> freshness check
  -> user policy check
  -> evidence-grounded AI qualification
  -> Decision Queue if material information is missing
  -> execution route health check
  -> browser/application execution
  -> confirmation proof required
  -> confirmed ledger state
  -> delivery reconciliation
  -> follow-up scheduling
```

### Material question flow

```text
application encounters unknown material answer
  -> BLOCKED_REQUIRES_USER
  -> Decision Queue entry
  -> user answers from GALIKA UI
  -> application returns to VERIFIED
  -> execution can resume
```

### Delivery failure flow

```text
email submitted
  -> Gmail SENT evidence
  -> DSN scan
  -> temporary DSN => SENT_PENDING
  -> permanent DSN => HARD_BOUNCED + do_not_retry
  -> immutable delivery event
  -> recovery/replacement-route process
```

---

## Web routes

Authenticated GALIKA routes:

```text
/galika
/galika/profile
/galika/opportunities
/galika/applications
/galika/decisions
/galika/analytics
```

Legacy Work-Lancer marketplace routes remain available for:

- superadmin
- freelancer
- employer/user workflows

---

## Technology stack

### Backend

- PHP 8+
- Laravel 9
- Eloquent ORM
- Laravel Scheduler
- Laravel authentication
- Laratrust role management
- Livewire legacy marketplace components

### Frontend

- Blade
- Bootstrap 5
- responsive GALIKA dashboard layouts

### External integrations

- OpenAI
- Gmail API
- Airtable API
- TinyFish browser automation
- Jobicy

The code is intentionally credential-free. Secrets are injected at runtime.

---

## Requirements

Minimum local development requirements:

- PHP 8.0.2+
- Composer
- SQLite for local development, or PostgreSQL/MySQL in production
- Node.js and npm for frontend assets
- scheduler process for autonomous execution

For production-scale execution, also use:

- PostgreSQL
- Redis
- queue workers
- process supervisor
- HTTPS
- production secret management

---

## Installation

Clone the repository:

```bash
git clone https://github.com/GAN-007/work-lancer.git
cd work-lancer
```

Install PHP dependencies:

```bash
composer install
```

Create the local environment:

```bash
cp .env.example .env
php artisan key:generate
```

For the default SQLite development setup:

```bash
touch database/database.sqlite
php artisan migrate
```

Install frontend dependencies:

```bash
npm install
npm run build
```

Start Laravel:

```bash
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

---

## Integration configuration

Configure only the providers you intend to use.

```env
OPENAI_API_KEY=

GMAIL_ACCESS_TOKEN=
GMAIL_FROM=

AIRTABLE_TOKEN=
AIRTABLE_BASE_ID=

TINYFISH_API_KEY=
TINYFISH_ENDPOINT=

JOBICY_ENDPOINT=https://jobicy.com/api/v2/remote-jobs
```

GALIKA runtime behavior:

```env
GALIKA_MINIMUM_MATCH_SCORE=70
GALIKA_MAX_AGE_DAYS=30
GALIKA_OPENAI_MODEL=gpt-5.6
GALIKA_DISCOVERY_QUERIES="AI Engineer,Machine Learning Engineer,Data Scientist,Data Engineer,Full Stack Developer,Backend Engineer,Technical Lead,AI Finance"
```

Do **not** commit production tokens or API keys.

---

## Running GALIKA manually

Execute one complete GALIKA cycle:

```bash
php artisan galika:run --limit=25
```

The command performs:

- discovery
- per-user autonomous execution
- qualification
- policy checks
- Decision Queue gating
- application attempts
- delivery reconciliation

---

## Continuous autonomous runtime

Laravel's scheduler is configured to invoke GALIKA automatically.

Run the scheduler locally:

```bash
php artisan schedule:work
```

Production deployments should run the scheduler under a process supervisor.

A standard cron alternative is:

```cron
* * * * * cd /path/to/work-lancer && php artisan schedule:run >> /dev/null 2>&1
```

For horizontally scaled execution, use Redis-backed queue workers and a supervisor rather than relying only on synchronous execution.

---

## Production deployment

Recommended topology:

```text
Internet
   |
Load balancer / HTTPS
   |
Laravel application
   |
   +-- PostgreSQL
   +-- Redis
   +-- queue workers
   +-- Laravel scheduler
   +-- secret manager
   |
External services
   +-- OpenAI
   +-- Gmail
   +-- Airtable
   +-- TinyFish
   +-- job sources / ATS platforms
```

GitHub Pages can host a static public/status/portfolio surface, but it cannot run the Laravel scheduler, queue workers, authenticated browser sessions, or server-side secrets.

---

## Testing

Run the full test suite:

```bash
php artisan test
```

The repository's GitHub Actions workflow performs:

1. checkout
2. PHP setup
3. Composer installation
4. environment bootstrap
5. Laravel key generation
6. clean SQLite database creation
7. all migrations
8. PHPUnit tests

Current GALIKA test coverage includes:

- canonical opportunity/application uniqueness
- multi-user GALIKA schema
- Email Route Registry schema
- Delivery Events schema
- cross-source fingerprint stability
- policy blocking
- platform circuit breaker behavior
- authentication boundary
- GALIKA Command Center rendering
- autonomy profile updates
- Decision Queue user isolation
- hard-bounce route suppression

---

## Important production verification boundary

The repository and CI prove that:

- the Laravel application boots
- migrations succeed
- GALIKA domain models and services are loadable
- user journeys render
- tested lifecycle behavior passes

Live provider execution additionally depends on:

- valid provider credentials
- external API availability
- provider-specific permissions
- authenticated browser sessions
- external ATS behavior
- network access

Therefore a production deployment should always perform live health checks after credentials are configured.

---

## Security model

Work-Lancer 2.0 intentionally does not store secrets in source control.

Use:

- deployment environment variables
- platform secret stores
- short-lived OAuth tokens where possible
- least-privilege API scopes
- HTTPS
- database backups
- audit-log retention

Never commit:

- passwords
- OTPs
- browser cookies
- Gmail access tokens
- OpenAI keys
- Airtable tokens
- TinyFish tokens

GALIKA also provides user-level autonomy controls so an individual user can disable autonomous execution without shutting down the entire platform.

---

## Repository structure

Key GALIKA paths:

```text
app/
  Console/
    Commands/
      GalikaRun.php

  Galika/
    Contracts/
    Enums/
    Services/
      AirtableAdapter.php
      ApplicationEngine.php
      CanonicalizationService.php
      DeliveryReconciliationService.php
      DiscoveryService.php
      FollowUpService.php
      GalikaAIService.php
      GmailAdapter.php
      PlatformCircuitBreaker.php
      PolicyEngine.php
      TinyFishAdapter.php
      WorkQueueService.php

  Http/
    Controllers/
      GalikaController.php

  Models/
    Galika*.php

database/
  migrations/
    *galika*

resources/
  views/
    galika/

tests/
  Feature/
    GalikaLifecycleTest.php
```

---

## Product philosophy

Work-Lancer 2.0 is not intended to be another passive job board.

The platform is designed to reduce the manual repetition involved in career search while preserving truthful candidate representation.

The operating rules are:

- discover continuously
- prefer authoritative evidence
- deduplicate before execution
- apply only when qualified
- never invent material candidate facts
- require confirmation proof
- reconcile delivery after submission
- suppress known-dead routes
- retry recoverable platform failures with backoff
- surface only material decisions to the user
- maintain an auditable application history
- preserve the marketplace and external-opportunity engines as one ecosystem

---

## Status

The current `main` branch includes the Work-Lancer marketplace plus the GALIKA multi-user execution, integration, reconciliation, policy, retry, and responsive UX layers.

The codebase has passed the repository CI suite after the production-completion merge.

Production use still requires the operator to configure the external service credentials appropriate for the deployment.

---

## License

The project is built on Laravel and retains the repository's existing open-source framework licensing obligations.

Project-specific licensing and commercial usage terms should be defined by the repository owner before public commercial distribution.


## User-managed integrations

Work-Lancer 2.0 now supports per-user credential and connection state rather than relying only on server-global provider credentials.

- Gmail: OAuth 2.0 connection flow with user-scoped tokens
- OpenAI: encrypted per-user API key storage
- Airtable: encrypted per-user API token storage
- TinyFish: encrypted per-user API key storage
- Connection health testing from the GALIKA Connections screen

## CV ingestion and evidence confirmation

Users can upload PDF, DOCX, or TXT CVs. GALIKA extracts text, derives explicit evidence only, and presents extracted facts for confirmation. Only confirmed facts become verified evidence available to autonomous applications.

## Recruiter inbox automation

The inbound worker scans connected Gmail mailboxes, correlates recruiter/application messages to submitted applications, classifies acknowledgements, assessments, interviews, offers, rejections, information requests, and recruiter replies, and marks material responses for human intervention. Safe non-material replies can be generated and sent without inventing commitments.

## Wealth engine

The Wealth Engine provides non-job execution lanes for consulting, B2B, tenders, partnerships, products, IP, and other revenue opportunities. Wealth items are persisted, prioritized, analyzed, and progressed independently from job application throughput.

## Production runtime

The repository includes a Dockerfile, Supervisor configuration, Redis queue worker configuration, Laravel scheduler execution, and a Render deployment manifest. The production runtime launches both scheduler and queue workers and supports continuous GALIKA cycles.
