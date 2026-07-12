# RedCall: App Engine → Cloud Run Migration — Design

**Date:** 2026-07-12
**Status:** Approved
**Scope:** Make RedCall deployable to Cloud Run, deploy and verify preprod (`redcall-dev`). Production cutover is documented as a runbook but NOT executed as part of this work.

## Context

RedCall (Symfony 5, PHP 8.4) runs on App Engine Standard (`runtime: php84`). The GAE coupling points are:

| Coupling | Where | Cloud Run replacement |
|----------|-------|----------------------|
| `app.yaml` runtime, static handlers, scaling | `deploy/{prod,preprod}/app.yaml` | Docker image (Caddy serves statics), `gcloud run deploy` flags |
| App Engine Cron | `deploy/{prod,preprod}/cron.yaml`, 9 jobs → `/cron/*` | Cloud Scheduler jobs with OIDC |
| Cron auth via `X-Appengine-Cron` header | `symfony/src/Controller/CronController.php` | OIDC token verification (header only trusted when on GAE) |
| Cloud Tasks `AppEngineHttpRequest` targets | `symfony/bundles/google-task-bundle/Service/TaskSender.php` | `HttpRequest` targets (`Process::HTTP`), selected via env var |
| `X-Appengine-QueueName` header check | `symfony/src/Controller/TaskController.php:84` | Also accept `X-CloudTasks-QueueName` |
| VPC connector for Cloud SQL private IP | `app.yaml` `vpc_access_connector` | Same connector, passed to `gcloud run deploy --vpc-connector` |
| Deploy: `gcloud app deploy` + host-built assets | `deploy/deploy.sh` | `gcloud builds submit` + `gcloud run deploy` |

Already Cloud Run–compatible (no changes): sessions in MySQL (`PdoSessionHandler`), media in GCS, cache/logs in `sys_get_temp_dir()`, path-based Twilio/Sendgrid webhooks, task payload HMAC signing (`GoogleTaskBundle\Security\Signer`).

## 1. Container image

New multi-stage Dockerfile at `deploy/cloudrun/Dockerfile`, build context = repo root, build arg `ENV` (`prod`|`preprod`):

1. **Assets stage** — node image; `yarn install && yarn encore production` (replaces the host-side build in `deploy.sh`).
2. **PHP deps stage** — composer image or PHP stage; `composer install --no-dev --optimize-autoloader`.
3. **Runtime stage** — same recipe as `docker/php/Dockerfile` (php:8.4-fpm-alpine, extensions: opcache, pdo_mysql, intl, mbstring, bcmath, zip, sodium, dom, pcntl, gd, gmp), plus the `caddy` package.
   - Copies app code, built assets, vendor.
   - Bakes `deploy/$ENV/dotenv` → `symfony/.env` and `deploy/$ENV/google-service-account.json` → `symfony/config/keys/`. **Parity decision:** secrets are baked into the image exactly as they ship in the GAE source upload today. Migrating to Secret Manager is a follow-up, out of scope.
   - Runs MJML template generation (`generate:mjml`) and `cache:warmup` at build time so cold starts don't pay container compilation.
   - `deploy/cloudrun/Caddyfile`: listens on `:{$PORT:8080}`, `root symfony/public`, serves statics via `file_server` (covers app.yaml's `/build`, `/bundles`, image handlers with cache headers), `php_fastcgi 127.0.0.1:9000`, JSON logs to stdout.
   - `deploy/cloudrun/entrypoint.sh`: starts `php-fpm -D`, then `exec caddy run` (Caddy is PID 1).

## 2. Code changes (backward-compatible with GAE until cutover)

### 2.1 TaskSender HTTP targets
`TaskSender::fire()` currently defaults to `Process::APP_ENGINE()` in prod. Change: default comes from env var `GOOGLE_TASK_PROCESS` (`app_engine` | `http`), falling back to `app_engine` when unset. The Cloud Run dotenv sets `http`. HTTP tasks target the absolute `google_task_receiver` URL (built from `WEBSITE_URL`/router context); receiver security is the existing HMAC signature — unchanged.

### 2.2 CronController auth
Current check: client IP `127.0.0.1` OR `X-Appengine-Cron: true` OR logged-in admin. The header is forgeable outside GAE (GAE strips it from external traffic; Cloud Run does not).

New logic, in order:
1. `127.0.0.1` client IP → allow (local/dev).
2. `X-Appengine-Cron: true` **AND** `GAE_SERVICE` env var present → allow (still on GAE).
3. Valid Google OIDC bearer token → allow. Verified with the `google/auth` library (already in vendor): signature against Google certs, audience = the service's cron URL, issuer accepted (`accounts.google.com`/`https://accounts.google.com`), and `email` claim equals the configured invoker service account (`CRON_INVOKER_SA` env var).
4. Logged-in admin user → allow (existing behavior).
5. Otherwise 403.

### 2.3 TaskController queue-name header
`src/Controller/TaskController.php:84` reads `X-Appengine-QueueName`. Cloud Tasks HTTP targets send `X-CloudTasks-QueueName` instead. Accept either (App Engine header first for compatibility).

### 2.4 Tests
Regression tests accompany each change (project rule): cron auth matrix (GAE header with/without `GAE_SERVICE`, OIDC paths mockable via an injectable verifier, admin fallback), queue-name header fallback, task process env selection.

## 3. Infrastructure & deploy scripts

### 3.1 `deploy/deploy-cloudrun.sh <prod|preprod>`
- Same project mapping as `deploy.sh` (`redcall-prod-260921` / `redcall-dev`).
- `gcloud builds submit` with `deploy/cloudrun/Dockerfile` (build runs in Cloud Build — no local Docker needed, no host-side asset builds, no `.env` swap/restore dance) → Artifact Registry.
- `gcloud run deploy redcall`:
  - `--region europe-west1`
  - `--vpc-connector` = existing `gae-serverless-conn-<env>` (serverless VPC connectors serve both GAE and Cloud Run; DB connectivity unchanged)
  - `--concurrency 10 --min-instances 0 --max-instances 10` (mirrors `automatic_scaling`)
  - `--memory 1Gi --cpu 1` initially (mirrors F4_1G; tune after load observation). PHP `memory_limit` set to a container-appropriate value (not the dev image's 2G).
  - `--allow-unauthenticated` (the app performs its own auth; `/twilio`, `/msg`, `/syn`, `/space` etc. must stay anonymous).
- Keeps N previous revisions implicitly (Cloud Run revisions replace the GAE "versions to keep" cleanup).

### 3.2 `deploy/cloudrun/init-scheduler.sh <prod|preprod>`
One-time (idempotent) creation of 9 Cloud Scheduler jobs mirroring `cron.yaml`:

| Job | Path | Schedule |
|-----|------|----------|
| twilio-price | /cron/twilio-price | `0 * * * *` |
| report-communication | /cron/report-communication | `0 * * * *` |
| user-cron | /cron/user-cron | daily |
| clear-campaign | /cron/clear-campaign | daily |
| clear-media | /cron/clear-media | daily |
| clear-space | /cron/clear-space | daily |
| clear-expirable | /cron/clear-expirable | daily |
| sync-data | /cron/sync-data | daily |
| import-national | /cron/import-national | daily |

Each with `--oidc-service-account-email=<CRON_INVOKER_SA>` and `--oidc-token-audience=<cron URL>`. The script creates the invoker service account if missing.

### 3.3 Unchanged infrastructure
Cloud Tasks queues (targeting is per-task), GCS buckets, Cloud SQL, Twilio/Sendgrid config.

## 4. Rollout & verification

Preprod state: GAE and Cloud SQL in `redcall-dev` are currently **stopped**. Preprod URL is `https://dev.redcall.minutis.croix-rouge.fr/`. The user has authorized removing the App Engine deployment in `redcall-dev` entirely (delete versions/services + GAE domain mapping, disable the app — a GAE application cannot be fully deleted without deleting the project, which stays: it hosts Cloud SQL, buckets, and Task queues).

1. **Local:** `docker build` the image, run against the dev MySQL container with a dev dotenv, smoke-test: homepage 200, login page renders, `/build` statics served with cache headers, a cron endpoint 403s without credentials.
2. **Preprod deploy** (`redcall-dev`): start the Cloud SQL instance, run the deploy script end-to-end; verify on the `run.app` URL: pages load, DB reachable via connector, fire a real Cloud Task (HTTP target) and see it execute, `gcloud scheduler jobs run` one job and confirm 200 + effect.
3. **Preprod domain:** move `dev.redcall.minutis.croix-rouge.fr` to the Cloud Run service. Both GAE custom domains and Cloud Run domain mappings resolve via `ghs.googlehosted.com`, so if preprod DNS already CNAMEs there, deleting the GAE domain mapping and creating the Cloud Run one needs no croix-rouge.fr DNS change; otherwise flag the required DNS record to the user.
4. **GAE cleanup (preprod only):** delete GAE versions/services and disable the App Engine app in `redcall-dev`.
5. `make test` passes (full suite) with the code changes.

## 5. Production cutover (runbook only — NOT executed)

1. Deploy prod image to Cloud Run in `redcall-prod-260921`; smoke-test on `run.app` URL.
2. Create prod Scheduler jobs **paused**; set `GOOGLE_TASK_PROCESS=http` only in the Cloud Run dotenv.
3. Point the production domain (`redcall.minutis.croix-rouge.fr` / whatever `WEBSITE_URL` is in `deploy/prod/dotenv`) at Cloud Run (domain mapping or LB). External callers (Twilio webhooks, Minutis SSO, Google OAuth redirect URIs) follow the domain — verify OAuth authorized redirect URIs and Twilio webhook URLs reference the domain, not `appspot.com`.
4. Resume Scheduler jobs; pause GAE cron (`cron.yaml` emptied or GAE stopped).
5. Rollback = DNS/domain mapping back to GAE (kept warm until confidence).
6. Later cleanup: disable GAE app, move secrets to Secret Manager.

## Out of scope
- Secret Manager / runtime env-var injection (explicit follow-up).
- Direct VPC egress (connector reuse chosen for parity).
- Production cutover execution.
- CI/CD pipeline changes.
