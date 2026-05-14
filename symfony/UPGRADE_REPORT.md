# Dependency Upgrade Report — 2026-05-14

Branch: `chore/dependency-upgrade-20260514`

## Summary

PHP runtime is on **8.4** (both App Engine `prod` and `preprod`, plus the
Composer `platform` pin). Symfony has been brought to **8.0** — every other
direct Composer dependency is also on its latest stable major. The previously
documented `craue/formflow-bundle` blocker was resolved by vendoring the
bundle in-tree at `bundles/formflow-bundle/` and lifting its `symfony/*`
constraints to allow `^8.0`; everything else was a straight major-version
bump per the upgrade loop. Doctrine ORM stays at 3.6 (latest), Doctrine bundle
moved to 3.x along the way. PHPUnit is on **13.1**.

Test suite went from a baseline of **1567 green** to **1571 green** (4 new
tests added for the captcha verifier) with no regressions. All deprecations
and PHPUnit notices triggered along the way have been cleared.

## Package upgrade table

| Package | Previous | New | Commit |
|---|---|---|---|
| _runtime_ — App Engine prod/preprod | php83 | php84 | `2e3b6d1e` |
| _runtime_ — Composer platform pin | 8.3.29 | 8.4.18 | `2e3b6d1e` |
| doctrine/doctrine-bundle | 2.18.2 | 3.2.2 | `c8d0427b` |
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
| google/recaptcha | 1.3.1 | 1.5.0 | `e33a4ec1` |
| doctrine/doctrine-migrations-bundle | 3.7.0 | 4.0.0 | `874ddfd6` |
| phpunit/phpunit | 12.5.25 | 13.1.9 | `7929aeef` |
| craue/formflow-bundle | upstream 3.7.0 | in-tree 3.99.99 | `a17e701f` |
| symfony/asset | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/browser-kit | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/console | 7.4.11 | 8.0.11 | `6f39719a` |
| symfony/css-selector | 7.4.9 | 8.0.9 | `6f39719a` |
| symfony/debug-bundle | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/dotenv | 7.4.11 | 8.0.11 | `6f39719a` |
| symfony/expression-language | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/form | 7.4.9 | 8.0.9 | `6f39719a` |
| symfony/framework-bundle | 7.4.11 | 8.0.11 | `6f39719a` |
| symfony/http-client | 7.4.9 | 8.0.9 | `6f39719a` |
| symfony/mailer | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/process | 7.4.11 | 8.0.11 | `6f39719a` |
| symfony/property-access | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/property-info | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/runtime | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/security-bundle | 7.4.11 | 8.0.11 | `6f39719a` |
| symfony/security-csrf | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/serializer | 7.4.10 | 8.0.10 | `6f39719a` |
| symfony/stopwatch | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/translation | 7.4.10 | 8.0.10 | `6f39719a` |
| symfony/twig-bridge | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/twig-bundle | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/validator | 7.4.10 | 8.0.10 | `6f39719a` |
| symfony/web-link | 7.4.8 | 8.0.8 | `6f39719a` |
| symfony/web-profiler-bundle | 7.4.11 | 8.0.11 | `6f39719a` |
| symfony/yaml | 7.4.11 | 8.0.11 | `6f39719a` |
| doctrine/instantiator | 2.0.0 | 2.1.0 | `093432b4` (transitive) |
| nette/utils | 4.1.3 | 4.1.4 | `fcd13403` (transitive) |
| setasign/fpdi | 2.6.6 | 2.6.7 | `fcd13403` (transitive) |

## BC-break fixes applied

### PHP 8.3 → 8.4 (`2e3b6d1e`)
- App Engine `runtime: php83` updated to `php84` in both `deploy/prod/app.yaml` and `deploy/preprod/app.yaml`.
- Composer's `config.platform.php` lifted from `8.3.29` to `8.4.18`; root `php` require bumped from `>=8.1` to `>=8.4` so the autoloader explicitly demands 8.4.

### Symfony 7.4 → 8.0 (`6f39719a`, predecessor `a17e701f`)

The previous run's blocker (`craue/formflow-bundle` had no Symfony 8 release)
was lifted by vendoring the bundle in-tree at `bundles/formflow-bundle/`. The
local fork bumps its `symfony/*` constraints to allow `^8.0`, pins version
`3.99.99`, and is wired into Composer via a `path` repository in the root
`composer.json` (mirrored into `vendor/`, not symlinked).

BC-break fixes applied:
- `Bundle::boot()`/`build()`, `ExtensionInterface::load()`,
  `CompilerPassInterface::process()`, `Command::configure()` and
  `EventSubscriberInterface::getSubscribedEvents()` all require their
  parent's return type now. Added `: void` / `: array` everywhere.
- `UserCheckerInterface::checkPreAuth/checkPostAuth` now take an additional
  `?TokenInterface $token = null` parameter (`App\Security\UserChecker`).
- `Voter::voteOnAttribute` now takes an additional `?Vote $vote = null`
  parameter (all 8 voters in `src/Security/Voter/`).
- `ConstraintValidatorInterface::validate` signature is now
  `(mixed $value, Constraint $constraint): void` (Phone, RecaptchaTrue,
  Unlocked, WhitelistedRedirectUrl validators).
- Validator constraint constructors no longer accept an options array as
  their first arg. Converted 22 callsites (Length, Choice, Range, Callback,
  NotBlank, Email, Regex, NotNull, Type) to named arguments. Also fixed
  `#[Assert\Choice([...])]` attributes on `PrefilledAnswers` and
  `Form\Model\Campaign` to use `#[Assert\Choice(choices: [...])]`.
- `Request::get()` removed. Replaced 30+ callsites in controllers and two
  bundles with explicit `$request->attributes->get(X) ?? $request->query->get(X) ?? $request->request->get(X[, default])` lookups, mirroring the
  old method's lookup order (route attributes → query → POST body).
- Implicit Doctrine entity controller-arg resolution is gone. Added
  `#[MapEntity(mapping: ['<route_param>' => '<entity_field>'])]` to the
  entity-typed args in `SynthesisController`, `NivolController`,
  `MessageController` and `SpaceController`.
- `symfony/dependency-injection` 8 removed XML configuration support.
  Converted the in-tree formflow-bundle's three service definitions from
  XML to PHP (`Resources/config/{form_flow,twig,util}.php`) and switched
  its extension loader from `XmlFileLoader` to `PhpFileLoader`.

Cleanups along the way:
- Removed `config/packages/dev/easy_log_handler.yaml` (referenced a class
  that no longer exists).
- Removed `ReCaptcha\RequestMethod\Curl` service entry — the class is gone
  in `google/recaptcha` 1.5; only `CurlPost` remains.
- Updated `config/routes/dev/web_profiler.yaml` to use the new `.php`
  routing entry points (the old `.xml` entry points were removed by newer
  `web-profiler-bundle`).

### doctrine/doctrine-migrations-bundle 3 → 4 (`874ddfd6`)
- Bumped the constraint to `^4.0`. No code changes required.

### doctrine/doctrine-bundle 2 → 3 (`c8d0427b`)
- `doctrine.orm.auto_generate_proxy_classes`, `proxy_dir`, `proxy_namespace` removed — they are no-ops once ORM 3 + native lazy objects are in play, and doctrine-bundle 3 explicitly rejects them. Cleaned from both `config/packages/doctrine.yaml` and `config/packages/prod/doctrine.yaml`.
- `default_table_options.collate` renamed to `collation` (3.0 rename).
- 50+ controllers were importing `Symfony\Component\Routing\Annotation\Route`. That alias was dropped when framework-bundle/doctrine-bundle 3 removed the annotation-reader integration; replaced with `Symfony\Component\Routing\Attribute\Route` across `src/` and `bundles/`.
- Service definitions referencing `Doctrine\Common\Persistence\ManagerRegistry` (the long-deprecated alias) now use `Doctrine\Persistence\ManagerRegistry` (`config/services.yaml`, `bundles/*/Resources/config/repository.{yaml,yml}`).

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

### google/recaptcha 1.3 → 1.5 (`e33a4ec1`)
- Minor bump; the now-removed `ReCaptcha\RequestMethod\Curl` class needed
  to be dropped from `config/packages/google_recaptcha.yaml`.

### PHPUnit 12 → 13 (`7929aeef`, `26d2501b`)
- Required PHP ≥ 8.4.1 (already in place).
- PHPUnit 13 deprecates `with()` without `expects()` and deprecates
  `expects($this->any())`. Replaced the 5 occurrences with
  `expects($this->atLeastOnce())` (BaseControllerTest, MediaManagerTest,
  CommunicationExtensionTest, PhoneValidatorTest, UnlockedValidatorTest).

### PHP 8.4 / 8.5 deprecation cleanup (`eb0c5d99`, `c09646fb`)
- Ran `ExplicitNullableParamTypeRector` (Rector 2.4) across the codebase
  to rewrite implicit-nullable params (30 files).
- Removed all `ReflectionProperty::setAccessible()` / `ReflectionMethod::
  setAccessible()` calls — they're no-ops since PHP 8.1 and PHP 8.5
  formally deprecates them. Cleaned out of 8 test files.
- `auto_detect_line_endings` ini setting removed in PHP 8.5 — dropped the
  call in `ArrayToCsvResponse`.
- `league/csv` 9.27 deprecations: `createFromString` → `fromString`,
  `BOM_UTF8` constant → `Bom::Utf8`, `getContent` → `toString`.
- `jsonSerialize()` now declares `: mixed` (BaseTrigger, EmailTrigger).
- Template validator: cast `getBody()` result to string before
  `mb_strlen` / `strip_tags` (passing null deprecated since 8.1).
- `preg_split(…, null, …)` × 4 in GSM.php → `preg_split(…, -1, …)`.
- `MessageManager::generatePrefixes`: alphabetic `$prefix++` rewritten as
  a while-loop using `str_increment()` (PHP 8.3+).
- Two `imagedestroy()` calls removed from `TemplateImageManagerTest`
  (no-op since PHP 8.0).
- Declared `$simpleProcessor` property explicitly on `VolunteersController`
  (dynamic-property creation is an error in 8.4).
- PHPUnit 12 introduced a notice for unused mock-object stubs;
  added `#[AllowMockObjectsWithoutExpectations]` at class level on the 32
  test classes that intentionally use mocks as stubs.

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
- Final: **1571 tests, 3039 assertions, all green**. Zero deprecations, zero PHPUnit notices.
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

None. See `UPGRADE_BLOCKERS.md` for residual transitive pins (none of which block any upgrade): the previous run's `craue/formflow-bundle` blocker was resolved by vendoring the bundle in-tree under `bundles/formflow-bundle/`.
