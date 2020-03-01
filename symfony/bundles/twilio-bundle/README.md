# Twilio Bundle

Purpose of this bundle is to manage sending and receiving SMS in a way independent from your application logic. 

As SMS are expensive, we track everything by storing outbounds, inbounds, costs and delivery statuses. Your app is then free to use those information for business-related uses.

In order to stay KISS, this bundle does not handle cost optimizations (transliteration, character set filtering etc). 

## Send an SMS and keep a log of it

- Send an SMS through Twilio
- Store the message and its context for further use

## Track every SMS delivery status

- Sending SMS populates the statusCallback parameter
- When Twilio hits the given webhook, verify the request signature
- Store the message status for further uses
- Fire a symfony Event to notify of the new message status

## Get and store cost of all SMS sent

- Provides a command that you need to cron in order to store sms prices

## Receive SMS inbounds

- Verify the request signature
- Store the inbound message for further operations
- Fire a symfony Event to notify of the new sms inbound

## Get and store cost of all SMS inbounds

## Helpers

- Provides a command `twilio:sms` to send an sms to any phone number
 


Testing inbounds : ngrok http 127.0.0.1;8080





    /**
     * @Route(path="webhooks/twilio/inbound-sms", methods={"POST"})
     *
     * @return Response
     */
    public function twilioInboundSms(Request $request)
    {
        // see https://www.twilio.com/docs/libraries/php

        // use https://ngrok.com/ to do some tests locally

        // check X-Twilio-Signature (https://www.twilio.com/docs/usage/security#validating-requests)

        return new Response('<Response></Response>');
    }

    // send sms with statusCallback url in order to get sms delivery status

    // find a way to get sms and inbound prices

    // create a twilio-bundle to manage everything?