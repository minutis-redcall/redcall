#!/usr/bin/env bash

# Stop deployment script on errors
set -ex

ENV=$1
ROOTDIR=$(dirname "$0")/../

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

# Generating Twig templates from MJML code
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

gcloud config set project ${GCP_PROJECT_NAME}
gcloud config set app/cloud_build_timeout 3600
yarn encore production
gcloud beta app deploy --verbosity debug --quiet --no-cache
cd ..

# Cron jobs
gcloud app deploy --quiet symfony/cron.yaml

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

VERSIONS=$(gcloud app versions list --service default --sort-by '~version' --format 'value(version.id)' | sort -r | tail -n +2)
if [ ${#VERSIONS} -gt 0 ]; then
  gcloud app versions delete --service default $VERSIONS -q
fi
