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

cd $ROOTDIR

# Backing up current context
rm -rf deploying
mkdir deploying
cp symfony/.env deploying/

# Copying configuration files
cat deploy/${ENV}/dotenv | grep -v DATABASE_URL > symfony/.env
cat deploy/${ENV}/dotenv-migrate >> symfony/.env

# Migrating
(
    cd symfony
    source .env > /dev/null

    gcloud config set project ${GCP_PROJECT_NAME}

    # Start Bastion
    gcloud compute instances start ${GCP_BASTION_INSTANCE}
    sleep 30

    # Start MySQL tunneling
    gcloud compute ssh ${USER}@${GCP_BASTION_INSTANCE} -- -L 3304:${DATABASE_HOST} -N -f
)

# Restoring current context
cp deploying/.env symfony/.env
rm -r deploying
