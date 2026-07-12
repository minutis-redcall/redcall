#!/usr/bin/env bash

set -euo pipefail

# ─── Configuration ────────────────────────────────────────────────────────────

GCP_ACCOUNT="alain.tiemblo@croix-rouge.fr"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
SERVICE="redcall"
REGION="europe-west1"
AR_REPO="redcall"

log()   { echo "==> $*"; }
error() { echo "ERROR: $*" >&2; }

# ─── Validate arguments ──────────────────────────────────────────────────────

ENV="${1:-}"

if [[ -z "$ENV" ]]; then
  echo "Usage: $0 <prod|preprod>"
  exit 1
fi

case "$ENV" in
  prod)
    GCP_PROJECT="redcall-prod-260921"
    VPC_CONNECTOR="gae-serverless-conn-prod"
    ;;
  preprod)
    GCP_PROJECT="redcall-dev"
    VPC_CONNECTOR="serverless-connector"
    ;;
  *)
    error "No GCP project configured for environment '$ENV'."
    exit 1
    ;;
esac

for file in "$SCRIPT_DIR/$ENV/dotenv" "$SCRIPT_DIR/$ENV/google-service-account.json"; do
  if [[ ! -f "$file" ]]; then
    error "Missing deploy config: $file"
    exit 1
  fi
done

GCLOUD=(gcloud --project="$GCP_PROJECT" --account="$GCP_ACCOUNT")

# ─── Build via Cloud Build ────────────────────────────────────────────────────

TAG="$(git -C "$ROOT_DIR" rev-parse --short HEAD)-$(date +%Y%m%d%H%M%S)"
IMAGE="$REGION-docker.pkg.dev/$GCP_PROJECT/$AR_REPO/$SERVICE:$TAG"

if ! "${GCLOUD[@]}" artifacts repositories describe "$AR_REPO" --location="$REGION" &>/dev/null; then
  log "Creating Artifact Registry repository '$AR_REPO'..."
  "${GCLOUD[@]}" artifacts repositories create "$AR_REPO" \
    --location="$REGION" --repository-format=docker
fi

log "Building $IMAGE with Cloud Build..."
"${GCLOUD[@]}" builds submit "$ROOT_DIR" \
  --config="$SCRIPT_DIR/cloudrun/cloudbuild.yaml" \
  --substitutions="_ENV=$ENV,_IMAGE=$IMAGE"

# ─── Deploy to Cloud Run ──────────────────────────────────────────────────────

log "Deploying $SERVICE to Cloud Run ($GCP_PROJECT)..."
"${GCLOUD[@]}" run deploy "$SERVICE" \
  --image="$IMAGE" \
  --region="$REGION" \
  --platform=managed \
  --vpc-connector="$VPC_CONNECTOR" \
  --concurrency=10 \
  --min-instances=0 \
  --max-instances=10 \
  --memory=1Gi \
  --cpu=1 \
  --timeout=600 \
  --port=8080 \
  --allow-unauthenticated

URL="$("${GCLOUD[@]}" run services describe "$SERVICE" --region="$REGION" --format='value(status.url)')"
log "Deployed: $URL"
