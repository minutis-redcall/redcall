#!/usr/bin/env bash

# Stop deployment script on errors
set -ex

ENV=$1
ROOTDIR=$(dirname "$0")/../

if [[ "${ENV}" != "preprod" ]] && [[ "${ENV}" != "prod" ]]
then
  echo "'${ENV}' is not a valid environment. Valid values are ['preprod', 'prod']"
  exit 1
fi

# gcloud auth login

cd $ROOTDIR

# Backing up current context
rm -rf deploying
mkdir deploying
cp symfony/.env deploying/
cp symfony/config/keys/google-service-account.json deploying/

# Copying configuration files
cp deploy/${ENV}/app.yaml symfony/
cp deploy/${ENV}/dotenv symfony/.env
cp deploy/${ENV}/google-service-account.json symfony/config/keys

# Deploying
cd symfony

source .env
gcloud config set project ${GCP_PROJECT_NAME}
gcloud config set app/cloud_build_timeout 3600
yarn encore production
gcloud beta app deploy --verbosity debug
cd ..

# Cron jobs
gcloud app deploy --quiet symfony/cron.yaml

# Restoring current context
cp deploying/.env symfony/.env
cp deploying/google-service-account.json symfony/config/keys/google-service-account.json
rm -r deploying
rm symfony/app.yaml
