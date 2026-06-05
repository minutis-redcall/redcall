# Twig UX Review — Report

A surgical UX review pass on RedCall's Twig templates. Each change is small,
specific, backed by an integration test, and committed individually so a
reviewer can read every diff and either accept or revert in isolation.

## Test suite delta

- **Baseline** (before this pass): 1571 tests / 3039 assertions, all green.
- **Final**: 1588 tests / 3085 assertions, all green. **+17 new UX/a11y tests**.

The new tests use `Symfony\Bundle\FrameworkBundle\Test\WebTestCase` and
`DomCrawler` — no new dependencies. They assert the specific markup
contract each commit introduced, named after the assertion itself
("testLoginFieldHasUsernameAutocomplete") so a future "cleanup" commit
that removes the attribute breaks a test the contributor can read and
understand instead of a generic regression.

## Templates reviewed

### Layouts — changed
- `templates/base.html.twig` — `<html lang>` from request locale; ARIA
  roles on flash messages (`role="alert"` for danger/warning,
  `role="status"` for info/success); `aria-label` on flash close buttons.
- `bundles/password-login-bundle/Resources/views/base.html.twig` — same
  pair of fixes mirrored.
- `templates/bundles/TwigBundle/Exception/error.html.twig` — meaningful
  `<title>` ("404 Not Found – RedCall") instead of bare "RedCall".

### Auth pages — changed
- `bundles/password-login-bundle/Form/Type/ConnectType.php` — login email
  gets `autocomplete="username"`, `inputmode="email"`, `autofocus`;
  password gets `autocomplete="current-password"`.
- `bundles/password-login-bundle/Form/Type/RegistrationType.php` — email
  `autocomplete="username"`, both new password fields
  `autocomplete="new-password"`.
- `bundles/password-login-bundle/Form/Type/ForgotPasswordType.php` —
  email field `autocomplete="username"`, `autofocus`.
- `bundles/password-login-bundle/Form/Type/ChangePasswordType.php` —
  both fields `autocomplete="new-password"`, `autofocus` on first.
- `bundles/password-login-bundle/Form/Type/ProfileType.php` — current
  password / new password / email all get correct autocomplete hints.
- `src/Form/Type/NivolType.php` — `autocomplete="off"`,
  `autocapitalize="characters"`, `autofocus` on NIVOL field.
- `src/Form/Type/CodeType.php` — `autocomplete="one-time-code"`,
  `autocapitalize="characters"`, `maxlength=6`, `autofocus` on OTP field.
- `templates/bundles/PasswordLoginBundle/security/connect.html.twig` —
  method-selector buttons get explicit `type="button"`; Google CTA
  reads "Connect with Google" with the Google logo marked decorative.
- `templates/nivol/login.html.twig`, `templates/nivol/code.html.twig` —
  `alt` attribute added to the RedCall logo.

### Lists/index pages — changed
- `templates/management/structures/template/list.html.twig` —
  `aria-label` on the bare ▲/▼ reorder buttons, naming the template and
  the direction.
- `templates/management/structures/prefilled_answers/list.html.twig` —
  destructive Delete CTA now uses `btn-danger` (was `btn-secondary`,
  same weight as Edit); per-row heading promoted from `<h3>` to
  `<h2 class="h3">` to fix the heading outline.
- `templates/favorite_badge/index.html.twig` — added `onclick=confirm`
  guard on the favourite-badge Delete CTA (was a one-click destroy).
- `templates/home.html.twig` — promoted the "no structures" landing
  heading from `<h3>` to `<h1>` so the page has its required single h1.
- `templates/management/home.html.twig` — heading hierarchy h1→h2→h3
  restored (was h1→h4→h5, skipping two levels).

### Detail/show pages and partials — changed
- `templates/management/volunteers/volunteer.html.twig` — `aria-label`
  on lock/unlock toggle; volunteer-flag emojis (🐻/👤) and the
  Minutis-user `<img>` get accessible names via three new translation
  keys.
- `templates/management/structures/structure.html.twig` — same pattern
  on the structure card (lock toggle + 🚫 disabled-status icon).
- `templates/management/volunteers/form.html.twig` — six section
  headings promoted from `<h3>` to `<h2 class="h3">` so the
  five-tab form has a contiguous heading outline.
- `templates/macros.html.twig` — `aria-label`/`alt` on the volunteer
  status icons in the shared macro (mirrors the per-page fix).
- `templates/message/index.html.twig` — `aria-label` on the bare-❌
  cancel-response button on the public message page, locale-aware.
- `templates/space/index.html.twig`, `templates/space/infos.html.twig` —
  `alt` attributes on volunteer self-service images.

### Reviewed, no changes
- `templates/costs/home.html.twig` — already has good heading order,
  empty states, and labels.
- `templates/management/structures/form.html.twig` — minimal page,
  clean.
- `templates/management/structures/prefilled_answers/editor.html.twig` —
  delegates to a widget; clean.
- `templates/synthesis/base.html.twig` — public anonymous page, very
  lightweight; the cross-cutting fix in `templates/base.html.twig` is
  enough.

### Skipped (per ground rules)
- All admin templates under `templates/admin/**` — gated by
  `ROLE_ADMIN`, lower UX-ROI, off the no-touch list.
- Communication form-flow (`templates/new_communication/**`) and
  status views (`templates/status_communication/**`) — heavy domain
  complexity with CraueFormFlow; out of scope for a "small, surgical"
  pass.
- `templates/admin/*` and bundles' admin views — admin-only.

## Categorized summary

| Category                                  | Count |
|------------------------------------------- |-------|
| `autocomplete` / `autofocus` / `inputmode` | 13 fields across 6 form types |
| `aria-label` on icon-only controls         | 8 controls (lock toggles, reorder buttons, cancel ❌, flag emojis) |
| `<img alt>` added or improved              | 5 images |
| Heading hierarchy fixes                    | 4 templates (volunteer form, prefilled list, home, management home) |
| Flash-message ARIA roles                   | 8 flash variants across 2 layouts |
| Destructive-action visual + confirm guard  | 2 (favourite badge, prefilled answers) |
| Microcopy improvements                     | 2 (error page title, Google CTA label) |
| `<html lang>` declaration                  | 2 base layouts |
| Type-safe button declarations              | 2 buttons on /connect |

## Translation keys added (English only — `messages.en.yml`)

A French translator should populate the FR equivalents.

| Key                                                | English value                  |
|----------------------------------------------------|--------------------------------|
| `manage_structures.disabled_status`                | "Disabled structure"           |
| `manage_structures.templates.actions.move_up_aria` | "Move template "%name%" up"   |
| `manage_structures.templates.actions.move_down_aria` | "Move template "%name%" down" |
| `manage_volunteers.flags.has_account`              | "RedCall user"                 |
| `manage_volunteers.flags.minor`                    | "Minor volunteer"              |
| `manage_volunteers.flags.volunteer`                | "Volunteer"                    |
| `campaign_status.cancel_answer`                    | "Cancel response "%choice%""   |

## Follow-ups noticed, out of scope

These would each be a focused project on their own:

1. **Form theme could wire `aria-describedby`** from error nodes to their
   field — fixing this once in `templates/form_theme.html.twig` would
   propagate to every form in the app. Symfony's default Bootstrap form
   theme already does this; the custom overrides break the chain on
   the radio/checkbox/answer/phone widgets.
2. **Title pattern inconsistency** — most pages override `{% block title
   %}` to a single page-specific string; the browser tab reads "Connect"
   or "Register" with no app-name context. A clean fix needs a new
   `{% block page_title %}` convention and migration of every title
   override; doing it piecemeal would mix two conventions on the same
   site. Holding for a dedicated PR.
3. **Status colours convey state alone** in a handful of places (red
   text for "Not callable", `color:lightgray` for IDs). Most of these
   are paired with text so they're fine, but a once-over to apply a
   semantic class instead of inline `style="color:..."` would help.
4. **Five different button visual weights** (`btn-primary`,
   `btn-secondary`, `btn-outline-secondary`, `btn-link`, `btn-light`)
   appear across templates with no clear rule. A design-system
   consolidation pass would help newcomers know which to reach for.
5. **Tab panels on the volunteer form use `<a>` with role="tab"** —
   Bootstrap 4 generates these — but a screen-reader user pressing
   Tab still walks each panel link individually. Modern keyboard
   patterns (arrow keys to move within the tablist, Tab to escape)
   would be nicer; that's a JS change and outside this pass.
6. **CraueFormFlow buttons** in
   `bundles/formflow-bundle/Resources/views/FormFlow/buttons.html.twig`
   are vendored; haven't reviewed them yet, may have similar
   accessible-name gaps.

## Branch state

Branch: `chore/dependency-upgrade-20260514`. New commits stack on top
of the existing dependency-upgrade work; nothing has been rebased,
amended, or rewritten. Each UX change is its own commit with the
`ux(scope): short description` pattern.
