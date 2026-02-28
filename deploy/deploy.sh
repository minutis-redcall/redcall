#!/usr/bin/env bash

# Stop deployment script on errors
set -ex

ENV=$1
ROOTDIR=$(dirname "$0")/../

# --- CONFIGURATION GCP ---
GCP_ACCOUNT="alain.tiemblo@croix-rouge.fr"

if [[ "${ENV}" == "prod" ]]; then
  GCP_PROJECT_NAME="redcall-prod-260921"
else
  GCP_PROJECT_NAME="redcall-dev"
fi

GCLOUD_OPTS="--project=${GCP_PROJECT_NAME} --account=${GCP_ACCOUNT}"
# -------------------------

if [ ! -d "${ENV}" ]; then
  echo "'${ENV}' is not a valid environment."
  exit 1
fi

# gcloud auth login

cd $ROOTDIR

#php symfony/bin/console phrase:sync --sleep=5 --create

# Backing up current context
rm -rf deploying
mkdir deploying
cp symfony/.env deploying/
cp symfony/config/keys/google-service-account.json deploying/

## Generating Twig templates from MJML code
php symfony/bin/console generate:mjml symfony/templates/message/email.html.twig.mjml
php symfony/bin/console generate:mjml symfony/templates/message/image.html.twig.mjml
php symfony/bin/console --env=prod cache:warmup

# Copying configuration files
cp deploy/${ENV}/app.yaml symfony/
cp deploy/${ENV}/dotenv symfony/.env
cp deploy/${ENV}/google-service-account.json symfony/config/keys
cp deploy/${ENV}/cron.yaml symfony/

# Deploying
cd symfony
source .env >/dev/null

#GREENLIGHT=$(wget -O- ${WEBSITE_URL}/deploy)
#if [[ "${GREENLIGHT}" != "0" ]]; then
#  echo "A communication has recently been triggered, cannot deploy before ${GREENLIGHT} seconds"
#  cd ..
#  cp deploying/.env symfony/.env
#  cp deploying/google-service-account.json symfony/config/keys/google-service-account.json
#  rm -r deploying
#  rm symfony/app.yaml
#  rm symfony/cron.yaml
#  exit 1
#fi

gcloud config set project ${GCP_PROJECT_NAME} ${GCLOUD_OPTS}
gcloud config set app/cloud_build_timeout 3600 ${GCLOUD_OPTS}
export NODE_OPTIONS=--openssl-legacy-provider
yarn encore production
gcloud beta app deploy --verbosity debug --quiet --no-cache ${GCLOUD_OPTS}
cd ..

# Cron jobs
gcloud app deploy --quiet symfony/cron.yaml ${GCLOUD_OPTS}

# Restoring current context
cp deploying/.env symfony/.env
cp deploying/google-service-account.json symfony/config/keys/google-service-account.json
rm -r deploying
rm symfony/app.yaml
rm symfony/cron.yaml

# Removing previous instance(s)
# In case a rollback may be necessary, we give a 5 mins grace
if [[ "${ENV}" = "prod" ]]; then
sleep 300
fi

VERSIONS=$(gcloud app versions list --service default --sort-by '~version' --format 'value(version.id)' ${GCLOUD_OPTS} | sort -r | tail -n +2)
if [ -n "${VERSIONS}" ]; then
  gcloud app versions delete --service default $VERSIONS -q ${GCLOUD
fi
