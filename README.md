# Work-Lancer + GALIKA Wealth OS

This repository is being transformed from a Laravel freelance marketplace into an autonomous job-application and wealth-execution platform.

GALIKA adds:

- fresh opportunity discovery
- canonical employer/requisition/ATS deduplication
- authoritative vacancy verification
- candidate-role qualification
- exact application-answer lineage
- application packaging
- governed ATS/browser/email routing
- delivery reconciliation and bounce chronology
- decision queue for material unknowns
- recruiter-response ingestion
- follow-up scheduling
- platform/source health monitoring
- conversion learning
- parallel B2B/consulting/product/IP/tender execution

## Runtime security

Secrets must never be committed to this repository. OpenAI, Airtable, Gmail/Google, TinyFish and other credentials are runtime configuration supplied through environment variables or a deployment secret manager.

## Development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Run asynchronous execution separately:

```bash
php artisan queue:work --queue=galika,default --tries=3 --timeout=180
php artisan schedule:work
```

## Production

A real autonomous deployment requires:

1. a persistent PHP web service;
2. a persistent queue worker;
3. scheduler execution;
4. a persistent relational database and Redis;
5. runtime secrets configured outside Git;
6. authorized browser/ATS execution;
7. Gmail/Google integration where permitted.

GitHub Pages can host a read-only static operations dashboard, but it cannot execute Laravel queues, schedulers, ATS flows or browser automation.

See `docs/GALIKA_REPOSITORY_AUDIT.md`.
