#!/usr/bin/env bash

set -euo pipefail

# ─── Configuration ────────────────────────────────────────────────────────────

GCP_ACCOUNT="alain.tiemblo@croix-rouge.fr"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
SYMFONY_DIR="$ROOT_DIR/symfony"
VERSIONS_TO_KEEP=3

# ─── Helpers ──────────────────────────────────────────────────────────────────

log()   { echo "==> $*"; }
error() { echo "ERROR: $*" >&2; }

# ─── Validate arguments ──────────────────────────────────────────────────────

ENV="${1:-}"

if [[ -z "$ENV" ]]; then
  echo "Usage: $0 <prod|preprod>"
  exit 1
fi

if [[ ! -d "$SCRIPT_DIR/$ENV" ]]; then
  error "'$ENV' is not a valid environment (no $SCRIPT_DIR/$ENV directory)."
  exit 1
fi

case "$ENV" in
  prod)    GCP_PROJECT="redcall-prod-260921" ;;
  preprod) GCP_PROJECT="redcall-dev" ;;
  *)
    error "No GCP project configured for environment '$ENV'."
    exit 1
    ;;
esac

GCLOUD_OPTS="--project=$GCP_PROJECT --account=$GCP_ACCOUNT"

# ─── Cleanup trap ─────────────────────────────────────────────────────────────
# Always restore local working files, whether the deploy succeeds or fails.

NEEDS_RESTORE=false

cleanup() {
  if [[ "$NEEDS_RESTORE" == true ]]; then
    log "Restoring local configuration files..."
    cp "$ROOT_DIR/.deploy-backup/.env" "$SYMFONY_DIR/.env"
    cp "$ROOT_DIR/.deploy-backup/google-service-account.json" "$SYMFONY_DIR/config/keys/google-service-account.json"
  fi
  rm -rf "$ROOT_DIR/.deploy-backup"
  rm -f "$SYMFONY_DIR/app.yaml" "$SYMFONY_DIR/cron.yaml"
}

trap cleanup EXIT

# ─── Pre-flight checks ───────────────────────────────────────────────────────

log "Checking prerequisites..."

for cmd in gcloud yarn php; do
  if ! command -v "$cmd" &>/dev/null; then
    error "'$cmd' is not installed or not in PATH."
    exit 1
  fi
done

for file in "$SCRIPT_DIR/$ENV/app.yaml" "$SCRIPT_DIR/$ENV/dotenv" "$SCRIPT_DIR/$ENV/google-service-account.json" "$SCRIPT_DIR/$ENV/cron.yaml"; do
  if [[ ! -f "$file" ]]; then
    error "Missing deploy config: $file"
    exit 1
  fi
done

# ─── Back up local files ─────────────────────────────────────────────────────

log "Backing up local configuration..."
rm -rf "$ROOT_DIR/.deploy-backup"
mkdir -p "$ROOT_DIR/.deploy-backup"
cp "$SYMFONY_DIR/.env" "$ROOT_DIR/.deploy-backup/.env"
cp "$SYMFONY_DIR/config/keys/google-service-account.json" "$ROOT_DIR/.deploy-backup/google-service-account.json"
NEEDS_RESTORE=true

# ─── Reset the prod container cache ──────────────────────────────────────────
# The prod kernel compiles its container into sys_temp (see Kernel::getCacheDir),
# NOT into var/cache. A container left there by a previous deploy can carry an
# out-of-date service signature and make the commands below fail to boot
# (e.g. "Too few arguments to ...::__construct()"). Rebuild it from scratch.

log "Clearing stale prod container cache..."
rm -rf "$(php -r 'echo sys_get_temp_dir();')/redcall/cache"

# ─── Generate MJML templates ─────────────────────────────────────────────────

log "Generating MJML email templates..."
php "$SYMFONY_DIR/bin/console" generate:mjml "$SYMFONY_DIR/templates/message/email.html.twig.mjml"
php "$SYMFONY_DIR/bin/console" generate:mjml "$SYMFONY_DIR/templates/message/image.html.twig.mjml"

# ─── Swap in deploy configuration ────────────────────────────────────────────

log "Copying $ENV configuration into symfony/..."
cp "$SCRIPT_DIR/$ENV/app.yaml" "$SYMFONY_DIR/"
cp "$SCRIPT_DIR/$ENV/dotenv" "$SYMFONY_DIR/.env"
cp "$SCRIPT_DIR/$ENV/google-service-account.json" "$SYMFONY_DIR/config/keys/"
cp "$SCRIPT_DIR/$ENV/cron.yaml" "$SYMFONY_DIR/"

# ─── Build frontend assets ───────────────────────────────────────────────────

log "Building frontend assets..."
cd "$SYMFONY_DIR"
yarn encore production

# ─── Deploy to App Engine ─────────────────────────────────────────────────────

log "Deploying application to $ENV ($GCP_PROJECT)..."
gcloud config set project "$GCP_PROJECT" $GCLOUD_OPTS
gcloud config set app/cloud_build_timeout 3600 $GCLOUD_OPTS
gcloud beta app deploy --verbosity info --quiet --no-cache $GCLOUD_OPTS

log "Deploying cron jobs..."
gcloud app deploy --quiet "$SYMFONY_DIR/cron.yaml" $GCLOUD_OPTS

cd "$ROOT_DIR"

# ─── Restore local files (also runs via trap on failure) ──────────────────────

log "Restoring local configuration..."
cp "$ROOT_DIR/.deploy-backup/.env" "$SYMFONY_DIR/.env"
cp "$ROOT_DIR/.deploy-backup/google-service-account.json" "$SYMFONY_DIR/config/keys/google-service-account.json"
NEEDS_RESTORE=false

# ─── Clean up old versions ───────────────────────────────────────────────────

log "Cleaning up old App Engine versions (keeping last $VERSIONS_TO_KEEP)..."
OLD_VERSIONS=$(
  gcloud app versions list \
    --service default \
    --sort-by '~version' \
    --format 'value(version.id)' \
    $GCLOUD_OPTS \
  | sort -rV \
  | tail -n +$((VERSIONS_TO_KEEP + 1))
)

if [[ -n "$OLD_VERSIONS" ]]; then
  log "Deleting old versions: $OLD_VERSIONS"
  gcloud app versions delete --service default $OLD_VERSIONS -q $GCLOUD_OPTS || true
else
  log "No old versions to clean up."
fi

log "Deploy to $ENV complete."
