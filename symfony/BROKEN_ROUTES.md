# Broken Routes

Catalogue of routes whose integration test ended up `markTestIncomplete()`'d
because the route — not the test — is misbehaving. Each entry names the file
and line the symptom surfaces at, the user-visible behaviour, the most likely
root cause traced one or two indirections back, and the test that locks it in.

This file was written during the route-coverage pass. Initial entries have
since been **resolved** as a follow-up; their final status is in each entry.

## ~~GET /campaign/operations~~ — RESOLVED

- **Controller**: `App\Controller\CampaignController::searchForOperation`
- **Was**: 500 `InvalidArgumentException: URI must be a string or UriInterface` from `vendor/guzzlehttp/psr7/src/Utils.php:475`, raised inside `App\Provider\Minutis\Minutis::getClient()` when constructing `new Client([base_uri => $env['MINUTIS_HOST']])` and `MINUTIS_HOST` was unset.
- **Root cause**: `config/services_dev.yaml` aliased `App\Provider\Minutis\MinutisProvider` → `Bundles\SandboxBundle\Provider\FakeMinutisProvider`. `config/services_test.yaml` did not mirror the alias, so the real Minutis client ran in test.
- **Fix applied**: copied the alias into `config/services_test.yaml`. `tests/Controller/CampaignControllerTest::testSearchForOperationReturnsJsonForAccessibleStructure` is now a real test (the structure uses a numeric external id because `Bundles\SandboxBundle\Repository\FakeOperationRepository::search()` is typed `int $structureExternalId` — this is a separate sandbox quirk worth noting if anyone exercises Minutis flows with non-numeric NIVOL-style ids).

## ~~GET /admin/maintenance/pegass-files and /admin/maintenance/annuaire-national~~ — RESOLVED

- **Controller**: `App\Controller\Admin\MaintenanceController::pegassFiles` and `::annuaireNational`.
- **Was**: 500 (pegass-files) / 200 with a logged Google-auth error (annuaire-national). Both fire Google Cloud Tasks via `Bundles\GoogleTaskBundle\Service\TaskSender::fire()`. In non-prod envs the sender executes the task inline; the task hits `Google\Client` / `Google\Service\Sheets` and crashes for lack of service-account credentials.
- **Fix applied**: introduced `Bundles\SandboxBundle\Service\NullTaskSender` (a TaskSender subclass that records dispatches without executing them) and aliased the real sender to it in `config/services_test.yaml`, mirroring how `FakeEmailProvider` already replaces `EmailProvider`. Both tests in `tests/Controller/Admin/AdminSmokeTest` (`testMaintenancePegassFilesOk`, `testMaintenanceAnnuaireNationalOk`) are now real.

## Unreachable in test env

These routes exist in the dev catalogue but are not loaded in the test environment, so they cannot be exercised via HTTP:

- **All `/sandbox/*` routes (18 routes)** — registered via `config/routes/dev/sandbox.yaml`, which is loaded only in `dev`. `config/routes/` has no `test/` counterpart. Confirmed via `APP_ENV=test php bin/console debug:router`: 0 sandbox routes. Their underlying managers (FakeEmailManager, FakeCallManager, FakeSmsManager, etc.) **are** exercised indirectly by other tests in the suite — `services_test.yaml` aliases `EmailProvider` → `FakeEmailProvider`, so every email-sending test goes through the fake. Only the HTTP-exposed admin/debug views of the fake state are unreachable.

- **Twilio webhook routes (5 routes)** — `/twilio/incoming-call`, `/twilio/outgoing-call/{uuid}`, `/twilio/answering-machine/{uuid}`, `/twilio/incoming-message`, `/twilio/message-status/{uuid}` all validate the `X-Twilio-Signature` HMAC against `TWILIO_AUTH_TOKEN`. In test env this token is unset, so a valid signature cannot be forged. Tested only the signature-rejection (400) path. Happy-path coverage would require either a fake signature validator (Twilio SDK doesn't expose one) or a `services_test.yaml` override of `Bundles\TwilioBundle\Controller\BaseController::validateRequestSignature` — neither in scope.
