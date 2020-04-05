#!/usr/bin/env bash

# Stop deployment script on errors
set -ex

ENV=$1
ROOTDIR=$(dirname "$0")/../

if [ ! -d "${ENV}" ]
then
  echo "'${ENV}' is not a valid environment."
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

    GREENLIGHT=`wget -O- ${WEBSITE_URL}/deploy`
    if [[ "${GREENLIGHT}" != "0" ]]
    then
      echo "A communication has recently been triggered, cannot deploy before ${GREENLIGHT} seconds"
      cp deploying/.env symfony/.env
      rm -r deploying
      exit 1
    fi

    gcloud config set project ${GCP_PROJECT_NAME}

    # Start Bastion
    gcloud compute instances start ${GCP_BASTION_INSTANCE}
    sleep 30

    # Start MySQL tunneling
    gcloud compute ssh ${USER}@${GCP_BASTION_INSTANCE} -- -L 3304:${DATABASE_HOST} -N -f

    # Run the migration
    php bin/console doctrine:migration:migrate --no-interaction

    # Clear up everything
    kill -9 `ps ax|grep 3304|grep google_compute_engine|grep -v grep|awk '{print $1;}'`
    gcloud compute instances stop ${GCP_BASTION_INSTANCE}
)

# Restoring current context
cp deploying/.env symfony/.env
rm -r deploying

