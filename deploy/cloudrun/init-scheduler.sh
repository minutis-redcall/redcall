#!/usr/bin/env bash

set -euo pipefail

# Creates (or updates) the Cloud Scheduler jobs replacing App Engine cron.yaml.
# Schedules mirror cron.yaml: 2 hourly jobs, 7 daily jobs (staggered at night).

GCP_ACCOUNT="alain.tiemblo@croix-rouge.fr"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DEPLOY_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
SERVICE="redcall"
REGION="europe-west1"

log()   { echo "==> $*"; }
error() { echo "ERROR: $*" >&2; }

ENV="${1:-}"

case "$ENV" in
  prod)    GCP_PROJECT="redcall-prod-260921" ;;
  preprod) GCP_PROJECT="redcall-dev" ;;
  *)
    echo "Usage: $0 <prod|preprod>"
    exit 1
    ;;
esac

GCLOUD=(gcloud --project="$GCP_PROJECT" --account="$GCP_ACCOUNT")
INVOKER_SA="cron-invoker@$GCP_PROJECT.iam.gserviceaccount.com"

SERVICE_URL="$("${GCLOUD[@]}" run services describe "$SERVICE" --region="$REGION" --format='value(status.url)' 2>/dev/null || true)"
if [[ -z "$SERVICE_URL" ]]; then
  error "Cloud Run service '$SERVICE' not found; deploy it first."
  exit 1
fi

# The OIDC token audience must equal WEBSITE_URL: CronTokenVerifier pins it.
AUDIENCE="$(grep -m1 '^WEBSITE_URL=' "$DEPLOY_DIR/$ENV/dotenv" 2>/dev/null | cut -d= -f2- | tr -d '\r' | sed -e "s/^['\"]//" -e "s/['\"]\$//" || true)"
if [[ -z "$AUDIENCE" ]]; then
  error "WEBSITE_URL not found in $DEPLOY_DIR/$ENV/dotenv"
  exit 1
fi

if ! "${GCLOUD[@]}" iam service-accounts describe "$INVOKER_SA" &>/dev/null; then
  log "Creating service account $INVOKER_SA..."
  "${GCLOUD[@]}" iam service-accounts create cron-invoker \
    --display-name="Cloud Scheduler invoker for RedCall crons"
fi

"${GCLOUD[@]}" run services add-iam-policy-binding "$SERVICE" --region="$REGION" \
  --member="serviceAccount:$INVOKER_SA" --role="roles/run.invoker" >/dev/null

create_job() {
  local name="$1" path="$2" schedule="$3"
  local verb="create"

  if "${GCLOUD[@]}" scheduler jobs describe "$name" --location="$REGION" &>/dev/null; then
    verb="update"
  fi

  log "$verb job $name ($schedule) -> $path"
  "${GCLOUD[@]}" scheduler jobs "$verb" http "$name" \
    --location="$REGION" \
    --schedule="$schedule" \
    --time-zone="Europe/Paris" \
    --uri="$SERVICE_URL$path" \
    --http-method=GET \
    --oidc-service-account-email="$INVOKER_SA" \
    --oidc-token-audience="$AUDIENCE" \
    --attempt-deadline=660s
}

create_job redcall-twilio-price         /cron/twilio-price         "0 * * * *"
create_job redcall-report-communication /cron/report-communication "30 * * * *"
create_job redcall-user-cron            /cron/user-cron            "0 2 * * *"
create_job redcall-clear-campaign       /cron/clear-campaign       "10 2 * * *"
create_job redcall-clear-media          /cron/clear-media          "20 2 * * *"
create_job redcall-clear-space          /cron/clear-space          "30 2 * * *"
create_job redcall-clear-expirable      /cron/clear-expirable      "40 2 * * *"
create_job redcall-sync-data            /cron/sync-data            "0 3 * * *"
create_job redcall-import-national      /cron/import-national      "0 4 * * *"

log "Scheduler jobs ready."
