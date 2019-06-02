
## Configure Nexmo

Directories [prod](../../deploy/prod) and [preprod](../../deploy/preprod) 
contain a `dotenv` file that you need to fill up with the right configuration.

RedCall aims to call for volunteer's availability in case of emergency. SMS
is the fastest way to go and there are no need for an internet connection.

**Objective**:
We are using Nexmo because they provide a phone number that can be used
for inbound messages (volunteer can basically reply to questions by
writting another text message to the phone number directly).

Not a lot of details will be given here, because I don't have the proper
accesses to our current account.

1. Create Nexmo API key and secret

2. Create an inbound phone number in order to receive replies

3. Configure webhooks:

- Deliery receipts : https://preprod.redcall.io/webhooks/delivery-receipt

- Inbound SMS (replies) : https://preprod.redcall.io/webhooks/inbound-sms

### Project configuration

Set `NEXMO_API_KEY` and `NEXMO_API_SECRET` with your nexmo key and secret,
and `NEXMO_SEND_FROM` by the phone number that have been attributed from
Nexmo to redcall (in order to let people answer).

[Go back](../../README.md)
