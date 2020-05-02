#!/usr/bin/env bash

#https://cloud.google.com/functions/docs/env-var

mkdir -p /tmp/twilioWebhooks/
rm -rf /tmp/twilioWebhooks/*
cp -r ../cloudFunctions/twilioWebhooks/* /tmp/twilioWebhooks/

sed -i '' -e "s/¤CloudFunctioName¤/webHooksToTasksSMSStatus/g"  /tmp/twilioWebhooks/index.js

cd /tmp/twilioWebhooks/ || exit

gcloud functions deploy webHooksToTasksSMSStatus \
  --service-account cf-twilio-webhook@redcall-dev.iam.gserviceaccount.com \
  --trigger-http \
  --allow-unauthenticated \
  --runtime nodejs10 \
  --region europe-west1 \
  --set-env-vars TASK_QUEUE_LOCATION=europe-west1,TASK_QUEUE_NAME=webhook-sms-status,PROJECT_ID=redcall-dev

cd - || exit
rm -rf /tmp/twilioWebhooks/*
cp -r ../cloudFunctions/twilioWebhooks/* /tmp/twilioWebhooks/
cd /tmp/twilioWebhooks/ || exit

sed -i '' -e "s/¤CloudFunctioName¤/webHooksToTasksSMSResponse/g"  /tmp/twilioWebhooks/index.js

gcloud functions deploy webHooksToTasksSMSResponse \
  --service-account cf-twilio-webhook@redcall-dev.iam.gserviceaccount.com \
  --trigger-http \
  --allow-unauthenticated \
  --runtime nodejs10 \
  --region europe-west1 \
  --set-env-vars TASK_QUEUE_LOCATION=europe-west1,TASK_QUEUE_NAME=webhook-sms-responses,PROJECT_ID=redcall-dev

cd - || exit

rm -rf /tmp/twilioWebhooks/
