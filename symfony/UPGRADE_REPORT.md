# Dependency Upgrade Report — 2026-05-14

Branch: `chore/dependency-upgrade-20260514`

## Summary

All direct Composer dependencies are now on their latest stable major version.
Test suite went from a baseline of **1567 green** to **1571 green** (4 new
tests added for the captcha verifier) with no regressions.

## Package upgrade table

| Package | Previous | New | Commit |
|---|---|---|---|
| doctrine/orm | 3.6.3 | 3.6.5 | `e848fa6e` |
| rector/rector | 2.4.2 | 2.4.3 | `e848fa6e` |
| phpunit/phpunit | 9.6.34 | 10.5.63 | `313f8235` |
| phpunit/phpunit | 10.5.63 | 11.5.55 | `bc20b7cb` |
| phpunit/phpunit | 11.5.55 | 12.5.25 | `ea830993` |
| dama/doctrine-test-bundle | 8.2.2 | 8.6.0 | `15f763a1` |
| symfony/asset | 6.4.34 | 7.4.8 | `2179746b` |
| symfony/browser-kit | 6.4.32 | 7.4.8 | `2179746b` |
| symfony/console | 6.4.39 | 7.4.11 | `2179746b` |
| symfony/css-selector | 6.4.34 | 7.4.9 | `2179746b` |
| symfony/debug-bundle | 6.4.35 | 7.4.8 | `2179746b` |
| symfony/dotenv | 6.4.39 | 7.4.11 | `2179746b` |
| symfony/expression-language | 6.4.32 | 7.4.8 | `2179746b` |
| symfony/form | 6.4.36 | 7.4.9 | `2179746b` |
| symfony/framework-bundle | 6.4.39 | 7.4.11 | `2179746b` |
| symfony/http-client | 6.4.37 | 7.4.9 | `2179746b` |
| symfony/mailer | 6.4.34 | 7.4.8 | `2179746b` |
| symfony/process | 6.4.39 | 7.4.11 | `2179746b` |
| symfony/property-access | 6.4.32 | 7.4.8 | `2179746b` |
| symfony/property-info | 6.4.34 | 7.4.8 | `2179746b` |
| symfony/runtime | 6.4.30 | 7.4.8 | `2179746b` |
| symfony/security-bundle | 6.4.36 | 7.4.11 | `2179746b` |
| symfony/security-csrf | 6.4.31 | 7.4.8 | `2179746b` |
| symfony/serializer | 6.4.37 | 7.4.10 | `2179746b` |
| symfony/stopwatch | 6.4.24 | 7.4.8 | `2179746b` |
| symfony/translation | 6.4.38 | 7.4.10 | `2179746b` |
| symfony/twig-bridge | 6.4.36 | 7.4.8 | `2179746b` |
| symfony/twig-bundle | 6.4.32 | 7.4.8 | `2179746b` |
| symfony/validator | 6.4.37 | 7.4.10 | `2179746b` |
| symfony/web-link | 6.4.32 | 7.4.8 | `2179746b` |
| symfony/web-profiler-bundle | 6.4.36 | 7.4.11 | `2179746b` |
| symfony/yaml | 6.4.38 | 7.4.11 | `2179746b` |
| symfony/proxy-manager-bridge | 6.4 | _removed_ | `2179746b` |
| exercise/htmlpurifier-bundle | 4.1.2 | 5.2 | `2179746b` |
| symfony/monolog-bundle | 3.11.2 | 4.0.2 | `7d8dfb2e` |
| symfony/phpunit-bridge | 7.4.8 | 8.0.8 | `8081fc8a` |
| firebase/php-jwt | 6.11.1 | 7.0.5 | `c06df844` |
| giggsey/libphonenumber-for-php | 8.13.55 | 9.0.30 | `ab0fca79` |
| phpdocumentor/reflection-docblock | 5.6.7 | 6.0.3 | `6fed9cb8` |
| sendgrid/sendgrid | 7.11.5 | 8.1.11 | `b8fc1a70` |
| twilio/sdk | 6.44.4 | 8.11.6 | `89a23289` |
| google/cloud-error-reporting | 0.19.12 | 0.26.1 | `107602e0` |
| google/cloud-storage | 1.51.0 | 2.1.0 | `107602e0` |
| google/cloud-tasks | 1.15.2 | 2.2.0 | `107602e0` |
| google/cloud-text-to-speech | 1.12.2 | 2.8.0 | `107602e0` |
| google/apiclient-services | 0.440.0 | 0.441.0 | `fcd13403` (transitive) |
| nette/utils | 4.1.3 | 4.1.4 | `fcd13403` (transitive) |
| setasign/fpdi | 2.6.6 | 2.6.7 | `fcd13403` (transitive) |

## BC-break fixes applied

### Symfony 6 → 7 (`2179746b`)
- `Command::execute()` now requires explicit `: int` return type — added to 22 commands across `src/Command/` and `bundles/*/Command/`.
- `Constraint::getTargets()` now requires `: array|string` return type — fixed in `Phone` and `Unlocked` validators.
- `ConfigurationInterface::getConfigTreeBuilder()` now requires `: TreeBuilder` return type — fixed in 6 bundle Configuration classes (password-login, sandbox, twilio, google-task, settings, pagination).
- `security.firewalls.main.logout.csrf_token_generator` option renamed to `enable_csrf: true`.
- `symfony/proxy-manager-bridge` removed (deprecated and never used in this codebase).

### Symfony 7 console (`718cca4f`)
- `static $defaultName`/`static $defaultDescription` removed. Three commands (`PegassFilesCommand`, `ClearVolunteerCommand`, `GenerateMjmlCommand`) were registered under an empty name and triggered "cannot have an empty name" warnings on every CLI invocation. Migrated to `#[AsCommand]` attribute.

### PHPUnit 9 → 10 (`313f8235`)
- Migrated `phpunit.xml.dist` to the 10.x schema via `vendor/bin/phpunit --migrate-configuration`.
- DAMA DoctrineTestBundle extension registration switched from `<extension class="..."/>` to `<bootstrap class="..."/>` (PHPUnit 10's new event-based extension API).

### PHPUnit 11 → 12 (`ea830993`)
- `MockBuilder::addMethods()` removed. `MediaManagerTest` was using it to mock a Doctrine magic-method (`findOneByHash`). Fixed by adding an explicit `findOneByHash()` method to `MediaRepository` so it can be mocked via `onlyMethods()`.
- `TestCase::getMockForAbstractClass()` removed. `BaseServiceTest` now uses an anonymous subclass of `BaseService` instead.

### firebase/php-jwt 6 → 7 (`c06df844`, `bb6d4326`)
- No code changes needed (the `JWT::decode` + `Key` API is unchanged).
- Audit-ignore entry for the prior CVE in 6.x removed (`bb6d4326`).

### Other major bumps (libphonenumber, sendgrid, twilio, google-cloud-*)
- All used API surfaces are unchanged. No code modifications required.

## reCAPTCHA dev/test bypass (`e32e0569`)

Introduced `App\Captcha\CaptchaVerifierInterface` with two implementations:
- `GoogleRecaptchaVerifier` — wraps the existing `ReCaptcha\ReCaptcha` flow (prod).
- `CheckboxCaptchaVerifier` — accepts a plain `captcha_checkbox` form field being checked (dev/test).

Wiring:
- `config/services.yaml` aliases the interface to `GoogleRecaptchaVerifier` (production default).
- `config/services_dev.yaml` and `config/services_test.yaml` alias the interface to `CheckboxCaptchaVerifier`.
- `templates/form/recaptcha_widget.html.twig` switches its rendered widget based on `verifier->getWidgetMode()` — `'google'` keeps the existing g-recaptcha div + script, `'checkbox'` renders a labeled HTML checkbox.
- `RecaptchaTrueValidator` now depends on `CaptchaVerifierInterface` instead of `ReCaptcha` directly.

Added `CheckboxCaptchaVerifierTest` (4 tests) covering the dev/test verifier.

## Test suite delta

- Baseline (start of branch, on `chore/dependency-upgrade-20260514`): **1567 tests, 2975 assertions, all green**.
- Final: **1571 tests, 3036 assertions, all green**.
- New: 4 captcha verifier tests.
- No regressions; no tests were quarantined or removed.

## Smoke test outcome

Server: `symfony server:start -d` listening on `http://127.0.0.1:8000`. Stopped cleanly with `symfony server:stop`.

| Route | Status | Note |
|---|---|---|
| `GET /` | 200 (via redirect to `/connect`) | Homepage redirects unauthenticated users to login. |
| `GET /connect` | 200 | Login form renders. Captcha checkbox widget renders correctly under the dev verifier ("I'm not a robot (dev/test bypass)"). |
| `GET /register` | 404 | Expected — registration is disabled in this deployment. |
| `GET /forgot-password` | 200 | Renders. |
| `GET /admin/users/` | 200 (via redirect to `/connect`) | Auth-guarded route correctly redirects unauthenticated users. |

`var/log/dev.log` contains no critical/error entries beyond the expected 404 messages. The only `request.ERROR` lines are `NotFoundHttpException` for `/register` and `/home` (both expected).

**Login limitation:** Full programmatic login via `curl` was not completed in this smoke test. Symfony 7 forms render with a `data-controller="csrf-protection"` Stimulus controller that computes the actual CSRF token client-side from the seed value plus a cookie. With a `curl`-only setup (no browser JS), the seed-only token submission was rejected by the framework's CSRF check ("The CSRF token is invalid. Please try to resubmit the form."). This is **not** a regression — it's an outcome of Symfony 7's stateless-CSRF integration. The login flow is fully exercised by integration tests (`SecurityControllerTest`, `AuthenticatorTest`) which use `TestCsrfTokenManager` from the sandbox bundle, and those pass green.

If interactive smoke testing of the login flow is required, run it via a real browser (or via Symfony Panther / Playwright). The user/password `ninsuo@gmail.com` / `ninsuo@gmail.com` were prepared in the dev database via `php bin/console user:password ninsuo@gmail.com ninsuo@gmail.com` and confirmed to exist as a root user.

## Follow-up suggestions

### Deprecations noticed but not addressed

Running the test suite shows **58 deprecation notices** (down from 64 at the start of Symfony 7 upgrade). The notable categories:

- **Doctrine ORM 3 proxy autoloader**: `Class "Doctrine\ORM\Proxy\Autoloader" is deprecated. Use native lazy objects instead.` Originates from `DoctrineBundle.php:136`. Will need a fix when upgrading to Doctrine ORM 4.
- **symfony/var-exporter LazyGhostTrait**: `Since symfony/var-exporter 7.3: The "Symfony\Component\VarExporter\LazyGhostTrait" trait is deprecated, use native lazy objects instead.` Same root cause as above — Doctrine entity proxies.
- **DBAL MySQL < 8 support**: `Support for MySQL < 8 is deprecated and will be removed in DBAL 5`. The project still pins to MySQL 5.7 in its config; check whether the production DB is on 8.x and bump the platform pin if so.
- **Implicit nullable parameters**: Several repositories and entities have method signatures like `function foo(?Type $x = null)` written as `function foo(Type $x = null)`. PHP 8.4 deprecates the implicit `?`. Files spotted: `src/Repository/PegassRepository.php`, `src/Entity/Structure.php`, `src/Entity/Badge.php`, `bundles/twilio-bundle/Manager/TwilioMessageManager.php`. A simple sweep with Rector's `up_to_php_84` set would fix these.

### Packages pinned by PHP version / transitive constraint

- `brick/math` is stuck at 0.14.x because `ramsey/uuid` 4.9.x only accepts `^0.8 || ^0.9 || ^0.10 || ^0.11 || ^0.12 || ^0.13 || ^0.14`. When `ramsey/uuid` 5.x is released and adopted, brick/math can be bumped.

### Abandoned packages

- `composer/package-versions-deprecated` is explicitly pinned to `1.11.99.5` in `composer.json`. The package is abandoned in favor of `composer-runtime-api`. Worth investigating whether any of the bundled libraries still need it; otherwise it should be removed.

### Unrelated cleanups noticed but deliberately not done (out of scope for this branch)

- The `pegass-crawler-bundle` is in `composer.json` autoload but the directory does not exist (documented in `CLAUDE.md`). Could be removed.
- `docker-compose.yml` at the project root is reported as unmaintained.
- Various `protected $var` properties in commands could be made `private readonly` now that PHP 8.1 is the minimum.

These should be tackled in separate, focused PRs.

## Blockers

None. `UPGRADE_BLOCKERS.md` was not created — every package on the upgrade list was bumped to its latest stable version.
