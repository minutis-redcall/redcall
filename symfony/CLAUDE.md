# RedCall - Symfony Application

## Project Overview
RedCall is a volunteer management and emergency communication platform for the French Red Cross.
It manages volunteers, structures (organizational units), campaigns (mass communication via SMS/call/email), and integrates with external systems (Pegass, Twilio, Sendgrid, Google Cloud).

## Tech Stack
- **PHP:** >=7.3 (no native enums, no union types, no named arguments)
- **Framework:** Symfony 5.x with annotation-based routing (`@Route` on controllers)
- **ORM:** Doctrine 2.11+ with MySQL 5.7, annotation-based mappings (`@ORM\Entity`, etc.)
- **Templates:** Twig 3.x (server-side rendering, no SPA)
- **Frontend:** jQuery 3, Bootstrap 3, Webpack Encore
- **Auth:** Multi-authenticator guard system (Minutis SSO, form login, NIVOL code, Google OAuth)
- **Testing:** PHPUnit 9.6 + symfony/browser-kit + DAMA DoctrineTestBundle (transaction wrapping)
- **Forms:** CraueFormFlow for multi-step campaign creation
- **Enums:** MyCLabs\Enum (NOT native PHP enums)
- **Pagination:** babdev/pagerfanta-bundle
- **Background Jobs:** Google Cloud Tasks (custom GoogleTaskBundle)

## Architecture

### Layer Pattern
```
Controller → Manager → Repository → Entity
     ↓           ↓
  Form/Flow    Provider (external APIs)
     ↓           ↓
  Form/Type    Communication/Sender
```

- **Controllers** handle HTTP, validate input, call managers, render templates
- **Managers** (32 classes) are the business logic layer — the heart of the app
- **Repositories** encapsulate database queries
- **Providers** abstract external services (Twilio, Sendgrid, Google Cloud Storage)
- **Communication/Sender** orchestrates message delivery across SMS/Call/Email channels

### Directory Structure
```
symfony/
├── src/
│   ├── Base/                       # Abstract base classes (4 files)
│   │   ├── BaseController.php      # orderBy(), createNamedFormBuilder(), validateCsrfOrThrowNotFoundException()
│   │   ├── BaseCommand.php         # Shared console command base
│   │   ├── BaseRepository.php      # save($entity), remove($entity) — both call flush()
│   │   └── BaseService.php         # ServiceSubscriberInterface for lazy-loaded deps via $this->get()
│   ├── Controller/                 # Route handlers, annotation-based routing
│   │   ├── Admin/                  # ROLE_ADMIN required (10 controllers)
│   │   ├── Management/             # ROLE_TRUSTED required
│   │   │   ├── Structure/          # Template, PrefilledAnswers, VolunteerList controllers
│   │   │   └── Volunteer/          # Volunteer CRUD
│   │   └── OAuth/                  # Google Connect flow
│   │   # Root-level: Campaign, Communication, Audience, Message, Space, etc.
│   ├── Entity/                     # 28 Doctrine entities with annotation mappings
│   ├── Enum/                       # MyCLabs\Enum classes (Type, Group, Resource, Stop, Crud, ResourceOwnership)
│   ├── Form/
│   │   ├── Flow/                   # CraueFormFlow multi-step forms (Campaign, SMS/Call/Email triggers)
│   │   ├── Model/                  # Non-entity form data classes (BaseTrigger, SmsTrigger, Campaign, etc.)
│   │   ├── Type/                   # 31 Symfony form types
│   │   └── Extension/              # Form type extensions
│   ├── Manager/                    # 32 business logic services — core service layer
│   ├── Repository/                 # 27 Doctrine repositories with custom query methods
│   ├── Security/
│   │   ├── Authenticator/          # 3 guard authenticators (Minutis, GoogleConnect, Nivol)
│   │   ├── Voter/                  # 8 voters (Campaign, Communication, Structure, Volunteer, Badge, Category, User, VolunteerSession)
│   │   └── Helper/Security.php     # Custom security helper
│   ├── Services/                   # Domain services (MessageFormatter, Mjml, TextToSpeech, VoiceCalls, Phrase)
│   │   └── InstancesNationales/    # National directory sync
│   ├── Communication/
│   │   ├── Processor/              # Message dispatch strategies (SimpleProcessor, ExecProcessor, TaskProcessor)
│   │   └── Sender.php              # Orchestrates sending SMS/Call/Email via providers
│   ├── Provider/                   # External service adapters (strategy pattern)
│   │   ├── Call/                   # Twilio voice calls
│   │   ├── Email/                  # Sendgrid + Symfony mailer
│   │   ├── SMS/                    # Twilio SMS (with task-based status variant)
│   │   ├── Storage/                # Google Cloud Storage
│   │   ├── Minutis/                # Minutis Red Cross internal API
│   │   └── OAuth/GoogleConnect/    # Google OAuth
│   ├── Task/                       # Background job handlers implementing TaskInterface
│   │   ├── AbstractSendMessageTask.php  # Base for Send*Task
│   │   ├── Send{Sms,Call,Email}Task.php # Per-channel message sending
│   │   ├── SendCommunicationTask.php    # Spawns per-message tasks
│   │   ├── Pegass{CreateChunks,UpdateChunk}.php  # Pegass sync
│   │   └── Sync*.php               # Data sync tasks
│   ├── Command/                    # 16 console commands
│   ├── Model/                      # Value objects (Classification, LanguageConfig, PhoneConfig)
│   │   └── InstancesNationales/    # DTOs for national directory imports
│   ├── Component/HttpFoundation/   # Custom response types (ArrayToCsvResponse, MpdfResponse, DownloadResponse, NoContentResponse)
│   ├── Validator/Constraints/      # Custom validators (Phone, Unlocked, WhitelistedRedirectUrl)
│   ├── Twig/Extension/             # Twig functions/filters
│   ├── EventSubscriber/            # 6 subscribers (Locale, Timezone, Exception, Pegass, Twilio, CommunicationActivity)
│   ├── ParamConverter/             # CsrfParamConverter, EnumParamConverter
│   ├── Logger/                     # ContextProcessor for Monolog
│   ├── Queues.php                  # Queue name constants for Google Cloud Tasks
│   └── Migrations/                 # ~233 Doctrine migration files
├── bundles/                        # Custom Symfony bundles (NOT in src/)
│   ├── password-login-bundle/      # Auth forms, AbstractUser entity, email verification, captcha
│   ├── sandbox-bundle/             # Test fakes (FakeEmailManager, FakeEmailProvider, anonymization)
│   ├── twilio-bundle/              # Twilio webhook handling at /twilio/*, TwilioStatusManager
│   ├── google-task-bundle/         # Google Cloud Tasks integration, TaskInterface contract
│   ├── settings-bundle/            # Key-value settings store
│   └── pagination-bundle/          # Pagination helpers wrapping Pagerfanta
├── templates/                      # Twig templates matching controller structure
├── tests/                          # PHPUnit tests (see Testing section below)
├── config/                         # Symfony config (services.yaml, packages/, routes/)
├── translations/                   # fr/en translation files
└── assets/                         # Frontend JS/CSS (Webpack Encore)
```

### File Naming Conventions
| Layer | Pattern | Example |
|-------|---------|---------|
| Entity | `src/Entity/{Name}.php` | `src/Entity/Volunteer.php` |
| Repository | `src/Repository/{Name}Repository.php` | `src/Repository/VolunteerRepository.php` |
| Manager | `src/Manager/{Name}Manager.php` | `src/Manager/VolunteerManager.php` |
| Controller | `src/Controller/{Area?}/{Name}Controller.php` | `src/Controller/Admin/BadgeController.php` |
| Form Type | `src/Form/Type/{Name}Type.php` | `src/Form/Type/VolunteerType.php` |
| Form Flow | `src/Form/Flow/{Name}Flow.php` | `src/Form/Flow/SmsTriggerFlow.php` |
| Form Model | `src/Form/Model/{Name}.php` | `src/Form/Model/SmsTrigger.php` |
| Task | `src/Task/{Name}.php` | `src/Task/SendSmsTask.php` |
| Command | `src/Command/{Name}Command.php` | `src/Command/ClearCampaignCommand.php` |
| Voter | `src/Security/Voter/{Name}Voter.php` | `src/Security/Voter/CampaignVoter.php` |
| Provider | `src/Provider/{Channel}/{Implementation}.php` | `src/Provider/SMS/Twilio.php` |
| Migration | `src/Migrations/Version{YYYYMMDDHHmmss}.php` | `src/Migrations/Version20260227120000.php` |

## Entities & Relationships

### Entity Graph
```
User (UUID string ID, extends AbstractUser from PasswordLoginBundle)
 ├── ↔ Volunteer (1:1, bidirectional)
 ├── ↔ Structure (M:M, with OrderBy)
 └── ↔ Badge (M:M, favoriteBadges)

Volunteer
 ├── ↔ Structure (M:M)
 ├── ↔ Badge (M:M, skills/certifications)
 ├── → Phone (M:M, cascade=all, orphanRemoval=true)
 └── → VolunteerSession (for personal space access)

Campaign
 ├── → Communication (1:M)
 ├── → Operation (1:1, optional — Minutis integration)
 └── .code = binary(8) for public URLs (/syn/{code})

Communication
 ├── → Message (1:M)
 ├── → Choice (1:M)
 ├── ↔ Report (1:1, bidirectional)
 └── → Media/TemplateImage (1:M)

Message
 ├── → Answer (1:M)
 ├── → Cost (1:M)
 └── .code = binary(8) for public URLs (/msg/{code})

Structure
 ├── → Template (1:M)
 ├── → PrefilledAnswers (1:M)
 └── → VolunteerList (1:M)

Badge → Category (M:1, self-referencing parent/children + synonym relationships)
Report → ReportRepartition (1:M, both extend AbstractReport MappedSuperclass)
Pegass — External Red Cross directory data cache with lifecycle management
```

### Entity ID Types
- **User**: string (UUID v4, from AbstractUser in password-login-bundle)
- **All other entities**: integer (auto-increment)
- **Campaign.code**, **Message.code**: binary(8) — used in public URLs, read via `stream_get_contents()`

### Doctrine Specifics
- **Mapping type:** Annotation-based only (`@ORM\Entity`, `@ORM\Column`, etc.)
- **`ChangeTrackingPolicy("DEFERRED_EXPLICIT")`** on Badge, Category, Pegass, Phone, PrefilledAnswers, Structure, Template, User, Volunteer — must explicitly call `$em->persist()` for changes to be detected
- **`@ORM\HasLifecycleCallbacks`** on Answer, Communication, Cost, DeletedVolunteer, Pegass, Phone, PrefilledAnswers, Report, User
- **`@ORM\MappedSuperclass`**: AbstractReport (shared fields: messageCount, questionCount, answerCount, exchangeCount, errorCount) — extended by Report and ReportRepartition
- **User extends AbstractUser** from password-login-bundle (provides username, password, email, roles)
- **Connection wrapper**: `facile-it/doctrine-mysql-come-back` for auto-reconnect (3 attempts) — disabled in test env
- **Naming strategy**: `doctrine.orm.naming_strategy.underscore`
- **Schema filter**: `~^(?!session)~` (excludes session table from migrations)

### Interfaces
- **LockableInterface** — User, Volunteer, Badge, Category, Structure, VolunteerGroup, DeletedVolunteer, VolunteerList (supports locked state)
- **PhoneInterface** — Phone entity

## Security Model

### Access Control (security.yaml)
| Path | Role | Purpose |
|------|------|---------|
| `/connect`, `/register`, `/forgot-password`, `/verify-email` | Anonymous | Identity management |
| `/nivol`, `/code`, `/space` | Anonymous | NIVOL code login, volunteer personal space |
| `/twilio`, `/media`, `/syn`, `/msg`, `/geo` | Anonymous | Webhooks, public message links |
| `/cron`, `/task`, `/deploy`, `/cloud-task` | Anonymous | Infrastructure endpoints |
| `/auth`, `/google-*` | Anonymous | SSO authentication |
| `/`, `/logout`, `/profile`, `/guest` | ROLE_USER | Authenticated but possibly untrusted |
| `/admin/*` | ROLE_ADMIN | Administration |
| Everything else | ROLE_TRUSTED | Campaigns, management, audience, costs |

### Role Hierarchy
`ROLE_ROOT` → `ROLE_ADMIN` → (implicit ROLE_USER via boolean flags)
Roles are computed in `User::getRoles()` based on: `isVerified`, `isTrusted`, `isAdmin`, `isRoot`

### Voters (attribute-based authorization)
| Voter | Attributes | Logic |
|-------|-----------|-------|
| CampaignVoter | CAMPAIGN_ACCESS, CAMPAIGN_OWNER | User's structures overlap with campaign's volunteer structures |
| CommunicationVoter | COMMUNICATION | Delegates to campaign access via communication's campaign |
| StructureVoter | STRUCTURE | User has the structure, or is admin |
| VolunteerVoter | VOLUNTEER | User's structures overlap with volunteer's structures |
| BadgeVoter | BADGE | Always grants to ROLE_ADMIN |
| CategoryVoter | CATEGORY | Always grants to ROLE_ADMIN |
| UserVoter | USER | Always grants to ROLE_ADMIN |
| VolunteerSessionVoter | VOLUNTEER_SESSION | Checks session ID matches DB record |

### Authentication
4 guard authenticators on the `main` firewall (entry_point: MinutisAuthenticator):
1. **MinutisAuthenticator** — External system SSO via /auth endpoint
2. **FormLoginAuthenticator** — Classic email/password from PasswordLoginBundle
3. **GoogleConnectAuthenticator** — Google OAuth flow
4. **NivolAuthenticator** — NIVOL code sent by email, entered on /code/{uuid}

## Communication System (Core Domain)

### Message Sending Flow
```
CampaignController                    # User creates campaign via CraueFormFlow
  → CommunicationManager              # Creates Communication + Messages + Choices
    → ProcessorInterface::process()    # Dispatch strategy
      ├── TaskProcessor                # Fires Google Cloud Task (production)
      ├── SimpleProcessor              # Synchronous sending (dev)
      └── ExecProcessor                # Exec-based async
        → Sender::sendCommunication()  # Iterates messages
          → Sender::sendMessage()      # Route to channel
            ├── sendSms()   → SMSProvider (Twilio) — rate: 2/sec
            ├── sendCall()  → CallProvider (Twilio) — rate: 5/sec
            └── sendEmail() → EmailProvider (Sendgrid) — rate: 10/sec
```

### Queue Names (Queues.php)
| Constant | Queue | Purpose |
|----------|-------|---------|
| `PEGASS_CREATE_CHUNKS` | pegass-create-chunks | Pegass batch sync setup |
| `PEGASS_UPDATE_CHUNK` | pegass-update-chunk | Pegass batch chunk update |
| `SYNC_WITH_PEGASS_ALL` | sync-with-pegass-all | Full Pegass refresh |
| `SYNC_WITH_PEGASS_ONE` | sync-with-pegass-one | Single entity refresh |
| `CREATE_TRIGGER` | create-trigger | Communication preparation |
| `MESSAGES_SMS` | messages-sms | SMS delivery |
| `MESSAGES_CALL` | messages-call | Voice call delivery |
| `MESSAGES_EMAIL` | messages-email | Email delivery |

### Classification System
`AudienceManager::classifyAudience()` produces a `Classification` DTO that categorizes volunteers:
- `phoneMissing`, `phoneOptout`, `phoneLandline` (SMS constraints)
- `emailMissing`, `emailOptout` (Email constraints)
- `optoutUntil` (Temporal opt-out)
- `excludedMinors` (Age-based filtering)
- `reachable` (final list after all filters)

### Cost Tracking
`CostManager` syncs costs from Twilio. `Message` entity defines cost constants:
- `SMS_COST = 0.05052`, `CALL_COST = 0.033`, `EMAIL_COST = 0.000375`

### Provider Pattern (Strategy)
Providers are swappable via `services.yaml`:
```yaml
App\Provider\SMS\SMSProvider:   class: App\Provider\SMS\TwilioWithStatusAsTask
App\Provider\Call\CallProvider: class: App\Provider\Call\Twilio
App\Provider\Email\EmailProvider: class: App\Provider\Email\Sendgrid
App\Provider\Storage\StorageProvider: class: App\Provider\Storage\GoogleCloudStorage
App\Communication\Processor\ProcessorInterface: class: '%communication.processor%'
```
In test env, `EmailProvider` → `FakeEmailProvider` (via `config/services_test.yaml`).

## Forms

### Multi-Step Forms (CraueFormFlow)
Campaign creation uses multi-step forms:
- `CampaignFlow` — Campaign setup
- `SmsTriggerFlow` / `CallTriggerFlow` / `EmailTriggerFlow` — 2-step: audience+message → operation choices

Form flows define `loadStepsConfig()` with validation groups and conditional `skip()` logic.

### Form Model DTOs
Form models in `src/Form/Model/` are non-entity classes used by CraueFormFlow:
- `Campaign` (form model, NOT the entity)
- `BaseTrigger` (abstract, implements `\JsonSerializable`)
- `SmsTrigger`, `CallTrigger`, `EmailTrigger` (extend BaseTrigger)
- `Operation`

### Data Transformers
`CallTriggerType` uses `CallbackTransformer` to strip HTML tags from message body.

## Console Commands
All in `src/Command/`, extend `BaseCommand`. Key commands:

| Command | Purpose | Trigger |
|---------|---------|---------|
| `user:cron` | User verification cycling | Cron |
| `pegass:files` | Pegass file sync | Cron |
| `clear:campaign` | Archive old campaigns | Cron |
| `clear:media` | Remove old media | Cron |
| `clear:space` | Clean volunteer sessions | Cron |
| `clear:expirable` | Expire temporary records | Cron |
| `clear:volunteer` | Archive deleted volunteers | Cron |
| `report:communication` | Generate reports | Cron |
| `send:communication` | Send queued messages | Manual/Task |
| `import:national` | National directory import | Cron |
| `twilio:price` | Update pricing | Cron |
| `create:user` | Create root user | Manual setup |

## Testing

### Test Organization
```
tests/
├── Base/
│   ├── BaseWebTestCase.php         # WebTestCase + login() helper (for integration tests)
│   ├── BaseControllerTest.php      # Unit tests for BaseController
│   └── BaseServiceTest.php         # Unit tests for BaseService
├── Fixtures/
│   └── DataFixtures.php            # Factory for ALL test entities + scenario presets
├── Controller/                     # Integration tests (HTTP requests via KernelBrowser)
│   ├── Admin/                      # Admin controller tests (6 files)
│   ├── Management/                 # Management controller tests (4 files)
│   └── *.php                       # Root controller tests (10 files)
├── Manager/                        # Manager unit/integration tests
├── Repository/                     # Repository integration tests
├── Entity/                         # Entity unit tests
├── Form/                           # Form type tests
├── Communication/                  # Sender tests
├── Services/                       # Service tests
├── Security/                       # Voter tests
├── Component/                      # Custom response tests
├── Enum/                           # Enum tests
├── EventSubscriber/                # Subscriber tests
├── Tools/                          # Utility tests
├── Twig/                           # Extension tests
├── Validator/                      # Validator tests
└── bootstrap.php                   # PHPUnit bootstrap
```

### Running Tests
```bash
# Full test cycle (drops/recreates test DB + runs all tests)
cd symfony && make test

# Individual commands
php vendor/bin/phpunit                        # All tests
php vendor/bin/phpunit tests/Controller/      # Integration tests only
php vendor/bin/phpunit tests/Manager/         # Manager tests only
php vendor/bin/phpunit --filter=testName      # Single test by name
```

### Test Database
- Test DB is created from scratch via `doctrine:schema:create` (NOT migrations)
- DAMA DoctrineTestBundle wraps each test in a transaction → auto-rollback
- Static connection caching enabled for performance
- `facile-it/doctrine-mysql-come-back` wrapper_class is **disabled** in test env (`config/packages/test/doctrine.yaml`)
- CSRF protection is **disabled** in test env (`config/packages/test/framework.yaml`)
- Email provider is **replaced** by `FakeEmailProvider` in test env (`config/services_test.yaml`)

### DataFixtures Usage

Instantiate in each test class:
```php
private function getFixtures($container) : DataFixtures
{
    return new DataFixtures(
        $container->get('doctrine.orm.entity_manager'),
        $container->get('security.password_encoder')
    );
}
```

**Scenario presets** (preferred — use these for common setups):

| Method | Returns | Use case |
|--------|---------|----------|
| `createAdminWithStructure()` | `[user, structure]` | Admin controller tests, badge/category CRUD |
| `createUserWithStructure()` | `[user, structure]` | Trusted-user pages (costs, audience) |
| `createUserWithVolunteerAndStructure()` | `[user, volunteer, structure]` | Minimum for campaign voter access |
| `createFullCampaign()` | `[user, volunteer, structure, campaign, communication, message, choices]` | Campaign/communication/message tests |
| `createAdminWithTemplate()` | `[user, structure, template]` | Template management tests |
| `createAdminWithPrefilledAnswers()` | `[user, structure, prefilledAnswers]` | Prefilled answers management |
| `createBadgeWithCategory()` | `[category, badge]` | Badge hierarchy tests |
| `createVolunteerWithSession()` | `[volunteer, session]` | GDPR / personal space tests |

**Entity factories** (for custom scenarios):
`createRawUser`, `createVolunteer`, `createStandaloneVolunteer`, `createStructure`, `assignUserToStructure`, `assignVolunteerToStructure`, `createBadge`, `createCategory`, `createCampaign`, `createCommunication`, `createChoice`, `createMessage`, `createAnswer`, `createPrefilledAnswers`, `createTemplate`, `createVolunteerList`, `createVolunteerSession`, `createPegass`, `createOperation`, `createMedia`

### Integration Test Pattern
```php
class SomeControllerTest extends BaseWebTestCase
{
    public function testSomething()
    {
        $client    = static::createClient();
        $container = $client->getContainer();
        $fixtures  = $this->getFixtures($container);

        // Setup: create entities via fixtures
        $data = $fixtures->createAdminWithStructure();
        $this->login($client, $data['user']);

        // Act: make HTTP request
        $client->request('GET', '/admin/some-page');

        // Assert
        $this->assertResponseStatusCodeSame(200);

        // For form submissions — use followRedirects for PRG pattern
        $client->followRedirects();
        $crawler = $client->request('GET', '/admin/some-form');
        $form = $crawler->selectButton('Submit')->form([...]);
        $client->submit($form);

        // After DB writes, clear EM before re-reading
        $em = $container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $em->getRepository(SomeEntity::class)->find($id);
    }
}
```

## Common Recipes

### To add a new controller endpoint:
1. Create/edit controller in `src/Controller/{Area}/` (Admin, Management, or root level)
2. Add `@Route` annotation with path and methods
3. Inject managers via constructor or method arguments
4. Return a `$this->render()` for HTML or `new JsonResponse()` for AJAX
5. For admin pages, place in `src/Controller/Admin/` (automatically requires ROLE_ADMIN via security.yaml)
6. For management pages, place in `src/Controller/Management/` (requires ROLE_TRUSTED)
7. Add Twig template in `templates/` matching the controller structure
8. If the endpoint needs authorization beyond role-based: use `$this->denyAccessUnlessGranted('ATTRIBUTE', $entity)` with an existing voter

### To add a new entity:
1. Create `src/Entity/{Name}.php` with `@ORM\Entity(repositoryClass=...)` annotation
2. Add `@ORM\Table(name="...")` with appropriate indexes and unique constraints
3. Create `src/Repository/{Name}Repository.php` extending `BaseRepository`
4. Constructor: `parent::__construct($registry, {Name}::class)`
5. If the entity needs business logic, create `src/Manager/{Name}Manager.php`
6. Generate and run a migration (see below)
7. Add a factory method to `tests/Fixtures/DataFixtures.php`

### To add a database migration:
```bash
# Auto-generate from entity diff
php bin/console doctrine:migrations:diff

# Create empty migration
php bin/console doctrine:migrations:generate

# Run pending migrations
php bin/console doctrine:migrations:migrate
```
Migrations live in `src/Migrations/` under the `DoctrineMigrations` namespace (NOT `App\Migrations` — they are not autoloaded). Each migration uses `$this->addSql()` with raw SQL.

For tests: the test DB is created from schema (not migrations), so after adding an entity you just need `make test` which recreates the schema.

### To add a new Manager:
1. Create `src/Manager/{Name}Manager.php`
2. Use constructor injection for dependencies (repositories, other managers, etc.)
3. Autowiring handles registration automatically (no services.yaml entry needed)
4. If using lazy-loaded deps, extend `BaseService` and implement `getSubscribedServices()`

### To add a new form type:
1. Create `src/Form/Type/{Name}Type.php` extending `AbstractType`
2. Implement `buildForm()` and `configureOptions()`
3. For multi-step flows, create `src/Form/Flow/{Name}Flow.php` extending `FormFlow` (CraueFormFlow)
4. For complex forms that need non-entity data, create `src/Form/Model/{Name}.php` as a DTO

### To add a new console command:
1. Create `src/Command/{Name}Command.php` extending `BaseCommand`
2. Set `$defaultName = 'namespace:action'` (e.g., `clear:something`)
3. Implement `execute(InputInterface, OutputInterface)`
4. If it should run on a schedule, add it to `CronController::actions()`

### To add a new background task:
1. Create `src/Task/{Name}.php` implementing `Bundles\GoogleTaskBundle\Contracts\TaskInterface`
2. Implement `execute(array $context)` and `getQueueName()` (return a `Queues::` constant)
3. Add queue name to `src/Queues.php` if it's a new queue
4. The `google_task` tag is auto-applied via `_instanceof` in services.yaml
5. Dispatch via `GoogleTaskSender::fire($taskName, $context)`

### To add a new security voter:
1. Create `src/Security/Voter/{Name}Voter.php` extending `Voter`
2. Implement `supports($attribute, $subject)` and `voteOnAttribute($attribute, $subject, $token)`
3. Define attribute constants (e.g., `const MY_ACCESS = 'MY_ACCESS'`)
4. Autoconfigure handles voter registration

### To add a new provider implementation:
1. Create interface in `src/Provider/{Channel}/{Interface}.php` (if new channel)
2. Create implementation in `src/Provider/{Channel}/{Implementation}.php`
3. Wire in `config/services.yaml` with `class:` pointing to implementation
4. For test env, create fake in `bundles/sandbox-bundle/` and wire in `config/services_test.yaml`

### To add a new custom bundle:
1. Create directory in `bundles/{bundle-name}/`
2. Add PSR-4 autoload entry in `composer.json`
3. Register in `config/bundles.php`
4. If it has services, register in `config/services.yaml` under its namespace

### To add a new test:
1. Create `tests/{Layer}/{Name}Test.php`
2. For controller/integration tests: extend `BaseWebTestCase`, use `$this->login()` and `DataFixtures`
3. For unit tests: extend `PHPUnit\Framework\TestCase`
4. For kernel-dependent tests: extend `Symfony\Bundle\FrameworkBundle\Test\KernelTestCase`
5. Add a fixture factory in `DataFixtures.php` if testing a new entity
6. Add a scenario preset in `DataFixtures.php` if the setup is reusable

## External Integrations
| System | Purpose | Bundle/Provider |
|--------|---------|-----------------|
| **Pegass** | Red Cross volunteer directory sync | PegassManager + Task classes |
| **Twilio** | SMS + voice calls | twilio-bundle (webhooks) + Provider/SMS + Provider/Call |
| **Sendgrid** | Email delivery | Provider/Email/Sendgrid |
| **Google Cloud Tasks** | Background job dispatch | google-task-bundle |
| **Google Cloud Storage** | Media file storage | Provider/Storage/GoogleCloudStorage |
| **Google Cloud TTS** | Text-to-speech for voice calls | Services/TextToSpeech |
| **Minutis** | Red Cross internal platform SSO | Provider/Minutis + MinutisAuthenticator |
| **Google OAuth** | Alternative login method | Provider/OAuth/GoogleConnect |

## Known Gotchas & Pitfalls

### MappedSuperclass EM Registration
`AbstractReport` is a `@ORM\MappedSuperclass`. Its concrete implementations (Report, ReportRepartition) are the actual entities. You cannot query `AbstractReport` directly — always query Report or ReportRepartition.

### DEFERRED_EXPLICIT Change Tracking
9 entities use `DEFERRED_EXPLICIT` change tracking. If you modify one of these entities, you **must** call `$em->persist($entity)` explicitly before flush, or the changes will be silently ignored. This is a common source of "my changes aren't saving" bugs.

### Binary Fields (Campaign.code, Message.code)
These are `binary(8)` columns. When reading them, the getter may return a stream resource instead of a string. The entities handle this with `stream_get_contents()`, but be aware if working with raw query results.

### Entity Manager After Writes
After writing to the database in tests (or anywhere with DAMA's static connection), call `$em->clear()` before re-reading to get fresh data from the database rather than cached identity map.

### User ID is a UUID String
Unlike all other entities (integer auto-increment), `User.id` is a UUID v4 string from AbstractUser. Don't assume integer IDs in queries involving User.

### PrefilledAnswers Comma Storage
`PrefilledAnswers` stores answers in a `simple_array` Doctrine type (comma-separated in DB). The entity uses a replacement trick for answers containing commas.

### Phone Number Handling
Phone entity requires `giggsey/libphonenumber-for-php` for parsing/formatting. Always use `PhoneManager` for phone operations, not raw string manipulation.

### CraueFormFlow in Tests
Campaign creation uses multi-step CraueFormFlow. It cannot be tested with simple form submission — you need to handle step-by-step progression or test the managers directly.

### CSRF in Tests
CSRF is disabled in test env, BUT if you need to test CSRF-protected actions (like delete links with CSRF tokens in the URL), generate tokens via:
```php
$tokenManager = $container->get('security.csrf.token_manager');
$token = $tokenManager->getToken('token_id')->getValue();
```

### Static Connection in Tests (DAMA)
DAMA DoctrineTestBundle uses static connections with transaction wrapping. This means:
- Each test runs in a transaction that auto-rolls back
- No test data persists between tests
- But you must be careful with `$em->clear()` for identity map freshness
- Static metadata and query caches are enabled for performance

### BaseRepository.save() Calls flush()
Both `BaseRepository::save()` and `remove()` call `$this->_em->flush()`. This means every save triggers a full flush — be aware when doing batch operations.

### Autowiring Exceptions
Some services have explicit wiring in `services.yaml`:
- `MessageRepository` — needs translator and token_storage
- `ContextProcessor` — needs kernel and token_storage
- `CommunicationActivitySubscriber` — manually tagged as doctrine event listener
- `UserRepositoryInterface` — maps to App\Repository\UserRepository
- All Provider interfaces — explicitly mapped to implementations

### Bundle Registration
The `pegass-crawler-bundle` is registered in `composer.json` autoload but the directory does NOT exist in the current codebase. Ignore references to it.

### Docker Development (likely broken)
A `docker-compose.yml` exists at the project root with PHP-FPM (9000), Nginx (81→80), MySQL 5.7 (3308→3306), but it has not been maintained for a long time and is likely broken. **Development is done directly on the host system** using the Symfony local server (`symfony serve` or `php bin/console server:start` via `make run`). MySQL runs natively on the host.
