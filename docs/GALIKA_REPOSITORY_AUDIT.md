# GALIKA / work-lancer Repository Audit

## Current application intent

The existing repository is a Laravel 9 marketplace prototype with three user roles:

- `superadmin` manages categories, jobs, users and dashboard views.
- `user` acts as an employer and creates/publishes jobs.
- `freelancer` browses active jobs and sees assigned/completed work.

The current implementation is a human-driven freelance marketplace, not an autonomous job-application engine.

## Existing user journeys

### Employer
1. Register as employer.
2. Create a job in a 3-step Livewire form.
3. Upload attachments.
4. Publish the job.
5. Review jobs and basic status buckets.

### Freelancer
1. Register as freelancer.
2. Browse active jobs.
3. Open a job detail.
4. View assigned/completed work and placeholder payment screens.

### Superadmin
1. Login.
2. Manage categories.
3. Inspect/delete jobs.
4. Inspect employers/freelancers.
5. View aggregate dashboard counts.

## Material defects found in the existing code

1. `app/Console/Kernel.php` has no active schedule, so there is no autonomous recurring runtime.
2. `routes/api.php` exposes only the default authenticated-user endpoint; no application/discovery integration API exists.
3. The existing job model is marketplace-specific and has no canonical requisition identity, source provenance, freshness, application route, verification, qualification, delivery state or evidence model.
4. Payment pages exist as UI/controller placeholders but there is no payment model or transaction implementation.
5. `FreelancerController::pendingjobs()` incorrectly queries `status = complete`.
6. Job status names are inconsistent across controllers: `assigned`, `pending`, `inprogress`, `complete`, `active`, `draft`.
7. `AdminController::jobsinprogress()` queries `pending` while the employer controller queries `inprogress`.
8. `AdminController::allpayments()` returns jobs in `draft` state rather than payments.
9. `UserController::UpdateSingleJob()` validates `hourly_pay` and `project_pay`, but the job migration stores `payment_category` and `pay_rate`.
10. `Attachments::submitattachment()` uses `$order->order_id`, a field not defined on the Job model/migration.
11. Job IDs are produced from user ID + a substring of Unix time, which is collision-prone and not a canonical external requisition identity.
12. Several destructive state-changing actions use GET routes, including job/category deletion and job publication.
13. There is no authorization check in several ID-based controllers beyond route-level role checks; job ownership is not consistently enforced on update/delete/detail actions.
14. There is no application proof model, no delivery-event chronology, no decision queue, no exact submitted-answer ledger, no candidate knowledge store, no source health registry and no learning loop.
15. README remains the stock Laravel README and does not document the product, deployment, security model or operations.
16. Laravel 9 / PHP 8.0.2-era dependencies are legacy for a new production deployment and should be upgraded deliberately rather than assumed current.
17. No production queue worker, scheduler monitor, health check or dead-letter recovery exists.
18. No GitHub Actions, container production runtime, worker process definition or deployment manifest exists.
19. No tests cover the actual employer/freelancer workflows; only framework example tests are present.
20. No browser-automation abstraction exists for ATS application execution.

## GALIKA transformation target

The transformed repository keeps the existing marketplace functionality but adds GALIKA as a first-class autonomous application and wealth-operations subsystem.

The production graph is:

```
DISCOVER
  -> CANONICALIZE + DEDUPE
  -> VERIFY || QUALIFY
  -> FAN-IN
  -> PACKAGE
  -> APPLY
  -> EXTERNAL CONFIRMATION
  -> DELIVERY RECONCILIATION
  -> FOLLOW-UP
  -> INBOUND RESPONSE
  -> LEARNING
```

The watchdog is deliberately not the primary event runtime. Its job is to repair stalled work, detect route/source degradation, reconcile delivery evidence, requeue expired leases and surface only material blockers.

## Success measurements

Primary operational metrics:

- Freshness coverage: verified eligible discoveries processed within <=12h publication age.
- Discovery-to-terminal latency.
- Discovery-to-confirmed-submit latency.
- Eligible-opportunity terminal coverage.
- Confirmed submission rate.
- Application acknowledgement rate.
- Recruiter response rate.
- Assessment/interview/offer conversion.
- Hard-bounce rate and bounce-to-reroute latency.
- Source semantic quality by geography and role family.
- Route success rate by ATS/platform.
- Blocker rate requiring user decisions.
- B2B/consulting/product/IP expected revenue and realized revenue.

Configuration is never considered proof. A capability becomes PROVEN only after externally evidenced execution.
