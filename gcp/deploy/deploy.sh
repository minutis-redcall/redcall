#!/usr/bin/env bash
#This script deploys the cloud function twice, one per endpoint (sms status, sms response)

#if GCP_PROJECT_NAME is defined, we're being called by the main deployment scripts and env vars are defined
if [ ! -d "${GCP_PROJECT_NAME}" ]
then
  ENV=$1
  if  [[ "${ENV}1" != "preprod1" ]] && [[ "${ENV}1" != "prod1" ]]
  then
    echo "'${ENV}' is not a valid environment. 'preprod' & 'prod' are allowed"
    exit 1
  fi

  source "../../deploy/${ENV}/dotenv"
fi

#https://cloud.google.com/functions/docs/env-var

mkdir -p /tmp/twilioWebhooks/
rm -rf /tmp/twilioWebhooks/*
cp -r ../cloudFunctions/twilioWebhooks/* /tmp/twilioWebhooks/

sed -i '' -e "s/¤CloudFunctioName¤/webHooksToTasksSMSStatus/g"  /tmp/twilioWebhooks/index.js

cd /tmp/twilioWebhooks/ || exit

gcloud functions deploy webHooksToTasksSMSStatus \
  --service-account "cf-twilio-webhook@${GCP_PROJECT_NAME}.iam.gserviceaccount.com" \
  --trigger-http \
  --allow-unauthenticated \
  --runtime nodejs10 \
  --region europe-west1 \
  --set-env-vars "TASK_QUEUE_LOCATION=europe-west1,TASK_QUEUE_NAME=webhook-sms-status,PROJECT_ID=${GCP_PROJECT_NAME}"

cd - || exit
rm -rf /tmp/twilioWebhooks/*
cp -r ../cloudFunctions/twilioWebhooks/* /tmp/twilioWebhooks/
cd /tmp/twilioWebhooks/ || exit

sed -i '' -e "s/¤CloudFunctioName¤/webHooksToTasksSMSResponse/g"  /tmp/twilioWebhooks/index.js

gcloud functions deploy webHooksToTasksSMSResponse \
  --service-account "cf-twilio-webhook@${GCP_PROJECT_NAME}.iam.gserviceaccount.com" \
  --trigger-http \
  --allow-unauthenticated \
  --runtime nodejs10 \
  --region europe-west1 \
  --set-env-vars "TASK_QUEUE_LOCATION=europe-west1,TASK_QUEUE_NAME=webhook-sms-responses,PROJECT_ID=${GCP_PROJECT_NAME}"

cd - || exit

rm -rf /tmp/twilioWebhooks/
