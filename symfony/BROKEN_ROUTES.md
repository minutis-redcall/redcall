# Broken Routes

Catalogue of routes whose integration test ended up `markTestIncomplete()`'d
because the route — not the test — is misbehaving. Each entry names the file
and line the symptom surfaces at, the user-visible behaviour, the most likely
root cause traced one or two indirections back, and the test that locks it in.

This file is written *during* the route-coverage pass. Fixes are a separate
pass on top of this branch.

## GET /campaign/operations

- **Controller**: `App\Controller\CampaignController::searchForOperation`
- **Expected**: 200 `application/json` with `{operations: [...]}` once an accessible structure's external id is passed.
- **Actual in test env**: 500 `InvalidArgumentException: URI must be a string or UriInterface` from `vendor/guzzlehttp/psr7/src/Utils.php:475`, raised inside `App\Provider\Minutis\Minutis::getClient()` when it constructs `new Client([base_uri => $env['MINUTIS_HOST']])` and `MINUTIS_HOST` is unset.
- **Root cause (one indirection in)**: `config/services_dev.yaml` aliases `App\Provider\Minutis\MinutisProvider` → `Bundles\SandboxBundle\Provider\FakeMinutisProvider`. `config/services_test.yaml` does **not** mirror this alias — so the real Minutis client runs in the test env. The bug is **test-env wiring**, not route logic; the route itself is fine when the alias is in place.
- **Decision point**: trivial — copy the alias from `services_dev.yaml` to `services_test.yaml`. Note: any other route that touches Minutis from a logged-in path (Volunteer→Minutis lookups, campaign reports rendering an operation link) will likely hit the same wall once exercised.
- **Test**: `tests/Controller/CampaignControllerTest::testSearchForOperationReturnsJsonForAccessibleStructure` — `markTestIncomplete`.

