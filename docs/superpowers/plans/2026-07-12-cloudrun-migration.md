# RedCall Cloud Run Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make RedCall deployable to Cloud Run and deploy + verify it on preprod (`redcall-dev`), replacing App Engine.

**Architecture:** Single Docker image (Caddy + PHP-FPM 8.4-alpine, multi-stage: yarn assets → composer deps → runtime) built by Cloud Build, deployed with `gcloud run deploy` reusing the existing serverless VPC connector. App Engine Cron becomes Cloud Scheduler jobs with OIDC; Cloud Tasks switch from `AppEngineHttpRequest` to HTTP targets via an env var. All code changes stay backward-compatible with GAE (needed for prod, which stays on GAE until a later cutover).

**Tech Stack:** Symfony 5 / PHP 8.4, Caddy 2, Docker, gcloud (Cloud Run, Cloud Build, Artifact Registry, Cloud Scheduler, Cloud Tasks), `google/auth` for OIDC verification.

**Spec:** `docs/superpowers/specs/2026-07-12-cloudrun-migration-design.md`

## Global Constraints

- Working directory for PHP/tests: `symfony/`. Tests run **inside the Docker dev container**: `docker compose exec php php vendor/bin/phpunit ...` from repo root, or `make test` for the full cycle (see CLAUDE.md — running phpunit on the host is error-prone).
- Repo root is `/Users/ninsuo/code/redcall/app`; the Makefile and `compose.yaml` live there, NOT in `symfony/`.
- Every bug-fix/behavior change ships with a regression test (project rule).
- The codebase reads env via `getenv()` (Dotenv is loaded with `usePutenv(true)` in `public/index.php` and `bin/console`). New env reads use `getenv()` for consistency.
- GCP: prod project `redcall-prod-260921` (VPC connector `gae-serverless-conn-prod`), preprod project `redcall-dev` (VPC connector `serverless-connector`), region `europe-west1`, account `alain.tiemblo@croix-rouge.fr`.
- Preprod URL: `https://dev.redcall.minutis.croix-rouge.fr`. Preprod GAE + Cloud SQL are currently STOPPED; deleting the preprod GAE deployment is authorized.
- `deploy/{prod,preprod}/dotenv` and `google-service-account.json` are **gitignored secrets** — never commit them; commit only the `.dist` templates.
- Commits end with `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>`.

---

### Task 1: TaskController accepts the Cloud Tasks queue header

Cloud Tasks HTTP targets send `X-CloudTasks-QueueName`; App Engine targets send `X-Appengine-QueueName`. `TaskController::checkOrigin()` only accepts the latter.

**Files:**
- Modify: `symfony/src/Controller/TaskController.php:82-87`
- Test: `symfony/tests/Controller/InfrastructureRoutesTest.php`

**Interfaces:**
- Produces: `/task/webhook` accepts requests bearing either header (no signature change; body validation unchanged).

- [ ] **Step 1: Write the failing test**

Add to `symfony/tests/Controller/InfrastructureRoutesTest.php` (it already covers `/task/*`; keep its section-comment style):

```php
public function testTaskWebhookAcceptsCloudTasksQueueHeader(): void
{
    $client = static::createClient();

    // A Cloud Tasks HTTP-target request carries X-CloudTasks-QueueName
    // instead of X-Appengine-QueueName. With the header present the
    // origin check must pass; the empty body then yields 404 (no
    // WebhookRequest payload), NOT 403.
    $client->request('POST', '/task/webhook', [], [], [
        'HTTP_X_CLOUDTASKS_QUEUENAME' => 'webhook-sms-responses',
    ], json_encode([]));

    $this->assertResponseStatusCodeSame(404);
}

public function testTaskWebhookRejectsRequestWithoutAnyQueueHeader(): void
{
    $client = static::createClient();

    $client->request('POST', '/task/webhook', [], [], [], json_encode([]));

    $this->assertResponseStatusCodeSame(403);
}
```

- [ ] **Step 2: Run tests to verify the new one fails**

Run (repo root): `docker compose exec php php vendor/bin/phpunit tests/Controller/InfrastructureRoutesTest.php --filter=testTaskWebhook`
Expected: `testTaskWebhookAcceptsCloudTasksQueueHeader` FAILS (403 instead of 404); the reject test passes.

- [ ] **Step 3: Implement**

In `symfony/src/Controller/TaskController.php`, replace `checkOrigin()`:

```php
private function checkOrigin(Request $request)
{
    // App Engine targets send X-Appengine-QueueName, Cloud Run (HTTP)
    // targets send X-CloudTasks-QueueName.
    if (!$request->headers->get('X-Appengine-QueueName')
        && !$request->headers->get('X-CloudTasks-QueueName')) {
        throw $this->createAccessDeniedException();
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/Controller/InfrastructureRoutesTest.php`
Expected: ALL PASS (the pre-existing tests in this file must stay green).

- [ ] **Step 5: Commit**

```bash
git add symfony/src/Controller/TaskController.php symfony/tests/Controller/InfrastructureRoutesTest.php
git commit -m "[CloudRun] accept X-CloudTasks-QueueName on task webhook"
```

---

### Task 2: TaskSender chooses its target process from `GOOGLE_TASK_PROCESS`

`TaskSender::fire()` hardcodes `Process::APP_ENGINE()` as the prod default. Cloud Run needs `Process::HTTP()` (the HTTP path already exists and the receiver is HMAC-protected by `Signer`).

**Files:**
- Modify: `symfony/bundles/google-task-bundle/Service/TaskSender.php:58-60`
- Create: `symfony/tests/GoogleTaskBundle/TaskSenderTest.php`
- Modify: `deploy/prod/dotenv.dist`, `deploy/preprod/dotenv.dist` (document the new var)

**Interfaces:**
- Produces: `TaskSender::getDefaultProcess() : Process` — public, returns `Process::HTTP()` when `getenv('GOOGLE_TASK_PROCESS') === 'http'`, else `Process::APP_ENGINE()`. `fire()` uses it when `$process` is null in prod.

- [ ] **Step 1: Write the failing test**

Create `symfony/tests/GoogleTaskBundle/TaskSenderTest.php`:

```php
<?php

namespace App\Tests\GoogleTaskBundle;

use Bundles\GoogleTaskBundle\Bag\TaskBag;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouterInterface;

class TaskSenderTest extends TestCase
{
    protected function tearDown() : void
    {
        putenv('GOOGLE_TASK_PROCESS');

        parent::tearDown();
    }

    public function testDefaultProcessIsAppEngineWhenEnvIsNotSet()
    {
        putenv('GOOGLE_TASK_PROCESS');

        $this->assertTrue($this->createSender()->getDefaultProcess()->isAppEngine());
    }

    public function testDefaultProcessIsHttpWhenEnvSaysSo()
    {
        putenv('GOOGLE_TASK_PROCESS=http');

        $this->assertTrue($this->createSender()->getDefaultProcess()->isHttp());
    }

    public function testDefaultProcessIsAppEngineForUnknownValues()
    {
        putenv('GOOGLE_TASK_PROCESS=whatever');

        $this->assertTrue($this->createSender()->getDefaultProcess()->isAppEngine());
    }

    private function createSender() : TaskSender
    {
        return new TaskSender(
            $this->createMock(RouterInterface::class),
            $this->createMock(KernelInterface::class),
            $this->createMock(TaskBag::class)
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/GoogleTaskBundle/TaskSenderTest.php`
Expected: FAIL — `Call to undefined method ...TaskSender::getDefaultProcess()`.

- [ ] **Step 3: Implement**

In `symfony/bundles/google-task-bundle/Service/TaskSender.php`:

Replace (inside `fire()`):

```php
        if (null === $process) {
            $process = Process::APP_ENGINE();
        }
```

with:

```php
        if (null === $process) {
            $process = $this->getDefaultProcess();
        }
```

and add the method after `fire()`:

```php
    public function getDefaultProcess() : Process
    {
        // On Cloud Run, tasks must use HTTP targets; App Engine targets
        // only work when the app runs on GAE.
        if ('http' === getenv('GOOGLE_TASK_PROCESS')) {
            return Process::HTTP();
        }

        return Process::APP_ENGINE();
    }
```

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/GoogleTaskBundle/TaskSenderTest.php`
Expected: 3 tests PASS.

- [ ] **Step 5: Document the env var in the dist templates**

Append to BOTH `deploy/prod/dotenv.dist` and `deploy/preprod/dotenv.dist`:

```
# Cloud Tasks target type: 'app_engine' (default, GAE deployments) or 'http' (Cloud Run deployments)
GOOGLE_TASK_PROCESS=http
```

(Do NOT touch the gitignored `deploy/*/dotenv` files yet — that happens in Task 9 for preprod only.)

- [ ] **Step 6: Commit**

```bash
git add symfony/bundles/google-task-bundle/Service/TaskSender.php symfony/tests/GoogleTaskBundle/TaskSenderTest.php deploy/prod/dotenv.dist deploy/preprod/dotenv.dist
git commit -m "[CloudRun] make Cloud Tasks target selectable via GOOGLE_TASK_PROCESS"
```

---

### Task 3: CronTokenVerifier (OIDC verification for Cloud Scheduler)

New small service that verifies the OIDC ID token Cloud Scheduler attaches. Uses `Google\Auth\AccessToken` (already in vendor via `google/auth ^1.37`); constructor injection of `AccessToken` keeps it unit-testable without network.

**Files:**
- Create: `symfony/src/Security/CronTokenVerifier.php`
- Test: `symfony/tests/Security/CronTokenVerifierTest.php`

**Interfaces:**
- Produces: `App\Security\CronTokenVerifier::verify(string $idToken) : bool`. Returns true only when: token signature/expiry valid (checked by `AccessToken::verify`), audience equals `getenv('WEBSITE_URL')`, issuer is `https://accounts.google.com`, `email` claim equals `getenv('CRON_INVOKER_SA')`, and `email_verified` is true.
- Consumed by Task 4 (`CronController`).

- [ ] **Step 1: Write the failing test**

Create `symfony/tests/Security/CronTokenVerifierTest.php`:

```php
<?php

namespace App\Tests\Security;

use App\Security\CronTokenVerifier;
use Google\Auth\AccessToken;
use PHPUnit\Framework\TestCase;

class CronTokenVerifierTest extends TestCase
{
    private $originalWebsiteUrl;
    private $originalInvokerSa;

    protected function setUp() : void
    {
        parent::setUp();

        $this->originalWebsiteUrl = getenv('WEBSITE_URL');
        $this->originalInvokerSa  = getenv('CRON_INVOKER_SA');

        putenv('WEBSITE_URL=https://dev.redcall.example');
        putenv('CRON_INVOKER_SA=cron-invoker@redcall-dev.iam.gserviceaccount.com');
    }

    protected function tearDown() : void
    {
        putenv(false === $this->originalWebsiteUrl ? 'WEBSITE_URL' : 'WEBSITE_URL='.$this->originalWebsiteUrl);
        putenv(false === $this->originalInvokerSa ? 'CRON_INVOKER_SA' : 'CRON_INVOKER_SA='.$this->originalInvokerSa);

        parent::tearDown();
    }

    public function testAcceptsTokenFromTheInvokerServiceAccount()
    {
        $verifier = new CronTokenVerifier($this->createAccessToken([
            'email'          => 'cron-invoker@redcall-dev.iam.gserviceaccount.com',
            'email_verified' => true,
        ]));

        $this->assertTrue($verifier->verify('some-token'));
    }

    public function testRejectsWhenGoogleRefusesTheToken()
    {
        $verifier = new CronTokenVerifier($this->createAccessToken(false));

        $this->assertFalse($verifier->verify('some-token'));
    }

    public function testRejectsTokenFromAnotherServiceAccount()
    {
        $verifier = new CronTokenVerifier($this->createAccessToken([
            'email'          => 'evil@attacker.iam.gserviceaccount.com',
            'email_verified' => true,
        ]));

        $this->assertFalse($verifier->verify('some-token'));
    }

    public function testRejectsUnverifiedEmailClaim()
    {
        $verifier = new CronTokenVerifier($this->createAccessToken([
            'email'          => 'cron-invoker@redcall-dev.iam.gserviceaccount.com',
            'email_verified' => false,
        ]));

        $this->assertFalse($verifier->verify('some-token'));
    }

    public function testRejectsWhenInvokerIsNotConfigured()
    {
        putenv('CRON_INVOKER_SA');

        $verifier = new CronTokenVerifier($this->createAccessToken([
            'email'          => 'cron-invoker@redcall-dev.iam.gserviceaccount.com',
            'email_verified' => true,
        ]));

        $this->assertFalse($verifier->verify('some-token'));
    }

    /**
     * @param array|false $payload what AccessToken::verify() should return
     */
    private function createAccessToken($payload) : AccessToken
    {
        $accessToken = $this->createMock(AccessToken::class);

        $accessToken->method('verify')->willReturnCallback(
            function (string $token, array $options) use ($payload) {
                // The verifier must pin audience and issuer.
                $this->assertSame('https://dev.redcall.example', $options['audience'] ?? null);
                $this->assertSame('https://accounts.google.com', $options['issuer'] ?? null);

                return $payload;
            }
        );

        return $accessToken;
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `docker compose exec php php vendor/bin/phpunit tests/Security/CronTokenVerifierTest.php`
Expected: FAIL — `Class "App\Security\CronTokenVerifier" not found`.

- [ ] **Step 3: Implement**

Create `symfony/src/Security/CronTokenVerifier.php`:

```php
<?php

namespace App\Security;

use Google\Auth\AccessToken;

/**
 * Verifies the OIDC ID token that Cloud Scheduler attaches to cron
 * requests when the app runs on Cloud Run (where the X-Appengine-Cron
 * header cannot be trusted).
 */
class CronTokenVerifier
{
    private $accessToken;

    public function __construct(?AccessToken $accessToken = null)
    {
        $this->accessToken = $accessToken ?: new AccessToken();
    }

    public function verify(string $idToken) : bool
    {
        $audience      = getenv('WEBSITE_URL');
        $expectedEmail = getenv('CRON_INVOKER_SA');

        if (!$audience || !$expectedEmail) {
            return false;
        }

        try {
            $payload = $this->accessToken->verify($idToken, [
                'audience' => $audience,
                'issuer'   => 'https://accounts.google.com',
            ]);
        } catch (\Throwable $e) {
            return false;
        }

        if (!is_array($payload)) {
            return false;
        }

        return ($payload['email'] ?? null) === $expectedEmail
               && true === ($payload['email_verified'] ?? false);
    }
}
```

Note: no `services.yaml` entry needed — `src/` autowiring registers it, and the optional `AccessToken` argument defaults to a real instance at runtime.

- [ ] **Step 4: Run test to verify it passes**

Run: `docker compose exec php php vendor/bin/phpunit tests/Security/CronTokenVerifierTest.php`
Expected: 5 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add symfony/src/Security/CronTokenVerifier.php symfony/tests/Security/CronTokenVerifierTest.php
git commit -m "[CloudRun] add OIDC verifier for Cloud Scheduler cron calls"
```

---

### Task 4: CronController trusts GAE header only on GAE, accepts OIDC elsewhere

`X-Appengine-Cron` is stripped by GAE's front end for external traffic, but NOT by Cloud Run — off GAE it is forgeable. New rule set, in order: localhost → allow; GAE header AND running on GAE (`GAE_SERVICE` env set) → allow; valid Cloud Scheduler OIDC bearer token → allow; logged-in admin → allow (existing behavior, with the existing session-save quirk); otherwise 403.

**Files:**
- Modify: `symfony/src/Controller/CronController.php`
- Test: `symfony/tests/Controller/InfrastructureRoutesTest.php`

**Interfaces:**
- Consumes: `App\Security\CronTokenVerifier::verify(string $idToken) : bool` (Task 3).

- [ ] **Step 1: Write the failing tests**

Add to `symfony/tests/Controller/InfrastructureRoutesTest.php`, in the `/cron/{key}` section. Also add `use App\Security\CronTokenVerifier;` to the imports.

```php
public function testCronRejectsSpoofedAppEngineHeaderOffGae(): void
{
    // Off App Engine (GAE_SERVICE not set), the X-Appengine-Cron header
    // is client-controlled and must NOT grant access.
    $client = static::createClient();

    $client->request('GET', '/cron/user-cron', [], [], [
        'REMOTE_ADDR'          => '203.0.113.5',
        'HTTP_X_APPENGINE_CRON' => 'true',
    ]);

    $status = $client->getResponse()->getStatusCode();
    $this->assertContains($status, [302, 403], sprintf(
        'Expected 302 or 403 for a spoofed GAE cron header off GAE; got %d', $status
    ));
}

public function testCronAcceptsAppEngineHeaderOnGae(): void
{
    // On App Engine (GAE_SERVICE set by the platform), the header is
    // stripped from external traffic and can be trusted.
    putenv('GAE_SERVICE=default');

    try {
        $client = static::createClient();

        $client->request('GET', '/cron/user-cron', [], [], [
            'REMOTE_ADDR'          => '203.0.113.5',
            'HTTP_X_APPENGINE_CRON' => 'true',
        ]);

        $this->assertResponseIsSuccessful();
    } finally {
        putenv('GAE_SERVICE');
    }
}

public function testCronAcceptsValidCloudSchedulerOidcToken(): void
{
    $client = static::createClient();

    $verifier = $this->createMock(CronTokenVerifier::class);
    $verifier->method('verify')->with('valid-token')->willReturn(true);
    $client->getContainer()->set(CronTokenVerifier::class, $verifier);

    $client->request('GET', '/cron/user-cron', [], [], [
        'REMOTE_ADDR'        => '203.0.113.5',
        'HTTP_AUTHORIZATION' => 'Bearer valid-token',
    ]);

    $this->assertResponseIsSuccessful();
}

public function testCronRejectsInvalidOidcToken(): void
{
    $client = static::createClient();

    $verifier = $this->createMock(CronTokenVerifier::class);
    $verifier->method('verify')->willReturn(false);
    $client->getContainer()->set(CronTokenVerifier::class, $verifier);

    $client->request('GET', '/cron/user-cron', [], [], [
        'REMOTE_ADDR'        => '203.0.113.5',
        'HTTP_AUTHORIZATION' => 'Bearer forged-token',
    ]);

    $status = $client->getResponse()->getStatusCode();
    $this->assertContains($status, [302, 403], sprintf(
        'Expected 302 or 403 for an invalid OIDC token; got %d', $status
    ));
}
```

Implementation note for the test-container trick: `$client->getContainer()->set(...)` replaces a service in the test container only if it has not been instantiated yet — set it BEFORE `$client->request()`, as shown. If Symfony complains the service is private/already set, make the verifier public for tests by adding to `symfony/config/services_test.yaml`:

```yaml
    App\Security\CronTokenVerifier:
        public: true
```

- [ ] **Step 2: Run tests to verify the new ones fail**

Run: `docker compose exec php php vendor/bin/phpunit tests/Controller/InfrastructureRoutesTest.php --filter=Cron`
Expected: `testCronRejectsSpoofedAppEngineHeaderOffGae` FAILS (currently 200 — the spoofed header is accepted). `testCronAcceptsValidCloudSchedulerOidcToken` FAILS (302/403). The GAE-header test may pass already (header currently always trusted) — that's fine, it pins behavior we must keep.

- [ ] **Step 3: Implement**

In `symfony/src/Controller/CronController.php`: add `use App\Security\CronTokenVerifier;`, then replace the `run()` method's auth block:

```php
    #[Route("/{key}")]
    public function run(Request $request, string $key, KernelInterface $kernel, CronTokenVerifier $verifier)
    {
        $key = str_replace('-', ':', $key);
        if (!in_array($key, self::CRONS)) {
            throw $this->createNotFoundException();
        }

        if (!$this->isTrustedCronCall($request, $verifier)) {
            if ($this->getUser() && $this->getUser()->isAdmin()) {
                $this->requestStack->getSession()->save();
            } else {
                throw $this->createAccessDeniedException();
            }
        }

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array_merge($request->query->all(), [
            'command' => $key,
        ]));

        $application->run($input, new NullOutput());

        return new Response();
    }

    private function isTrustedCronCall(Request $request, CronTokenVerifier $verifier) : bool
    {
        if ('127.0.0.1' === $request->getClientIp()) {
            return true;
        }

        // On App Engine the front end strips X-Appengine-Cron from external
        // traffic, so the header proves the call comes from GAE Cron. Off
        // GAE (e.g. Cloud Run) it is forgeable and must be ignored.
        if ('true' === $request->headers->get('X-Appengine-Cron') && getenv('GAE_SERVICE')) {
            return true;
        }

        // Cloud Scheduler authenticates with an OIDC identity token.
        $authorization = $request->headers->get('Authorization', '');
        if (0 === strpos($authorization, 'Bearer ')) {
            return $verifier->verify(substr($authorization, 7));
        }

        return false;
    }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `docker compose exec php php vendor/bin/phpunit tests/Controller/InfrastructureRoutesTest.php`
Expected: ALL PASS, including the pre-existing cron tests (localhost allow + non-whitelisted-IP reject).

- [ ] **Step 5: Add CRON_INVOKER_SA to the dist templates**

Append to BOTH `deploy/prod/dotenv.dist` and `deploy/preprod/dotenv.dist`:

```
# Service account Cloud Scheduler uses to invoke /cron/* (OIDC); token audience must be WEBSITE_URL
CRON_INVOKER_SA=cron-invoker@your-project.iam.gserviceaccount.com
```

- [ ] **Step 6: Commit**

```bash
git add symfony/src/Controller/CronController.php symfony/tests/Controller/InfrastructureRoutesTest.php deploy/prod/dotenv.dist deploy/preprod/dotenv.dist
git add symfony/config/services_test.yaml 2>/dev/null || true
git commit -m "[CloudRun] secure cron endpoint off-GAE with Cloud Scheduler OIDC"
```

---

### Task 5: Full test suite

**Files:** none (verification gate).

- [ ] **Step 1: Run the full suite**

Run (repo root): `make test`
Expected: full suite PASSES (this also recreates the test DB, catching any schema drift).

- [ ] **Step 2: Fix any regressions surfaced, then re-run until green. Commit any fixes.**

---

### Task 6: Docker image (Dockerfile, Caddyfile, entrypoint) + local smoke test

**Files:**
- Create: `.dockerignore` (repo root)
- Create: `deploy/cloudrun/Dockerfile`
- Create: `deploy/cloudrun/Caddyfile`
- Create: `deploy/cloudrun/entrypoint.sh`

**Interfaces:**
- Produces: an image that listens on `$PORT` (default 8080), serves `symfony/public` statics via Caddy, and proxies PHP to FPM on 127.0.0.1:9000. Build arg `ENV` (`prod`|`preprod`) selects which `deploy/<env>/dotenv` + service-account key get baked in. Consumed by Task 7's Cloud Build config.

Key mechanics an implementer must know:
- `public/index.php` loads `.env` (with putenv) **only when `$_SERVER['APP_ENV']` is unset** — so the Dockerfile must NOT set `ENV APP_ENV`. Runtime real env vars (from `docker run -e` or Cloud Run) win over `.env` because Symfony Dotenv never overwrites existing vars.
- `php-fpm` defaults to `clear_env = yes`, which would hide container env vars from PHP. The image must set `clear_env = no`.
- `App\Kernel::getCacheDir()` returns `sys_get_temp_dir().'/redcall/cache/'` in prod. Setting `ENV TMPDIR=/app/tmp` makes the build-time `cache:warmup` land in a path that persists in the image and stays writable at runtime.
- `generate:mjml` calls the external MJML API using credentials from the baked `.env` — it needs network at build time (fine in Cloud Build and local docker).

- [ ] **Step 1: Create `.dockerignore` at repo root**

```
.git
.deploy-backup
logs
var
docs
notes.txt
docker/mysql/data
docker/caddy/data
docker/caddy/config
symfony/node_modules
symfony/vendor
symfony/var
symfony/public/build
symfony/.env.local
```

- [ ] **Step 2: Create `deploy/cloudrun/Caddyfile`**

```
{
    auto_https off
    admin off
}

:{$PORT:8080} {
    root * /app/symfony/public

    encode zstd gzip

    # Mirrors the App Engine static handlers (/build, /bundles, images) with 1d expiration
    @static path /build/* /bundles/* *.ico *.txt *.gif *.png *.jpg
    header @static Cache-Control "public, max-age=86400"

    php_fastcgi 127.0.0.1:9000

    file_server

    log {
        output stdout
        format json
    }
}
```

- [ ] **Step 3: Create `deploy/cloudrun/entrypoint.sh`**

```sh
#!/bin/sh
set -e

php-fpm -D
exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
```

- [ ] **Step 4: Create `deploy/cloudrun/Dockerfile`**

```dockerfile
# ─── Frontend assets (Webpack Encore) ────────────────────────────────────────
FROM node:20-alpine AS assets

WORKDIR /app
COPY symfony/package.json symfony/yarn.lock ./
RUN yarn install --frozen-lockfile

COPY symfony/webpack.config.js ./
COPY symfony/assets ./assets
RUN yarn encore production

# ─── PHP base: same extension set as docker/php/Dockerfile ───────────────────
FROM php:8.4-fpm-alpine AS base

RUN apk --update --no-cache add \
      bash make g++ gcc \
      icu-dev icu-data-full \
      oniguruma-dev libzip-dev \
      freetype freetype-dev \
      libpng libpng-dev \
      libjpeg-turbo libjpeg-turbo-dev \
      libwebp libwebp-dev \
      libsodium-dev libxml2-dev \
      gmp-dev

RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install opcache pdo pdo_mysql intl mbstring bcmath zip sodium dom gd gmp

# ─── PHP dependencies ─────────────────────────────────────────────────────────
FROM base AS deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app/symfony
COPY symfony/composer.json symfony/composer.lock ./
COPY symfony/bundles ./bundles
COPY symfony/src ./src
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction

# ─── Runtime: Caddy + PHP-FPM ─────────────────────────────────────────────────
FROM base AS runtime

ARG ENV=preprod

RUN apk --no-cache add caddy

# Production PHP settings (mirrors symfony/php.ini used on GAE)
RUN { \
      echo 'memory_limit = 512M'; \
      echo 'post_max_size = 20M'; \
      echo 'upload_max_filesize = 20M'; \
      echo 'max_execution_time = 600'; \
      echo 'output_buffering = 4096'; \
      echo 'date.timezone = Europe/Paris'; \
      echo 'display_errors = Off'; \
      echo 'log_errors = On'; \
      echo 'opcache.memory_consumption = 256'; \
      echo 'opcache.max_accelerated_files = 20000'; \
      echo 'opcache.validate_timestamps = 0'; \
      echo 'realpath_cache_size = 4096K'; \
      echo 'realpath_cache_ttl = 600'; \
      echo 'session.cookie_lifetime = 43200'; \
    } > /usr/local/etc/php/conf.d/app.ini

# clear_env=no: Cloud Run env vars (PORT, overrides) must reach PHP workers.
# catch_workers_output: PHP errors go to the container's stderr → Cloud Logging.
RUN { \
      echo 'pm = dynamic'; \
      echo 'pm.max_children = 12'; \
      echo 'pm.start_servers = 3'; \
      echo 'pm.min_spare_servers = 2'; \
      echo 'pm.max_spare_servers = 6'; \
      echo 'clear_env = no'; \
      echo 'catch_workers_output = yes'; \
    } >> /usr/local/etc/php-fpm.d/www.conf

WORKDIR /app/symfony

COPY symfony/ ./
COPY --from=deps /app/symfony/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

COPY deploy/${ENV}/dotenv ./.env
COPY deploy/${ENV}/google-service-account.json ./config/keys/google-service-account.json
COPY deploy/cloudrun/Caddyfile /etc/caddy/Caddyfile
COPY deploy/cloudrun/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Kernel::getCacheDir() uses sys_get_temp_dir() in prod; pin it to a path
# that persists from build to runtime (do NOT set APP_ENV here: index.php
# only loads the baked .env when APP_ENV is absent from the environment).
ENV TMPDIR=/app/tmp
RUN mkdir -p /app/tmp

# Build-time template generation + container/cache warmup (uses the baked .env)
RUN php bin/console generate:mjml templates/message/email.html.twig.mjml \
 && php bin/console generate:mjml templates/message/image.html.twig.mjml \
 && php bin/console cache:warmup --env=prod

EXPOSE 8080
ENTRYPOINT ["/entrypoint.sh"]
```

- [ ] **Step 5: Build locally**

Run (repo root):

```bash
docker build -f deploy/cloudrun/Dockerfile --build-arg ENV=preprod -t redcall-cloudrun:smoke .
```

Expected: build completes. Likely first-run issues to fix here (not later): missing files in a COPY (adjust paths), `generate:mjml` needing a template path tweak, warmup failing on a missing env var (add it to the local gitignored `deploy/preprod/dotenv`).

- [ ] **Step 6: Smoke test against the local dev MySQL**

The dev stack's MySQL listens on host port 3309 (root, empty password, db `redcall_prod`). Real env vars override the baked `.env`:

```bash
docker compose up -d mysql
docker run -d --rm --name redcall-smoke -p 8085:8080 \
  -e DATABASE_HOST=host.docker.internal \
  -e DATABASE_PORT=3309 \
  -e DATABASE_NAME=redcall_prod \
  -e DATABASE_USER=root \
  -e DATABASE_PASSWORD= \
  -e WEBSITE_URL=http://127.0.0.1:8085 \
  redcall-cloudrun:smoke

sleep 3
curl -sSI http://127.0.0.1:8085/                          # expect 200 or 302 (redirect to /connect), NOT 5xx
# static asset with cache header — pick a real file from the build dir:
docker exec redcall-smoke sh -c "ls public/build | head -3"
curl -sSI http://127.0.0.1:8085/build/<one-of-those-files>  # expect 200 + Cache-Control: public, max-age=86400
curl -sS -o /dev/null -w '%{http_code}\n' -H 'X-Appengine-Cron: true' http://127.0.0.1:8085/cron/user-cron  # expect 302 or 403 (spoofed header rejected)
docker logs redcall-smoke | tail -20                       # JSON access logs from Caddy
docker stop redcall-smoke
```

Expected: statuses as annotated. Iterate on the image until all pass.

- [ ] **Step 7: Commit**

```bash
git add .dockerignore deploy/cloudrun/Dockerfile deploy/cloudrun/Caddyfile deploy/cloudrun/entrypoint.sh
git commit -m "[CloudRun] add production container image (Caddy + PHP-FPM)"
```

---

### Task 7: Deploy tooling (Cloud Build config, .gcloudignore, deploy script)

**Files:**
- Create: `deploy/cloudrun/cloudbuild.yaml`
- Create: `.gcloudignore` (repo root)
- Create: `deploy/deploy-cloudrun.sh` (executable)
- Modify: `deploy/prod/dotenv.dist`, `deploy/preprod/dotenv.dist` (TRUSTED_PROXIES)

**Interfaces:**
- Consumes: Task 6's Dockerfile (build arg `ENV`).
- Produces: `./deploy/deploy-cloudrun.sh <prod|preprod>` → builds via Cloud Build, pushes to Artifact Registry repo `redcall`, deploys Cloud Run service `redcall`, prints the service URL.

Key mechanics:
- `gcloud builds submit` excludes `.gitignore`d files unless a `.gcloudignore` exists. The gitignored `deploy/*/dotenv` + service-account JSON MUST upload with the build context, so `.gcloudignore` is mandatory and must NOT list them.
- Behind Cloud Run's front end, Symfony needs `TRUSTED_PROXIES=REMOTE_ADDR` (handled in `public/index.php:34`) so `getClientIp()` and the `https` scheme resolve correctly.

- [ ] **Step 1: Create `.gcloudignore` at repo root**

Same exclusions as `.dockerignore`, WITHOUT excluding the deploy secrets:

```
.git
.deploy-backup
logs
var
docs
notes.txt
docker/mysql/data
docker/caddy/data
docker/caddy/config
symfony/node_modules
symfony/vendor
symfony/var
symfony/public/build
symfony/.env.local
```

- [ ] **Step 2: Create `deploy/cloudrun/cloudbuild.yaml`**

```yaml
steps:
  - name: gcr.io/cloud-builders/docker
    args:
      - build
      - -f
      - deploy/cloudrun/Dockerfile
      - --build-arg
      - ENV=${_ENV}
      - -t
      - ${_IMAGE}
      - .
images:
  - ${_IMAGE}
options:
  machineType: E2_HIGHCPU_8
timeout: 3600s
```

- [ ] **Step 3: Create `deploy/deploy-cloudrun.sh`**

```bash
#!/usr/bin/env bash

set -euo pipefail

# ─── Configuration ────────────────────────────────────────────────────────────

GCP_ACCOUNT="alain.tiemblo@croix-rouge.fr"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
SERVICE="redcall"
REGION="europe-west1"
AR_REPO="redcall"

log()   { echo "==> $*"; }
error() { echo "ERROR: $*" >&2; }

# ─── Validate arguments ──────────────────────────────────────────────────────

ENV="${1:-}"

if [[ -z "$ENV" ]]; then
  echo "Usage: $0 <prod|preprod>"
  exit 1
fi

case "$ENV" in
  prod)
    GCP_PROJECT="redcall-prod-260921"
    VPC_CONNECTOR="gae-serverless-conn-prod"
    ;;
  preprod)
    GCP_PROJECT="redcall-dev"
    VPC_CONNECTOR="serverless-connector"
    ;;
  *)
    error "No GCP project configured for environment '$ENV'."
    exit 1
    ;;
esac

for file in "$SCRIPT_DIR/$ENV/dotenv" "$SCRIPT_DIR/$ENV/google-service-account.json"; do
  if [[ ! -f "$file" ]]; then
    error "Missing deploy config: $file"
    exit 1
  fi
done

GCLOUD=(gcloud --project="$GCP_PROJECT" --account="$GCP_ACCOUNT")

# ─── Build via Cloud Build ────────────────────────────────────────────────────

TAG="$(git -C "$ROOT_DIR" rev-parse --short HEAD)-$(date +%Y%m%d%H%M%S)"
IMAGE="$REGION-docker.pkg.dev/$GCP_PROJECT/$AR_REPO/$SERVICE:$TAG"

if ! "${GCLOUD[@]}" artifacts repositories describe "$AR_REPO" --location="$REGION" &>/dev/null; then
  log "Creating Artifact Registry repository '$AR_REPO'..."
  "${GCLOUD[@]}" artifacts repositories create "$AR_REPO" \
    --location="$REGION" --repository-format=docker
fi

log "Building $IMAGE with Cloud Build..."
"${GCLOUD[@]}" builds submit "$ROOT_DIR" \
  --config="$SCRIPT_DIR/cloudrun/cloudbuild.yaml" \
  --substitutions="_ENV=$ENV,_IMAGE=$IMAGE"

# ─── Deploy to Cloud Run ──────────────────────────────────────────────────────

log "Deploying $SERVICE to Cloud Run ($GCP_PROJECT)..."
"${GCLOUD[@]}" run deploy "$SERVICE" \
  --image="$IMAGE" \
  --region="$REGION" \
  --platform=managed \
  --vpc-connector="$VPC_CONNECTOR" \
  --concurrency=10 \
  --min-instances=0 \
  --max-instances=10 \
  --memory=1Gi \
  --cpu=1 \
  --timeout=600 \
  --port=8080 \
  --allow-unauthenticated

URL="$("${GCLOUD[@]}" run services describe "$SERVICE" --region="$REGION" --format='value(status.url)')"
log "Deployed: $URL"
```

Then: `chmod +x deploy/deploy-cloudrun.sh`

Notes locked in by the spec: `--concurrency 10 --min-instances 0 --max-instances 10` mirrors `automatic_scaling`; 1 CPU/1Gi mirrors F4_1G; `--timeout 600` matches GAE's 10-minute allowance for cron/task requests (PHP `max_execution_time` is 600 in the image for the same reason); `--allow-unauthenticated` because `/twilio`, `/msg`, `/syn`, `/space`, webhooks must stay anonymous — the app does its own auth.

- [ ] **Step 4: Add TRUSTED_PROXIES to the dist templates**

Append to BOTH `deploy/prod/dotenv.dist` and `deploy/preprod/dotenv.dist`:

```
# Cloud Run sits behind Google's HTTPS front end; trust X-Forwarded-* from the direct peer
TRUSTED_PROXIES=REMOTE_ADDR
```

- [ ] **Step 5: Sanity-check the script (no GCP calls)**

Run: `bash -n deploy/deploy-cloudrun.sh && ./deploy/deploy-cloudrun.sh 2>&1 | head -1`
Expected: syntax OK; usage line printed when called without args.

- [ ] **Step 6: Commit**

```bash
git add .gcloudignore deploy/cloudrun/cloudbuild.yaml deploy/deploy-cloudrun.sh deploy/prod/dotenv.dist deploy/preprod/dotenv.dist
git commit -m "[CloudRun] add Cloud Build + Cloud Run deploy tooling"
```

---

### Task 8: Cloud Scheduler bootstrap script

**Files:**
- Create: `deploy/cloudrun/init-scheduler.sh` (executable)

**Interfaces:**
- Consumes: the deployed Cloud Run service URL; `WEBSITE_URL` from `deploy/<env>/dotenv` (used as the OIDC audience — must match what `CronTokenVerifier` expects).
- Produces: 9 idempotent Cloud Scheduler jobs (create-or-update) mirroring `deploy/*/cron.yaml`, plus the `cron-invoker` service account.

- [ ] **Step 1: Create `deploy/cloudrun/init-scheduler.sh`**

```bash
#!/usr/bin/env bash

set -euo pipefail

# Creates (or updates) the Cloud Scheduler jobs replacing App Engine cron.yaml.
# Schedules mirror cron.yaml: 2 hourly jobs, 7 daily jobs (staggered at night).

GCP_ACCOUNT="alain.tiemblo@croix-rouge.fr"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DEPLOY_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
SERVICE="redcall"
REGION="europe-west1"

log()   { echo "==> $*"; }
error() { echo "ERROR: $*" >&2; }

ENV="${1:-}"

case "$ENV" in
  prod)    GCP_PROJECT="redcall-prod-260921" ;;
  preprod) GCP_PROJECT="redcall-dev" ;;
  *)
    echo "Usage: $0 <prod|preprod>"
    exit 1
    ;;
esac

GCLOUD=(gcloud --project="$GCP_PROJECT" --account="$GCP_ACCOUNT")
INVOKER_SA="cron-invoker@$GCP_PROJECT.iam.gserviceaccount.com"

SERVICE_URL="$("${GCLOUD[@]}" run services describe "$SERVICE" --region="$REGION" --format='value(status.url)')"
if [[ -z "$SERVICE_URL" ]]; then
  error "Cloud Run service '$SERVICE' not found; deploy it first."
  exit 1
fi

# The OIDC token audience must equal WEBSITE_URL: CronTokenVerifier pins it.
AUDIENCE="$(grep '^WEBSITE_URL=' "$DEPLOY_DIR/$ENV/dotenv" | cut -d= -f2-)"
if [[ -z "$AUDIENCE" ]]; then
  error "WEBSITE_URL not found in $DEPLOY_DIR/$ENV/dotenv"
  exit 1
fi

if ! "${GCLOUD[@]}" iam service-accounts describe "$INVOKER_SA" &>/dev/null; then
  log "Creating service account $INVOKER_SA..."
  "${GCLOUD[@]}" iam service-accounts create cron-invoker \
    --display-name="Cloud Scheduler invoker for RedCall crons"
fi

"${GCLOUD[@]}" run services add-iam-policy-binding "$SERVICE" --region="$REGION" \
  --member="serviceAccount:$INVOKER_SA" --role="roles/run.invoker" >/dev/null

create_job() {
  local name="$1" path="$2" schedule="$3"
  local verb="create"

  if "${GCLOUD[@]}" scheduler jobs describe "$name" --location="$REGION" &>/dev/null; then
    verb="update"
  fi

  log "$verb job $name ($schedule) -> $path"
  "${GCLOUD[@]}" scheduler jobs "$verb" http "$name" \
    --location="$REGION" \
    --schedule="$schedule" \
    --time-zone="Europe/Paris" \
    --uri="$SERVICE_URL$path" \
    --http-method=GET \
    --oidc-service-account-email="$INVOKER_SA" \
    --oidc-token-audience="$AUDIENCE" \
    --attempt-deadline=660s
}

create_job redcall-twilio-price         /cron/twilio-price         "0 * * * *"
create_job redcall-report-communication /cron/report-communication "30 * * * *"
create_job redcall-user-cron            /cron/user-cron            "0 2 * * *"
create_job redcall-clear-campaign       /cron/clear-campaign       "10 2 * * *"
create_job redcall-clear-media          /cron/clear-media          "20 2 * * *"
create_job redcall-clear-space          /cron/clear-space          "30 2 * * *"
create_job redcall-clear-expirable     /cron/clear-expirable       "40 2 * * *"
create_job redcall-sync-data            /cron/sync-data            "0 3 * * *"
create_job redcall-import-national      /cron/import-national      "0 4 * * *"

log "Scheduler jobs ready."
```

Then: `chmod +x deploy/cloudrun/init-scheduler.sh`

- [ ] **Step 2: Syntax check**

Run: `bash -n deploy/cloudrun/init-scheduler.sh && ./deploy/cloudrun/init-scheduler.sh 2>&1 | head -1`
Expected: syntax OK; usage line printed.

- [ ] **Step 3: Commit**

```bash
git add deploy/cloudrun/init-scheduler.sh
git commit -m "[CloudRun] add Cloud Scheduler bootstrap replacing cron.yaml"
```

---

### Task 9: Preprod deployment & verification (interactive — real GCP)

**Files:**
- Modify (LOCAL ONLY, gitignored): `deploy/preprod/dotenv`

**Interfaces:**
- Consumes: everything above.
- Produces: a live, verified Cloud Run preprod at the `run.app` URL and (if DNS allows) `https://dev.redcall.minutis.croix-rouge.fr`.

This task talks to real GCP. Expect `gcloud` auth/permission prompts. If a step fails on a missing API, enable it and retry (`run.googleapis.com`, `cloudbuild.googleapis.com`, `artifactregistry.googleapis.com`, `cloudscheduler.googleapis.com`, `iam.googleapis.com`).

- [ ] **Step 1: Update the local preprod dotenv** (gitignored — do not commit)

Append to `deploy/preprod/dotenv`:

```
GOOGLE_TASK_PROCESS=http
CRON_INVOKER_SA=cron-invoker@redcall-dev.iam.gserviceaccount.com
TRUSTED_PROXIES=REMOTE_ADDR
```

- [ ] **Step 2: Start the preprod Cloud SQL instance** (it is stopped)

```bash
gcloud sql instances list --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr
gcloud sql instances patch <INSTANCE_NAME> --activation-policy=ALWAYS --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr
```

Expected: instance state RUNNABLE after a few minutes. Verify its private IP matches `DATABASE_HOST` in `deploy/preprod/dotenv` (`10.177.80.3`).

- [ ] **Step 3: Verify the VPC connector exists**

```bash
gcloud compute networks vpc-access connectors describe serverless-connector --region=europe-west1 --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr --format='value(state)'
```

Expected: `READY`. If the connector was deleted while preprod was off, recreate it on the same VPC/subnet as Cloud SQL's private IP before continuing.

- [ ] **Step 4: Deploy**

```bash
./deploy/deploy-cloudrun.sh preprod
```

Expected: Cloud Build succeeds (~10-20 min first time), `gcloud run deploy` succeeds, script prints the service URL. Iterate on failures — this is the step where real-world issues surface (API enablement, IAM on the Cloud Build SA, image boot failures visible via `gcloud run services logs read redcall --region=europe-west1 --project=redcall-dev`).

- [ ] **Step 5: Smoke-test the run.app URL**

```bash
URL=$(gcloud run services describe redcall --region=europe-west1 --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr --format='value(status.url)')
curl -sSI "$URL/"                                     # 200 or 302 to /connect — proves PHP + DB via connector
curl -sS -o /dev/null -w '%{http_code}\n' -H 'X-Appengine-Cron: true' "$URL/cron/user-cron"   # 302/403 — spoof rejected
curl -sS -o /dev/null -w '%{http_code}\n' -X POST -d '{}' "$URL/cloud-task"                    # 400 — route wired, bad signature rejected
```

Also load the URL in a browser: login page must render with CSS/JS (statics from Caddy).

- [ ] **Step 6: Create Scheduler jobs and run one end-to-end**

```bash
./deploy/cloudrun/init-scheduler.sh preprod
gcloud scheduler jobs run redcall-clear-media --location=europe-west1 --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr
sleep 20
gcloud scheduler jobs describe redcall-clear-media --location=europe-west1 --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr --format='value(status.lastAttemptTime,status)'
gcloud run services logs read redcall --region=europe-west1 --project=redcall-dev --limit=20
```

Expected: the job's last attempt succeeded (HTTP 200 in the service logs for `/cron/clear-media`) — this proves the OIDC path end-to-end (audience = `https://dev.redcall.minutis.croix-rouge.fr`, which `CronTokenVerifier` reads from the baked `WEBSITE_URL`).

- [ ] **Step 7: Prove Cloud Tasks HTTP delivery reaches the service**

```bash
gcloud tasks create-http-task --queue=messages-sms --location=europe-west1 --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr \
  --url="$URL/cloud-task" --method=POST --body-content='{}' probe-task-1
sleep 15
gcloud run services logs read redcall --region=europe-west1 --project=redcall-dev --limit=10
```

Expected: a POST to `/cloud-task` appears with status 400 ("Request does not have a body"/invalid payload) — proving the queue can deliver HTTP tasks to Cloud Run; real tasks (valid HMAC) will succeed. Delete the probe if it retries: `gcloud tasks delete probe-task-1 --queue=messages-sms --location=europe-west1 --project=redcall-dev`.

- [ ] **Step 8: Move the preprod domain**

```bash
# What does DNS point at today?
dig +short CNAME dev.redcall.minutis.croix-rouge.fr
# Existing GAE mapping?
gcloud app domain-mappings list --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr
```

- If DNS CNAMEs to `ghs.googlehosted.com`: delete the GAE mapping, create the Cloud Run one — no DNS change needed:

```bash
gcloud app domain-mappings delete dev.redcall.minutis.croix-rouge.fr --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr
gcloud beta run domain-mappings create --service=redcall --domain=dev.redcall.minutis.croix-rouge.fr --region=europe-west1 --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr
```

Wait for cert provisioning (`gcloud beta run domain-mappings describe --domain=... --region=europe-west1`), then `curl -sSI https://dev.redcall.minutis.croix-rouge.fr/` → 200/302.

- If DNS points elsewhere (A records to GAE IPs, or croix-rouge.fr DNS is centrally managed): STOP and report the exact DNS records the Red Cross DNS admin must set (the `gcloud beta run domain-mappings create` output lists them). The `run.app` URL remains fully usable meanwhile.

- [ ] **Step 9: Report results to the user** — service URL, what was verified, domain status, anything deferred.

---

### Task 10: Preprod GAE cleanup + prod cutover runbook

**Files:**
- Create: `deploy/cloudrun/README.md`
- Modify: `deploy/deploy.sh` (deprecation notice only)

- [ ] **Step 1: Clean up the preprod App Engine deployment** (authorized by the user; preprod GAE is already stopped)

```bash
gcloud app services list --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr
gcloud app versions list --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr
# stop every version still marked SERVING:
gcloud app versions stop <VERSION_IDS> --service=default --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr
# delete all non-serving versions (GAE refuses to delete the last version of the default service — stopping it is enough):
gcloud app versions delete <VERSION_IDS...> --service=default --project=redcall-dev --account=alain.tiemblo@croix-rouge.fr
```

Note: an App Engine *application* cannot be deleted without deleting the whole GCP project (which hosts Cloud SQL, buckets, Task queues — it stays). Fully disabling the app is Console-only: App Engine → Settings → Disable application; mention this to the user as an optional manual step.

- [ ] **Step 2: Write `deploy/cloudrun/README.md`**

Content: how to deploy (`./deploy/deploy-cloudrun.sh <env>` + `./deploy/cloudrun/init-scheduler.sh <env>`), the env vars the Cloud Run deployment needs in `deploy/<env>/dotenv` (`GOOGLE_TASK_PROCESS=http`, `CRON_INVOKER_SA`, `TRUSTED_PROXIES=REMOTE_ADDR`), and the **prod cutover runbook** copied from spec §5 (deploy prod image → smoke-test on run.app → create prod Scheduler jobs paused → move prod domain → resume jobs, pause GAE cron → rollback = point domain back at GAE → later: disable GAE, migrate secrets to Secret Manager). Include the check that Google OAuth redirect URIs, Minutis SSO, and Twilio webhook URLs reference the domain, not `appspot.com`.

- [ ] **Step 3: Add a deprecation notice at the top of `deploy/deploy.sh`**

```bash
# NOTE: this script deploys to App Engine and is kept for prod rollback only.
# Cloud Run deployments (the current target) use deploy/deploy-cloudrun.sh
# — see deploy/cloudrun/README.md.
```

(right after the `#!/usr/bin/env bash` line).

- [ ] **Step 4: Commit**

```bash
git add deploy/cloudrun/README.md deploy/deploy.sh
git commit -m "[CloudRun] document Cloud Run deployment and prod cutover runbook"
```

- [ ] **Step 5: Final check** — `make test` one last time; report completion with: service URL, domain status, GAE cleanup results, and the list of follow-ups (Secret Manager, prod cutover).
