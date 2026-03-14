# RedCall - Symfony Application

## Project Overview
RedCall is a volunteer management and emergency communication platform for the French Red Cross.
It manages volunteers, structures (organizational units), campaigns (mass communication), and integrates with external systems (Pegass, Twilio, Google).

## Tech Stack
- **Framework:** Symfony 5.x with annotation-based routing (`@Route` annotations on controllers)
- **ORM:** Doctrine 2.11+ with MySQL, annotation-based mappings (`@ORM\Entity`, etc.)
- **Templates:** Twig
- **Auth:** Multi-authenticator guard system (form login, NIVOL code, Google OAuth, Minutis)
- **Testing:** PHPUnit 9.6 + symfony/browser-kit + DAMA DoctrineTestBundle (transaction wrapping)
- **Assets:** Webpack Encore
- **Forms:** CraueFormFlow for multi-step forms (campaign creation)
- **Enums:** MyCLabs\Enum (not native PHP enums)
- **Pagination:** babdev/pagerfanta-bundle

## Architecture

### Directory Structure
```
src/
  Base/                       # Abstract base classes
    BaseController.php        # orderBy(), createNamedFormBuilder(), validateCsrfOrThrowNotFoundException()
    BaseCommand.php           # Shared console command base
    BaseRepository.php        # Shared repository base
    BaseService.php           # Shared service base
  Controller/                 # Route handlers (annotation-based, 34 controllers)
    Admin/                    # ROLE_ADMIN or ROLE_ROOT required
    Management/               # ROLE_TRUSTED required
      Structure/              # Structure-scoped (templates, prefilled answers, volunteer lists)
      Volunteer/              # Volunteer CRUD
    OAuth/                    # Google Connect flow
  Entity/                     # 28 Doctrine entities with annotation mappings
  Enum/                       # MyCLabs\Enum classes (Type, Group, Resource, Stop, Crud, ResourceOwnership)
  Form/
    Flow/                     # CraueFormFlow multi-step forms (Campaign, SMS/Call/Email triggers)
    Model/                    # Non-entity form data classes (Campaign, BaseTrigger, SmsTrigger, etc.)
    Type/                     # Symfony form types (30+ types)
    Extension/                # Form type extensions (RegistrationTypeExtension)
  Manager/                    # 32 business logic services - the core service layer
  Security/
    Authenticator/            # 3 guard authenticators (Minutis, GoogleConnect, Nivol)
    Voter/                    # 8 voters (Campaign, Communication, Structure, Volunteer, Badge, Category, User, VolunteerSession)
    Helper/Security.php       # Custom security helper wrapping Symfony Security
  Repository/                 # 27 Doctrine repositories with custom query methods
  Services/                   # Domain services (MessageFormatter, Mjml, Phrase, TextToSpeech, VoiceCalls)
    InstancesNationales/      # National directory sync services
  Communication/
    Processor/                # Message sending strategies (Simple, Exec, Task)
    Sender.php                # Orchestrates sending SMS/Call/Email via processors
  Provider/                   # External service adapters
    Call/                     # Twilio voice calls
    Email/                    # Sendgrid, Symfony mailer
    Minutis/                  # Minutis API (Red Cross internal system)
    OAuth/GoogleConnect/      # Google OAuth
    SMS/                      # Twilio SMS (with task-based status variant)
    Storage/                  # Google Cloud Storage
  Task/                       # Background job handlers (Pegass sync, send messages, etc.)
  Tools/                      # Pure utility classes (Encryption, GSM, Hash, Random, Url, EscapedArray)
  Model/                      # Value objects (Classification, LanguageConfig, PhoneConfig, etc.)
    InstancesNationales/      # DTOs for national directory imports
  Component/HttpFoundation/   # Custom response types (CSV, PDF, Download, NoContent)
  Validator/Constraints/      # Custom validators (Phone, Unlocked, WhitelistedRedirectUrl)
  Twig/Extension/             # Twig functions/filters (AppExtension, CommunicationExtension)
  EventSubscriber/            # 6 subscribers (Locale, Timezone, Exception, Pegass, Twilio, CommunicationActivity)
  ParamConverter/             # CsrfParamConverter, EnumParamConverter
  Logger/                     # ContextProcessor for Monolog
bundles/                      # Custom Symfony bundles (not in src/)
  password-login-bundle/      # Auth forms, AbstractUser entity, email verification, captcha
  sandbox-bundle/             # Test fakes (FakeEmailManager, FakeEmailProvider, anonymization)
  twilio-bundle/              # Twilio webhook handling, TwilioStatusManager
  google-task-bundle/         # Google Cloud Tasks integration
  settings-bundle/            # Key-value settings store
  pagination-bundle/          # Pagination helpers wrapping Pagerfanta
templates/                    # Twig templates matching controller structure (~130 templates)
tests/
  Base/BaseWebTestCase.php    # WebTestCase with login() helper
  Fixtures/DataFixtures.php   # Factory for all test entities + scenario presets
  Controller/                 # Integration tests (~99 test methods across 21 files)
    Admin/                    # Admin controller tests
    Management/               # Management controller tests
```

### Key Entities & Relationships
- **User** (extends AbstractUser from PasswordLoginBundle) ↔ **Volunteer** (1:1) — User has auth, Volunteer has contact info
- **User** ↔ **Structure** (M:M) — Users manage structures
- **Volunteer** ↔ **Structure** (M:M) — Volunteers belong to structures
- **Volunteer** ↔ **Badge** (M:M) — Skills/certifications
- **Volunteer** ↔ **Phone** (M:M) — Phone numbers with country/format data
- **Campaign** → **Communication** (1:M) — Campaign has SMS/Call/Email communications
- **Communication** → **Message** (1:M) + **Choice** (1:M)
- **Message** → **Answer** (1:M) + **Cost** (1:M)
- **Structure** → **Template** (1:M) + **PrefilledAnswers** (1:M) + **VolunteerList** (1:M)
- **Badge** → **Category** (M:1), self-referencing parent/children and synonym relationships
- **Campaign** → **Operation** (1:1, optional)
- **Template** → **TemplateImage** (1:M)
- **Pegass** — External data cache with lifecycle management
- **VolunteerSession** — Session tokens for volunteer personal space access

### Entity ID Types
- **User**: string (UUID v4, from AbstractUser)
- **All other entities**: integer (auto-increment)
- **Campaign.code**, **Message.code**: binary(8) — used in public URLs (/syn/{code}, /msg/{code})

### Doctrine Specifics
- `ChangeTrackingPolicy("DEFERRED_EXPLICIT")` on User, Phone — must explicitly persist
- `HasLifecycleCallbacks` on several entities (User, Phone, Communication, PrefilledAnswers)
- Custom connection wrapper: `facile-it/doctrine-mysql-come-back` (auto-reconnect) — disabled in test env

## Security Model

### Access Control (security.yaml)
- Anonymous: /connect, /register, /forgot-password, /verify-email, /nivol, /code, /space, /twilio, /media, /syn, /msg, /geo, /cron, /task, /deploy, /cloud-task, /auth, /google-*
- ROLE_USER: /, /logout, /profile, /guest
- ROLE_TRUSTED: all other pages (campaigns, management, audience, costs, favorite-badge, etc.)
- ROLE_ADMIN: /admin/*
- ROLE_ROOT: inherits ROLE_ADMIN (role_hierarchy), required for /admin/maintenance

### Role Hierarchy
- ROLE_ROOT → ROLE_ADMIN → (implicit ROLE_USER via isAdmin/isTrusted/isVerified flags)
- Roles are computed in User::getRoles() based on boolean flags (isVerified, isTrusted, isAdmin, isRoot)

### Voters (attribute-based authorization)
| Voter | Attributes | Logic |
|-------|-----------|-------|
| CampaignVoter | CAMPAIGN_ACCESS, CAMPAIGN_OWNER | User's structures overlap with campaign's volunteer structures |
| CommunicationVoter | COMMUNICATION | Same as campaign access via communication's campaign |
| StructureVoter | STRUCTURE | User has the structure in their collection, or is admin |
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

## Testing Patterns

### Integration Tests (existing)
- Extend `BaseWebTestCase` for authenticated tests (provides `login(KernelBrowser, UserInterface, firewall)`)
- Use `DataFixtures` for entity creation and scenario setup (see below)
- CSRF is **disabled** in test env (`config/packages/test/framework.yaml`)
- DAMA DoctrineTestBundle wraps each test in a transaction (auto-rollback, no cleanup needed)
- `FakeEmailManager` from SandboxBundle intercepts emails in test env (`config/services_test.yaml`)
- Use `$client->followRedirects()` for form submission flows
- CSRF tokens for action URLs: `$container->get('security.csrf.token_manager')->getToken('token_id')->getValue()`
- After DB writes, call `$em->clear()` before re-reading to get fresh data

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

**Scenario presets** (preferred — use these instead of manual multi-step setup):

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

**Entity factories** (for building custom scenarios):

`createRawUser`, `createVolunteer`, `createStandaloneVolunteer`, `createStructure`, `assignUserToStructure`, `assignVolunteerToStructure`, `createBadge`, `createCategory`, `createCampaign`, `createCommunication`, `createChoice`, `createMessage`, `createAnswer`, `createPrefilledAnswers`, `createTemplate`, `createVolunteerList`, `createVolunteerSession`, `createPegass`, `createOperation`, `createMedia`

### Running Tests
```bash
php vendor/bin/phpunit                    # all tests
php vendor/bin/phpunit tests/Controller/  # integration tests only
php vendor/bin/phpunit --filter=testName  # single test
```

## External Integrations
- **Pegass** — Red Cross volunteer directory (sync via PegassManager, cached in Pegass entity)
- **Twilio** — SMS and voice calls (TwilioBundle handles webhooks at /twilio/*)
- **Sendgrid** — Email delivery (replaced by FakeEmailProvider in test)
- **Google Cloud Tasks** — Background job dispatch (GoogleTaskBundle)
- **Google Cloud Storage** — Media file storage
- **Minutis** — Red Cross internal platform SSO
- **Google OAuth** — Alternative login method

## Common Pitfalls
- The `Read` CLI tool may fail on absolute paths due to macOS firmlink resolution; use `cat -n` via Bash as fallback
- Entity manager needs `$em->clear()` after write operations to get fresh reads
- Campaign creation uses multi-step CraueFormFlow — can't be tested with simple form submit
- ParamConverter annotations auto-fetch entities from route parameters
- User ID is a UUID string, not an integer
- Campaign.code and Message.code are binary fields — getCode() reads from stream resource if needed
- PrefilledAnswers uses comma replacement trick for storing comma-separated answers in simple_array column
- Phone entity requires libphonenumber for parsing/formatting
