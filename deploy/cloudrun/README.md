# RedCall on Cloud Run

## Deploying

```bash
./deploy/deploy-cloudrun.sh <prod|preprod>       # Cloud Build (in-region) → Artifact Registry → Cloud Run
./deploy/cloudrun/init-scheduler.sh <prod|preprod>  # create/update the 9 Cloud Scheduler jobs (replaces cron.yaml)
```

Requirements on the machine running the deploy:
- `gcloud` authenticated as an account with owner/editor on the target project.
- `deploy/<env>/dotenv` and `deploy/<env>/google-service-account.json` present (gitignored secrets — they are baked into the image at build time, exactly as they used to ship inside the App Engine source upload).

The Cloud Run deployment needs these vars in `deploy/<env>/dotenv` on top of the GAE-era ones (see `dotenv.dist`):

```
GOOGLE_TASK_PROCESS=http        # Cloud Tasks use HTTP targets instead of AppEngineHttpRequest
CRON_INVOKER_SA=cron-invoker@<project>.iam.gserviceaccount.com
TRUSTED_PROXIES=REMOTE_ADDR     # trust X-Forwarded-* from Google's front end
```

## Architecture recap

- Single image: Caddy (PID 1, serves `public/` statics, `php_fastcgi` to 127.0.0.1:9000) + PHP-FPM. Built by `deploy/cloudrun/Dockerfile` (build arg `ENV` picks the baked dotenv).
- Scaling mirrors the old `app.yaml`: concurrency 10, 0–10 instances, 1 CPU / 1 Gi.
- DB via the existing serverless VPC connector (Cloud SQL private IP).
- App Engine Cron → Cloud Scheduler jobs with OIDC tokens (audience = `WEBSITE_URL`), verified in-app by `App\Security\CronTokenVerifier` (requires `phpseclib/phpseclib` v3, already in composer.json).
- Cloud Tasks → HTTP targets; `/cloud-task` payloads remain HMAC-signed (`GoogleTaskBundle\Security\Signer`).
- The deploy script registers `WEBSITE_URL` as a **custom audience** on the service so Cloud Run's IAM layer accepts Scheduler's tokens even while the service requires authentication.

## Known org-policy blockers (Croix-Rouge organization)

Two things require an **organization administrator** and could not be completed from a project-owner account:

1. **Public access.** `constraints/iam.allowedPolicyMemberDomains` forbids granting `roles/run.invoker` to `allUsers`, so the service currently answers 401/403 to unauthenticated callers at Google's front door. RedCall NEEDS public access (Twilio webhooks, `/msg`/`/syn` public links, volunteer space). Ask the org admin for a **project-level exception** to `iam.allowedPolicyMemberDomains` (rule `allowAll: true`) for `redcall-dev` and `redcall-prod-260921`, then run:
   `gcloud run services add-iam-policy-binding redcall --region=europe-west1 --member=allUsers --role=roles/run.invoker`
   (App Engine never hit this because GAE apps are public by design — the policy only gates IAM bindings.)
   Until then: authenticated smoke tests work (`curl -H "Authorization: Bearer $(gcloud auth print-identity-token)" …`), Cloud Scheduler crons work (OIDC), but Cloud Tasks deliveries get 403 (they carry no OIDC token; app-level HMAC takes over once the service is public).

2. **Custom domain.** Cloud Run domain mappings require the domain to be verified for the deploying account in Google Search Console; `dev.redcall.minutis.croix-rouge.fr` is not verified for any current account. Run `gcloud domains verify dev.redcall.minutis.croix-rouge.fr` (opens Search Console; verification is a DNS TXT record on croix-rouge.fr — needs whoever controls that DNS), then:
   `gcloud beta run domain-mappings create --service=redcall --domain=dev.redcall.minutis.croix-rouge.fr --region=europe-west1`
   DNS itself already points at `ghs.googlehosted.com` A records (216.239.3x.21), which serve both GAE and Cloud Run mappings — **no DNS change needed** once verified. The old GAE mappings (`dev.redcall.minutis.croix-rouge.fr`, `www.…`, `dev.rcl.re`) were deleted during preprod migration.

## Production cutover runbook

Prod stays on App Engine until this is executed deliberately:

1. Add the three Cloud Run vars to `deploy/prod/dotenv` (see above; `CRON_INVOKER_SA=cron-invoker@redcall-prod-260921.iam.gserviceaccount.com`).
2. Confirm both org-policy blockers above are resolved for `redcall-prod-260921`.
3. `./deploy/deploy-cloudrun.sh prod` — then smoke-test on the printed `run.app` URL (homepage redirect, login page, a `/build/*` asset, `/cron/*` 403 for anonymous).
4. `./deploy/cloudrun/init-scheduler.sh prod`, then **pause** the jobs (`gcloud scheduler jobs pause …`) until cutover.
5. Verify external callers reference the domain, not `appspot.com`: Twilio webhook URLs, Minutis SSO, Google OAuth authorized redirect URIs.
6. Cutover: create the Cloud Run domain mapping for the prod domain (delete the GAE one first — same `ghs.googlehosted.com` trick, no DNS change if already CNAME/A to Google). Resume the Scheduler jobs. Empty `deploy/prod/cron.yaml` and redeploy it (or stop GAE serving) so GAE cron stops firing.
7. Rollback = recreate the GAE domain mapping (GAE app left intact and warm) and pause the Scheduler jobs.
8. After confidence: stop GAE versions, disable the app (Console → App Engine → Settings), and consider moving secrets from baked dotenv to Secret Manager.

## Legacy

`deploy/deploy.sh` (App Engine) is kept for prod rollback only.
