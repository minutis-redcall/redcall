# Route Coverage Plan

Mission: every application route gets at least one integration test that proves
it behaves as the controller intends.

## Inventory

- **Total routes from `debug:router`**: 199
- **Excluded (infrastructure)**: 5 (`_wdt`, `_profiler*`, `_error*`, `_preview_error`)
- **Routes in scope**: **194** across **49 controllers**
- **Tests at baseline**: 1597 tests / 3117 assertions, all green
- **Branch**: `chore/dependency-upgrade-20260514` — commits stack on top

## Test conventions

- All controller tests live under `tests/Controller/{...}`. Subdirs `Admin/` and `Management/` mirror controller namespaces.
- Base class for HTTP tests: `App\Tests\Base\BaseWebTestCase` (extends `WebTestCase`, exposes `login($client, $user)` and `get($id)`).
- Fixture factory: `App\Tests\Fixtures\DataFixtures`, instantiated with EM + password hasher; scenario presets (`createUserWithStructure`, `createUserWithVolunteerAndStructure`, `createFullCampaign`, …) and granular factories.
- DAMA `DoctrineTestBundle` wraps each test in a transaction (confirmed in `config/bundles.php` + `phpunit.xml.dist`).
- CSRF protection is disabled in the test env; tokens can still be generated via `security.csrf.token_manager` when a route compares the token string from the URL.
- The Email and Storage providers are replaced by fakes in `config/services_test.yaml`.

## Status legend

- [ ] not yet covered
- [x] covered (happy / auth / 404 / form-validation, where applicable)
- [~] partial coverage (happy path only, or happy + auth)
- [!] broken — see BROKEN_ROUTES.md

## Out of scope (will document if untestable in test env)

- Twilio webhooks (`/twilio/*`) — depend on Twilio signatures; will hit them with crafted payloads where the controller doesn't validate the signature, otherwise document.
- Google Cloud Tasks receiver — similar.
- `/_ah/{start,stop,warmup}` — GAE lifecycle.
- `/cron/{key}` — runs `CronController::actions()`, side-effect heavy.

### Admin\AnswerAnalysisController (1)
- [ ] ANY /admin/answer-analysis — index

### Admin\BadgeController (5)
- [ ] ANY /admin/badges — index
- [ ] ANY /admin/badges/manage-{id} — manage
- [ ] ANY /admin/badges/toggle-visibility-{id}/{token} — toggleVisibility
- [ ] ANY /admin/badges/toggle-lock-{id}/{token} — toggleLock
- [ ] ANY /admin/badges/toggle-enable-{id}/{token} — toggleEnable

### Admin\CampaignController (1)
- [ ] ANY /admin/campaign — index

### Admin\CategoryController (9)
- [ ] ANY /admin/categories/ — listCategories
- [ ] ANY /admin/categories/form-for-{id} — categoryForm
- [ ] ANY /admin/categories/delete-category-{id}/{token} — deleteCategory
- [ ] ANY /admin/categories/lock-unlock-{id}/{token} — toggleLockCategory
- [ ] ANY /admin/categories/enable-disable-{id}/{token} — toggleEnableCategory
- [ ] ANY /admin/categories/list-badges-in-category-{id} — listBadgeInCategory
- [ ] ANY /admin/categories/add-badge-in-category-{id}/{token} — addBadgeInCategory
- [ ] ANY /admin/categories/refresh-category-category-{id} — refreshCategoryCard
- [ ] ANY /admin/categories/delete-badge-{badgeId}-in-category-{categoryId}/{token} — deleteBadgeInCategory

### Admin\GdprController (1)
- [ ] ANY /admin/gdpr — index

### Admin\HomeController (1)
- [ ] ANY /admin/ — indexAction

### Admin\MaintenanceController (8)
- [ ] ANY /admin/maintenance/ — index
- [ ] ANY /admin/maintenance/refresh — refresh
- [ ] ANY /admin/maintenance/pegass-files — pegassFiles
- [ ] ANY /admin/maintenance/annuaire-national — annuaireNational
- [ ] ANY /admin/maintenance/search — search
- [ ] ANY /admin/maintenance/search/change-nivol — searchChangeNivol
- [ ] ANY /admin/maintenance/search/change-expression — searchChangeExpression
- [ ] ANY /admin/maintenance/message — message

### Admin\PegassController (15)
- [ ] ANY /admin/pegass — index
- [ ] ANY /admin/pegass/list-users — userList
- [ ] ANY /admin/pegass/update/{csrf}/{id} — updateBoundVolunteer
- [ ] ANY /admin/pegass/update-structures/{id} — updateStructures
- [ ] ANY /admin/pegass/add-structure/{csrf}/{id} — addStructure
- [ ] ANY /admin/pegass/create-user — createUser
- [ ] ANY /admin/pegass/toggle-verify/{csrf}/{id} — toggleVerifyAction
- [ ] ANY /admin/pegass/toggle-trust/{csrf}/{id} — toggleTrustAction
- [ ] ANY /admin/pegass/toggle-admin/{csrf}/{id} — toggleAdminAction
- [ ] ANY /admin/pegass/toggle-lock/{csrf}/{id} — toggleLockAction
- [ ] ANY /admin/pegass/toggle-root/{csrf}/{id} — toggleRootAction
- [ ] ANY /admin/pegass/delete/{csrf}/{id} — deleteAction
- [ ] ANY /admin/pegass/administrators — administrators
- [ ] ANY /admin/pegass/revoke-admin/{csrf}/{id} — revokeAdmin
- [ ] ANY /admin/pegass/rtmr — rtmr

### Admin\PrefilledAnswersController (3)
- [ ] ANY /admin/reponses-pre-remplies/ — listAction
- [ ] ANY /admin/reponses-pre-remplies/editer/{pfaId} — editorAction
- [ ] ANY /admin/reponses-pre-remplies/supprimer/{csrf}/{pfaId} — deleteAction

### Admin\StatsController (3)
- [ ] ANY /admin/stats/ — index
- [ ] ANY /admin/stats/general — general
- [ ] ANY /admin/stats/structure — structure

### AudienceController (7)
- [ ] ANY /audience/search-volunteer — searchVolunteer
- [ ] ANY /audience/search-badge — searchBadge
- [ ] ANY /audience/numbers — numbers
- [ ] ANY /audience/problems — problems
- [ ] ANY /audience/selection — selection
- [ ] ANY /audience/home — home
- [ ] ANY /audience/resolve — resolve

### CampaignController (11)
- [ ] ANY /campaign/list — listCampaigns
- [ ] ANY /campaign/new/{type} — createCampaign
- [ ] ANY /campaign/{id}/audience — audience
- [ ] ANY /campaign/{id}/close/{csrf} — closeCampaign
- [ ] ANY /campaign/{id}/open/{csrf} — openCampaign
- [ ] ANY /campaign/{id}/keep/{csrf} — keepCampaign
- [ ] ANY /campaign/{id}/change-color/{color}/{csrf} — changeColor
- [ ] ANY /campaign/{id}/rename — rename
- [ ] ANY /campaign/{id}/notes — notes
- [ ] ANY /campaign/{id}/report — report
- [ ] ANY /campaign/operations — searchForOperation

### CampaignGroupController (2)
- [ ] POST /campaign/{id}/group/rename/{index} — rename
- [ ] POST /campaign/{id}/group/volunteer/{volunteerId}/toggle/{index} — toggle

### CommunicationController (13)
- [ ] ANY /campaign/{id} — indexAction
- [ ] ANY /campaign/goto/{id} — gotoAction
- [ ] ANY /campaign/{id}/short-polling — shortPolling
- [ ] ANY /campaign/{id}/long-polling — longPolling
- [ ] ANY /campaign/{id}/add-communication/{type} — addCommunicationAction
- [ ] ANY /campaign/{id}/new-communication/{type}/{key} — newCommunicationAction
- [ ] ANY /campaign/preview/{type} — previewCommunicationAction
- [ ] ANY /campaign/play — playCommunication
- [ ] ANY /campaign/answers — answersAction
- [ ] ANY /campaign/answer/{csrf}/{id} — changeAnswerAction
- [ ] ANY /campaign/{campaignId}/rename-communication/{communicationId} — rename
- [ ] ANY /campaign/{campaign}/communication/{communication}/relaunch — relaunchCommunication
- [ ] ANY /campaign/{campaignId}/provider-information/{messageId} — getProviderInformation

### CostsController (1)
- [ ] ANY /costs/ — home

### CronController (1)
- [ ] ANY /cron/{key} — run

### DeployController (1)
- [ ] ANY /deploy — check

### ExportController (2)
- [ ] POST /export/{id}/csv — csvAction
- [ ] ANY /export/{id}/pdf — pdfAction

### FavoriteBadgeController (2)
- [ ] ANY /favorite-badge — index
- [ ] ANY /favorite-badge/delete/{csrf}/{id} — delete

### GoogleController (3)
- [ ] ANY /_ah/start — start
- [ ] ANY /_ah/stop — stop
- [ ] ANY /_ah/warmup — warmup

### HomeController (4)
- [ ] ANY / — home
- [ ] ANY /locale/{locale} — locale
- [ ] ANY /auth — auth
- [ ] ANY /go-to-space — space

### Management\HomeController (1)
- [ ] ANY /management/ — indexAction

### Management\Structure\PrefilledAnswersController (4)
- [ ] ANY /management/structures/{structure}/prefilled-answers/ — listPrefilledAnswers
- [ ] ANY /management/structures/{structure}/prefilled-answers/{prefilledAnswers}/editor — editorPrefilledAnswers
- [ ] ANY /management/structures/{structure}/prefilled-answers/new — editorPrefilledAnswers
- [ ] ANY /management/structures/{structure}/prefilled-answers/{prefilledAnswers}/delete — deleteAction

### Management\Structure\StructuresController (7)
- [ ] ANY /management/structures/{enabled} — listAction
- [ ] ANY /management/structures/create/{id} — createStructure
- [ ] ANY /management/structures/pegass/{id} — pegass
- [ ] ANY /management/structures/export/{id} — export
- [ ] ANY /management/structures/list-users — listUsers
- [ ] ANY /management/structures/toggle-lock-{id}/{token} — toggleLock
- [ ] ANY /management/structures/toggle-enable-{id}/{token} — toggleEnable

### Management\Structure\TemplateController (5)
- [ ] ANY /management/structures/{structure}/template — list
- [ ] ANY /management/structures/{structure}/template/new — editor
- [ ] ANY /management/structures/{structure}/template/{template}/edit — editor
- [ ] ANY /management/structures/{structure}/template/{template}/{csrf}/delete — delete
- [ ] ANY /management/structures/{structure}/template/{template}/{csrf}/move/{newPriority} — move

### Management\Structure\VolunteerListController (6)
- [ ] ANY /management/structures/volunteer-lists/ — homeAction
- [ ] ANY /management/structures/volunteer-lists/{structureId}/ — indexAction
- [ ] ANY /management/structures/volunteer-lists/{structureId}/create/{volunteerListId} — createAction
- [ ] ANY /management/structures/volunteer-lists/{structureId}/cards/{volunteerListId} — cardsAction
- [ ] ANY /management/structures/volunteer-lists/{structureId}/remove-one-volunteer/{csrf}/{volunteerListId}/{volunteerId} — deleteOneVolunteerAction
- [ ] ANY /management/structures/volunteer-lists/{structureId}/remove/{csrf}/{volunteerListId} — deleteAction

### Management\Volunteer\VolunteersController (13)
- [ ] ANY /management/volunteers/{id} — listAction
- [ ] ANY /management/volunteers/manual-update/{id} — manualUpdateAction
- [ ] ANY /management/volunteers/create — createAction
- [ ] ANY /management/volunteers/pegass/{id} — pegass
- [ ] ANY /management/volunteers/pegass-reset/{csrf}/{id} — pegassReset
- [ ] ANY /management/volunteers/edit-structures/{id} — editStructures
- [ ] ANY /management/volunteers/remove-all-structures/{csrf}/{id} — removeAllStructures
- [ ] ANY /management/volunteers/add-structure/{csrf}/{id} — addStructure
- [ ] ANY /management/volunteers/delete-structure/{csrf}/{volunteerId}/{structureId} — deleteStructure
- [ ] ANY /management/volunteers/delete/{volunteerId}/{answerId} — deleteAction
- [ ] ANY /management/volunteers/list-user-structures — listUserStructures
- [ ] ANY /management/volunteers/toggle-lock-{id}/{token} — toggleLock
- [ ] ANY /management/volunteers/toggle-enable-{id}/{token} — toggleEnable

### MessageController (4)
- [ ] GET|POST /msg/{code} — openAction
- [ ] GET|POST /msg/optout/{code} — optoutAction
- [ ] GET /msg/{code}/{signature}/{action} — actionAction
- [ ] GET /msg/{code}/annuler/{signature}/{action} — cancelAction

### NivolController (2)
- [ ] ANY /nivol — login
- [ ] ANY /code/{uuid} — code

### OAuth\GoogleConnectController (2)
- [ ] ANY /google-connect — connect
- [ ] ANY /google-verify — verify

### SpaceController (9)
- [ ] ANY /space/{sessionId}/ — home
- [ ] ANY /space/{sessionId}/infos — infos
- [ ] ANY /space/{sessionId}/phone — phone
- [ ] ANY /space/{sessionId}/email — email
- [ ] ANY /space/{sessionId}/enabled — enabled
- [ ] ANY /space/{sessionId}/consult-data — consultData
- [ ] ANY /space/{sessionId}/download-data — downloadData
- [ ] ANY /space/{sessionId}/delete-data — deleteData
- [ ] ANY /space/{sessionId}/logout — logout

### SynthesisController (2)
- [ ] ANY /syn/{code} — index
- [ ] ANY /syn/{code}/poll — poll

### TaskController (1)
- [ ] ANY /task/webhook — webhook

### WidgetController (5)
- [ ] ANY /widget/template-data — templateData
- [ ] ANY /widget/volunteer-search/{searchAll} — volunteerSearch
- [ ] ANY /widget/structure-search/{searchAll} — structureSearch
- [ ] ANY /widget/badge-search — badgeSearch
- [ ] ANY /widget/category-search — categorySearch

### PasswordLoginBundle\Controller\AdminController (7)
- [ ] ANY /admin/users/ — listAction
- [ ] ANY /admin/users/toggle-verify/{username}/{csrf} — toggleVerify
- [ ] ANY /admin/users/toggle-trust/{username}/{csrf} — toggleTrust
- [ ] ANY /admin/users/toggle-admin/{username}/{csrf} — toggleAdmin
- [ ] ANY /admin/users/delete/{username}/{csrf} — delete
- [ ] ANY /admin/users/profile/{username} — profile
- [ ] ANY /admin/users/reset-password/{username}/{csrf} — resetPassword

### PasswordLoginBundle\Controller\SecurityController (8)
- [ ] ANY /register — registerAction
- [ ] ANY /verify-email/{uuid} — verifyEmailAction
- [ ] ANY /connect/{nivol} — connectAction
- [ ] ANY /logout — logoutAction
- [ ] ANY /profile — profileAction
- [ ] ANY /guest — notTrustedAction
- [ ] ANY /forgot-password — forgotPasswordAction
- [ ] ANY /change-password/{uuid} — changePasswordAction

### TwilioBundle\Controller\CallController (3)
- [ ] ANY /twilio/incoming-call — incoming
- [ ] ANY /twilio/outgoing-call/{uuid} — outgoing
- [ ] ANY /twilio/answering-machine/{uuid} — answeringMachine

### TwilioBundle\Controller\MessageController (1)
- [ ] ANY /twilio/incoming-message — incoming

### TwilioBundle\Controller\StatusController (1)
- [ ] ANY /twilio/message-status/{uuid} — messageStatus

### GoogleTaskBundle\Controller\TaskController (1)
- [ ] ANY /cloud-task — receive

### SandboxBundle\Controller\AnonymizeController (1)
- [ ] ANY /sandbox/anonymize/{csrf} — anonymizeAction

### SandboxBundle\Controller\FakeCallController (3)
- [ ] ANY /sandbox/fake-call/ — listAction
- [ ] ANY /sandbox/fake-call/clear/{csrf} — clearAction
- [ ] ANY /sandbox/fake-call/read/{e164}/{campaignId} — readAction

### SandboxBundle\Controller\FakeEmailController (3)
- [ ] ANY /sandbox/fake-email/ — listAction
- [ ] ANY /sandbox/fake-email/clear/{csrf} — clearAction
- [ ] ANY /sandbox/fake-email/read/{email}/{campaignId} — readAction

### SandboxBundle\Controller\FakeMinutisController (2)
- [ ] ANY /sandbox/fake-minutis/{id} — listAction
- [ ] ANY /sandbox/fake-minutis/clear/{token} — clear

### SandboxBundle\Controller\FakeSmsController (5)
- [ ] ANY /sandbox/fake-sms/ — listAction
- [ ] ANY /sandbox/fake-sms/clear/{csrf} — clearAction
- [ ] ANY /sandbox/fake-sms/thread/{e164}/{campaignId} — threadAction
- [ ] POST /sandbox/fake-sms/send/{e164}/{csrf} — sendAction
- [ ] ANY /sandbox/fake-sms/poll/{phoneNumber} — pollAction

### SandboxBundle\Controller\FakeStorageController (1)
- [ ] ANY /sandbox/fake-storage/{filename} — access

### SandboxBundle\Controller\FixturesController (1)
- [ ] ANY /sandbox/fixtures/ — index

### SandboxBundle\Controller\HomeController (1)
- [ ] ANY /sandbox/ — indexAction

### SandboxBundle\Controller\SpinnerController (1)
- [ ] ANY /sandbox/spinner — index

