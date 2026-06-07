#!/usr/bin/env bash

# Create the Google Cloud Tasks queues used by the daily CSV sync pipeline.
#  - sync-start:    one task per run, builds the chunks
#  - sync-chunk:    structure + volunteer chunks, the bulk of the work
#  - sync-finalize: one task per run, post-sync reconciliation
#
# Idempotent — uses `gcloud tasks queues describe` to skip queues that already
# exist. Run once per environment (preprod, prod).

set -euo pipefail

ENV="${1:-}"
if [[ "${ENV}" != "preprod" && "${ENV}" != "prod" ]]; then
  echo "Usage: $0 <preprod|prod>"
  exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOTENV_PATH="${SCRIPT_DIR}/../../../deploy/${ENV}/dotenv"

if [[ ! -f "${DOTENV_PATH}" ]]; then
  echo "Cannot find dotenv file at ${DOTENV_PATH}"
  exit 1
fi

# shellcheck disable=SC1090
source "${DOTENV_PATH}"

if [[ -z "${GCP_PROJECT_NAME:-}" ]]; then
  echo "GCP_PROJECT_NAME is not set after sourcing ${DOTENV_PATH}"
  exit 1
fi

echo "Target project: ${GCP_PROJECT_NAME}"

ensure_queue() {
  local NAME="$1"
  shift
  if gcloud tasks queues describe "${NAME}" --project="${GCP_PROJECT_NAME}" >/dev/null 2>&1; then
    echo "Queue ${NAME} already exists, skipping."
    return
  fi
  echo "Creating queue ${NAME}..."
  gcloud tasks queues create "${NAME}" --project="${GCP_PROJECT_NAME}" "$@"
}

# https://cloud.google.com/tasks/docs/configuring-queues
ensure_queue sync-start \
  --max-dispatches-per-second=1 \
  --max-concurrent-dispatches=1 \
  --max-attempts=3 \
  --min-backoff=10s

ensure_queue sync-chunk \
  --max-dispatches-per-second=10 \
  --max-concurrent-dispatches=30 \
  --max-attempts=3 \
  --min-backoff=5s

ensure_queue sync-finalize \
  --max-dispatches-per-second=1 \
  --max-concurrent-dispatches=1 \
  --max-attempts=3 \
  --min-backoff=10s

echo "All sync queues are ready."
