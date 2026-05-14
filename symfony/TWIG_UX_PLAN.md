# Twig UX Review — Plan

## Design system

- **CSS framework**: Bootstrap 4.6 (loaded via `assets/css/bootstrap.min.css`; password-login bundle layout pulls 4.1.1 from a CDN, kept as-is).
- **Icons**: emoji glyphs (📱📞✉️🗑❌) embedded directly in templates. No icon font in use.
- **JS**: jQuery 3, popper.js, custom code. No Symfony UX components.
- **Form theme**: custom `templates/form_theme.html.twig` overrides checkboxes/radios as toggles and adds widget rows for volunteers, structures, badges, categories, phones. Forms use the default Symfony Bootstrap form theme as base.
- **Locale**: bilingual (fr/en), default `fr`. Strings go through `|trans`. We will add English keys only.

## Baseline

- Tests passing: **1571 tests / 3039 assertions** at start (chore/dependency-upgrade-20260514).
- Branch: continue on `chore/dependency-upgrade-20260514`. Commits stack on top.

## Templates by group (review order)

### 1. Layouts (do first — they propagate)
- `templates/base.html.twig` — main layout
- `bundles/password-login-bundle/Resources/views/base.html.twig` — auth-bundle layout
- `templates/bundles/TwigBundle/Exception/error.html.twig` — error page
- `templates/synthesis/base.html.twig` — public synthesis layout

### 2. Auth pages (high stakes)
- `templates/bundles/PasswordLoginBundle/security/connect.html.twig`
- `templates/bundles/PasswordLoginBundle/security/register.html.twig`
- `templates/bundles/PasswordLoginBundle/security/forgot_password.html.twig`
- `templates/bundles/PasswordLoginBundle/security/change_password.html.twig`
- `templates/bundles/PasswordLoginBundle/security/profile.html.twig`
- `templates/nivol/login.html.twig`
- `templates/nivol/code.html.twig`

### 3. Top-level user pages
- `templates/home.html.twig`
- `templates/management/home.html.twig`
- `templates/costs/home.html.twig`
- `templates/space/*.html.twig` — volunteer self-service space

### 4. Forms and lists (management area)
- `templates/management/volunteers/form.html.twig`, `list.html.twig`, `volunteer.html.twig`
- `templates/management/structures/list.html.twig`, `form.html.twig`, `structure.html.twig`, `users.html.twig`
- `templates/management/structures/template/list.html.twig`, `editor.html.twig`
- `templates/management/structures/prefilled_answers/list.html.twig`, `editor.html.twig`
- `templates/management/structures/volunteer_list/*.html.twig`
- `templates/favorite_badge/index.html.twig`

### 5. Public message / synthesis pages (anonymous)
- `templates/message/*.html.twig`
- `templates/synthesis/*.html.twig`

### 6. Admin pages — skip per ground rules unless obviously broken
- `templates/admin/**`

### 7. Communication form-flow + status (complex domain — minimal touch)
- `templates/new_communication/*.html.twig`
- `templates/status_communication/*.html.twig`
- `templates/campaign/*.html.twig`
- `templates/audience/*.html.twig`

## Where the wins are

Focus areas, in priority order:
1. `<html lang>` and `<title>` in base layouts (impacts every page).
2. Skip-to-content link in main base layout.
3. ARIA roles on flash messages (`role="alert"` for warning/danger, `role="status"` for info/success).
4. Auth pages: `autocomplete`, `autofocus`, semantic submit button labels (mostly already done via form types — verify and patch where bypassed).
5. Empty states on list pages.
6. Icon-only buttons get `aria-label`.
7. Destructive action confirmations where missing.

## What we will NOT do

- No new dependencies, no JS libraries, no design tokens.
- No translation into French of newly-added English keys (that's a translator's job).
- No reformatting of unrelated lines.
- No admin templates unless plainly broken.
