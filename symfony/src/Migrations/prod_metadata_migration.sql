-- ============================================================================
-- PROD ONLY — migrate Doctrine Migrations 2.x → 3.x metadata table
--
-- Run this BEFORE the next `doctrine:migrations:migrate` on prod.
-- After this, prod will be ready for any new migrations (none currently
-- pending — staging is already up to date).
--
-- What it does:
--   1. Sanity-check rows: the SELECT at the top must return exactly 1 row
--      with columns matching the assumption. Read the result, confirm, then
--      uncomment the rest and run.
--   2. Creates `doctrine_migration_versions` (Doctrine 3.x format).
--   3. Copies all rows from `migration_versions`, prefixing each bare
--      timestamp version with `DoctrineMigrations\Version`.
--   4. Renames the legacy table to `migration_versions_legacy` (drop later
--      once a real migrate run confirms everything works).
--
-- Note: MySQL DDL implicitly commits, so the transaction protects DML only.
-- ============================================================================

-- ---- STEP 1: PRE-FLIGHT CHECK (run this alone first) -----------------------
-- Expected:
--   columns          = 'version,executed_at'
--   sample_version   = a bare timestamp like '20231019143514' (NO backslash)
--   row_count        > 0  (probably ~225-235)
--   target_exists    = 0  (doctrine_migration_versions must NOT already exist)

SELECT
  (SELECT GROUP_CONCAT(column_name ORDER BY ordinal_position SEPARATOR ',')
     FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'migration_versions')        AS columns,
  (SELECT version FROM migration_versions ORDER BY version DESC LIMIT 1)           AS sample_version,
  (SELECT COUNT(*) FROM migration_versions)                                        AS row_count,
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'doctrine_migration_versions') AS target_exists;

-- ---- STEP 2: MIGRATION (uncomment everything below after the check passes) -

START TRANSACTION;

CREATE TABLE doctrine_migration_versions (
    version VARCHAR(191) NOT NULL,
    executed_at DATETIME DEFAULT NULL,
    execution_time INT DEFAULT NULL,
    PRIMARY KEY(version)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;

INSERT INTO doctrine_migration_versions (version, executed_at, execution_time)
SELECT CONCAT('DoctrineMigrations\\Version', version), executed_at, NULL
FROM migration_versions;

RENAME TABLE migration_versions TO migration_versions_legacy;

COMMIT;

-- ---- STEP 3: SANITY CHECKS (run after the migration block) -----------------
-- Expected:
--   row counts should match (every legacy row copied)
--   bad_format should be 0
--
 SELECT
   (SELECT COUNT(*) FROM migration_versions_legacy)                              AS legacy_rows,
   (SELECT COUNT(*) FROM doctrine_migration_versions)                            AS new_rows,
   (SELECT COUNT(*) FROM doctrine_migration_versions
      WHERE version NOT LIKE 'DoctrineMigrations\\\\Version%')                   AS bad_format;

-- ============================================================================
-- STEPS 4–6 — apply the 7 pending schema migrations (20260112 → 20260424)
-- and record them in doctrine_migration_versions.
--
-- Migrations covered (in chronological order):
--   - Version20260112175632  campaign.group_names
--   - Version20260112175829  volunteer_group table
--   - Version20260123055852  drop stat_* tables
--   - Version20260124203615  drop token/webhook, drop 2 user columns
--   - Version20260126081210  sessions.sess_id length 128
--   - Version20260227120000  remove platform columns (only FR was used)
--   - Version20260424120000  campaign.last_activity_at + backfill
-- ============================================================================

-- ---- STEP 4: PRE-FLIGHT CHECK (run alone first) ----------------------------
-- Expected on a fresh prod sitting at 20231019143514:
--   campaign_group_names      = 0
--   has_volunteer_group       = 0
--   has_stat_page             = 1
--   has_token                 = 1
--   has_webhook               = 1
--   user_is_developer         = 1
--   user_is_pegass_api        = 1
--   campaign_platform         = 1
--   user_platform             = 1
--   volunteer_pf_extid_idx    = 1
--   volunteer_extid_idx       = 0
--   campaign_last_activity_at = 0
--   sessions_sess_id_len      = anything (will be set to 128 by step 5)
--
-- If any value differs from the expected, STOP and double-check — the schema
-- might already be partially ahead, in which case some statements below need
-- to be skipped to avoid errors.

SELECT
  (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'campaign' AND column_name = 'group_names')       AS campaign_group_names,
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'volunteer_group')                                AS has_volunteer_group,
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'stat_page')                                      AS has_stat_page,
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'token')                                          AS has_token,
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'webhook')                                        AS has_webhook,
  (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'user'     AND column_name = 'is_developer')      AS user_is_developer,
  (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'user'     AND column_name = 'is_pegass_api')     AS user_is_pegass_api,
  (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'campaign' AND column_name = 'platform')          AS campaign_platform,
  (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'user'     AND column_name = 'platform')          AS user_platform,
  (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = DATABASE() AND table_name = 'volunteer' AND index_name = 'pf_extid_idx')      AS volunteer_pf_extid_idx,
  (SELECT COUNT(*) FROM information_schema.statistics
     WHERE table_schema = DATABASE() AND table_name = 'volunteer' AND index_name = 'extid_idx')         AS volunteer_extid_idx,
  (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'campaign' AND column_name = 'last_activity_at')  AS campaign_last_activity_at,
  (SELECT character_maximum_length FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'sessions' AND column_name = 'sess_id')           AS sessions_sess_id_len;

-- ---- STEP 5: APPLY PENDING DDL + RECORD ALL 7 VERSIONS ---------------------
-- Adjusted for prod's actual state on 2026-06-05:
--   5 of 7 migrations are already applied to the schema (campaign.group_names,
--   volunteer_group, sess_id length, platform removal, campaign.last_activity_at).
--   Only two have NOT been applied: drop stat_* tables, and drop token/webhook
--   + 2 user columns.
--
--   The last_activity_at backfill is re-run defensively — it's idempotent
--   (UPDATE SET ... = MAX(...) gives the same value if already filled).
--
-- Note: MySQL DDL implicitly commits, so the transaction only protects DML.
-- Take a backup before running.

START TRANSACTION;

-- ---- Version20260123055852 — drop stat_* tables ----------------------------
ALTER TABLE stat_visualization DROP FOREIGN KEY FK_D63899ABC4663E4;
ALTER TABLE stat_visualization DROP FOREIGN KEY FK_D63899ABEF946F99;
DROP TABLE stat_page;
DROP TABLE stat_query;
DROP TABLE stat_visualization;

-- ---- Version20260124203615 — drop token/webhook + 2 user columns -----------
DROP TABLE token;
DROP TABLE webhook;
ALTER TABLE user DROP is_developer;
ALTER TABLE user DROP is_pegass_api;

-- ---- Version20260424120000 — backfill (column + index already present) -----
UPDATE campaign c
SET c.last_activity_at = (
    SELECT MAX(co.last_activity_at)
    FROM communication co
    WHERE co.campaign_id = c.id
);

-- ---- Record all 7 versions as applied --------------------------------------
INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) VALUES
    ('DoctrineMigrations\\Version20260112175632', NOW(), 0),
    ('DoctrineMigrations\\Version20260112175829', NOW(), 0),
    ('DoctrineMigrations\\Version20260123055852', NOW(), 0),
    ('DoctrineMigrations\\Version20260124203615', NOW(), 0),
    ('DoctrineMigrations\\Version20260126081210', NOW(), 0),
    ('DoctrineMigrations\\Version20260227120000', NOW(), 0),
    ('DoctrineMigrations\\Version20260424120000', NOW(), 0);

COMMIT;

-- ---- STEP 6: SANITY CHECKS -------------------------------------------------
-- Expected:
--   campaign_group_names      = 1
--   has_volunteer_group       = 1
--   has_stat_page             = 0
--   has_token                 = 0
--   campaign_platform         = 0
--   campaign_last_activity_at = 1
--   sessions_sess_id_len      = 128
--   tracked_new_versions      = 7
--
SELECT
  (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'campaign' AND column_name = 'group_names')       AS campaign_group_names,
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'volunteer_group')                                AS has_volunteer_group,
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'stat_page')                                      AS has_stat_page,
  (SELECT COUNT(*) FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name = 'token')                                          AS has_token,
  (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'campaign' AND column_name = 'platform')          AS campaign_platform,
  (SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'campaign' AND column_name = 'last_activity_at')  AS campaign_last_activity_at,
  (SELECT character_maximum_length FROM information_schema.columns
     WHERE table_schema = DATABASE() AND table_name = 'sessions' AND column_name = 'sess_id')           AS sessions_sess_id_len,
  (SELECT COUNT(*) FROM doctrine_migration_versions
     WHERE version IN (
       'DoctrineMigrations\\Version20260112175632',
       'DoctrineMigrations\\Version20260112175829',
       'DoctrineMigrations\\Version20260123055852',
       'DoctrineMigrations\\Version20260124203615',
       'DoctrineMigrations\\Version20260126081210',
       'DoctrineMigrations\\Version20260227120000',
       'DoctrineMigrations\\Version20260424120000'
     ))                                                                                                 AS tracked_new_versions;
