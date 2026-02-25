# Testing Plan

This document outlines the testing strategies and scenarios for each functional area of the application.

## 1. Authentication & User Account
Tests for `SecurityController` and related account management.

### Login (`/connect`, `/connect/{nivol}`, `/google-connect`)
- [ ] **Classic Login**: Verify successful login with email/password.
- [ ] **NIVOL Login**: Verify login via NIVOL (external ID).
- [ ] **Google Login**: Verify OAuth flow and automatic user creation if allowed.
- [ ] **Invalid Credentials**: Verify error message and no session created.
- [ ] **Locked Account**: Verify that locked users cannot connect.
- [ ] **Trusted Device**: Verify "trust this device" functionality.

### Registration & Verification (`/register`, `/verify-email/{uuid}`)
- [ ] **Register**: Verify user creation and verification email sent.
- [ ] **Register Duplicate**: Verify that duplicate email/nivol is handled.
- [ ] **Email Verification**: Verify that following the email link marks the user as verified.
- [ ] **Expired Link**: Verify behavior when using an expired verification link.

### Password Management (`/forgot-password`, `/change-password/{uuid}`, `/profile`)
- [ ] **Forgot Password**: Verify recovery email is sent only for existing users.
- [ ] **Reset Password**: Verify password update via recovery link.
- [ ] **Update Profile**: Verify updating username, password, and personal info in `/profile`.
- [ ] **Profile Security**: Verify that changing username triggers a logout and re-verification.

## 2. Campaigns
Tests for `CampaignController` and related logic.

### Campaign Lifecycle (`/campaign/new/{type}`, `/campaign/{id}`, `/campaign/{id}/close`)
- [ ] **Creation**: Verify multi-step creation flow for different types (SMS, Call, Email).
- [ ] **View Campaign**: Verify data display (status, counts, volunteers) for an active campaign.
- [ ] **Close/Open**: Verify closing a campaign and its impact on communications.
- [ ] **Rename/Notes**: Verify updating campaign metadata.

### Communications (`/campaign/{id}/add-communication/{type}`, `/campaign/{campaign}/communication/{communication}/relaunch`)
- [ ] **Add Communication**: Verify adding a secondary communication (e.g., SMS relaunch for an Email campaign).
- [ ] **Relaunch**: Verify relaunching a specific communication to non-responders.
- [ ] **Provider Info**: Verify viewing status details for a specific message.

### Polling & Real-time (`/campaign/{id}/long-polling`, `/campaign/{id}/short-polling`, `/syn/{code}`)
- [ ] **Status Updates**: Verify that the UI receives real-time updates for answers and message status.
- [ ] **Synthesis View**: Verify live campaign summary (`/syn/{code}`) for public/internal spectators.

## 3. Audience Selection
Tests for `AudienceController`.

### Selection Flow (`/audience/home`, `/audience/selection`)
- [ ] **Filters**: Verify filtering volunteers by badges, structures, or manual search.
- [ ] **Exclusion**: Verify excluding specific volunteers from a selection.
- [ ] **Persistence**: Verify that selection is saved when moving between steps or resuming.
- [ ] **Selection Summary**: Verify accurate counts of selected volunteers and their contactability (numbers, problems).

## 4. Management (Structures & Volunteers)
Tests for `Management` controllers.

### Structures (`/management/structures/`)
- [ ] **List/Search**: Verify listing and searching structures by name or platform.
- [ ] **Permissions**: Verify that structure admins can only see/edit their own structures.
- [ ] **Toggle State**: Verify enabling/disabling or locking/unlocking structures (Admin only).
- [ ] **Export**: Verify exporting structure data to CSV/PDF.

### Volunteers (`/management/volunteers/`)
- [ ] **Manual Creation/Edit**: Verify CRUD for volunteers not synced from Pegass.
- [ ] **Structure Assignment**: Verify adding/removing volunteers from structures.
- [ ] **Pegass Sync**: Verify manual triggers for Pegass data refresh.

### Templates & Prefilled Answers
- [ ] **Templates**: Verify CRUD for campaign message templates per structure.
- [ ] **Prefilled Answers**: Verify CRUD for standard responses (e.g., "I'm available", "Busy").

## 5. Administration
Tests for `Admin` dashboard.

### System Maintenance (`/admin/maintenance/`)
- [ ] **Search Engine**: Verify maintenance of the global search index.
- [ ] **Annuaire National**: Verify sync with national directory.
- [ ] **Maintenance Messages**: Verify displaying/clearing global site maintenance alerts.

### Pegass Management (`/admin/pegass`)
- [ ] **User Sync**: Verify global Pegass sync and user auto-creation.
- [ ] **Permissions**: Verify granting/revoking Admin, Root, and Trust roles.

### Statistics (`/admin/stats/`, `/costs/`)
- [ ] **General Stats**: Verify aggregated metrics (campaigns, messages, users).
- [ ] **Structure Stats**: Verify metrics filtered by organization level.
- [ ] **Cost Analysis**: Verify cost calculation based on message counts and provider rates (`/costs/`).

## 6. Public Portal (GDPR/Personal Space)
Tests for `/space/{sessionId}/`.

### Data Access
- [ ] **Consult Data**: Verify volunteers can see their own data via a signed link.
- [ ] **Download Data**: Verify GDPR-compliant data export (JSON/CSV).
- [ ] **Delete Data**: Verify "right to be forgotten" request flow.

## 7. Webhooks & Background Tasks
Tests for integrations.

### Twilio Hooks (`/twilio/incoming-call`, `/twilio/message-status`)
- [ ] **Voice Answer**: Simulate a voice response and verify answer capture.
- [ ] **SMS Answer**: Simulate an incoming SMS and verify answer parsing.
- [ ] **Delivery Receipts**: Verify message status updates (Delivered, Failed).

### Google Hooks (`/google-verify`)
- [ ] **Domain Verification**: Verify handling of Google Workplace domain verification tokens.

## 8. Developer Sandbox
Tests for `/sandbox/` (Test environment only).

- [ ] **Fixtures**: Verify loading various test data sets.
- [ ] **Fake Providers**: Verify interception of SMS, Emails, and Calls in the sandbox UI.
- [ ] **Anonymization**: Verify data anonymization tool.

## 9. UI Widgets
Tests for `/widget/`.

- [ ] **Search Widgets**: Verify AJAX search results for badges, structures, and volunteers.
- [ ] **Template Data**: Verify dynamically loading template content for campaigns.
