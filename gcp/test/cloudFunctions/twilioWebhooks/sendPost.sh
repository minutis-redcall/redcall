#!/usr/bin/env bash

#https://gist.github.com/subfuzion/08c5d85437d5d4f00e58

curl -w "%{http_code}" -H "Content-Type: application/json" -H "CustomHeader1: CustomHeaderValue1" -H "CustomHeader2: CustomHeaderValue2" \
  -d "@data-response.json" -X POST https://europe-west1-redcall-dev.cloudfunctions.net/webHooksToTasksSMSResponse

echo
echo

curl -w "%{http_code}" -H "Content-Type: application/json" -H "CustomHeader1: CustomHeaderValue1" -H "CustomHeader2: CustomHeaderValue2" \
  -d "@data-status.json" -X POST https://europe-west1-redcall-dev.cloudfunctions.net/webHooksToTasksSMSStatus
