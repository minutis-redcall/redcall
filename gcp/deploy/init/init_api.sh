#!/usr/bin/env bash

#This scripts is to be run once per environments
# * enables the required APIs
# * create a service account and set the appropriate right to run the cloud functions
# * create the cloud tasks queues

ENV=$1
if  [[ "${ENV}1" != "preprod1" ]] && [[ "${ENV}1" != "prod1" ]]
then
  echo "'${ENV}' is not a valid environment. 'preprod' & 'prod' are allowed"
  exit 1
fi
source "../../../deploy/${ENV}/dotenv"

echo "initializing GCP Project : ${GCP_PROJECT_NAME}"

#Cloud Functions
echo "gcloud services enable cloudfunctions.googleapis.com"
gcloud services enable cloudfunctions.googleapis.com

#Cloud Tasks
echo "gcloud services enable cloudtasks.googleapis.com"
gcloud services enable cloudtasks.googleapis.com


#https://cloud.google.com/iam/docs/creating-managing-service-accounts#iam-service-accounts-create-gcloud
echo "Creating cf-twilio-webhook service account"
gcloud iam service-accounts create cf-twilio-webhook \
    --description="Service Account for the cloud function 'twilio-webhook' that recieve twilio webhook " \
    --display-name="Cloud Function twilio-webhook SA"
echo "Add role to service account: roles/appengine.appViewer"
gcloud projects add-iam-policy-binding "${GCP_PROJECT_NAME}" \
  --member "serviceAccount:cf-twilio-webhook@${GCP_PROJECT_NAME}.iam.gserviceaccount.com" \
  --role roles/cloudtasks.enqueuer

echo "Add role to service account: roles/appengine.appViewer"
gcloud projects add-iam-policy-binding "${GCP_PROJECT_NAME}" \
  --member "serviceAccount:cf-twilio-webhook@${GCP_PROJECT_NAME}.iam.gserviceaccount.com" \
  --role roles/appengine.appViewer

#https://cloud.google.com/tasks/docs/configuring-queues
gcloud tasks queues create webhook-sms-status \
    --max-dispatches-per-second=10 \
    --max-concurrent-dispatches=30 \
    --max-attempts=100 \
    --min-backoff=1s

gcloud tasks queues create webhook-sms-responses \
    --max-dispatches-per-second=10 \
    --max-concurrent-dispatches=30 \
    --max-attempts=100 \
    --min-backoff=1s
