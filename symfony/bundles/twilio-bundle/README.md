# Twilio Bundle

Purpose of this bundle is to manage sending and receiving SMS in a way independent from your application logic. 

As SMS are expensive, we track everything by storing outbounds, inbounds, costs and delivery statuses. Your app is then free to use those information for business-related uses.

```
+----+--------------------------------------+-----------+-------------+-------------+-------------+------------------------------------+-----------+----------+------+---------+
| id | uuid                                 | direction | message     | from_number | to_number   | sid                                | status    | price    | unit | context |
+----+--------------------------------------+-----------+-------------+-------------+-------------+------------------------------------+-----------+----------+------+---------+
|  3 | 5e54a6a2-27db-448a-be4e-0b20199dc84d | outbound  | hello world | 33xxxxxxxxx | 33yyyyyyyyy | SMzjQJKmGYdz0cAf2rgymILNTv7PmhAPz7 | delivered | -0.07600 | USD  | []      |
|  4 | 7303e136-e832-481b-a38b-f1603db9ba4a | outbound  | hello world | 33xxxxxxxxx | 33yyyyyyyyy | SM4FFJqdViROSy2hI33cUJeOcx1qkbn9lV | queued    | -0.07600 | USD  | []      |
|  5 | e37abd25-b611-4bdb-99b1-85641892ff70 | outbound  | hello world | 33xxxxxxxxx | 33yyyyyyyyy | SMk9Sdf5JDMBp5GskUGns82y1Iw4o0hQQC | delivered | -0.07600 | USD  | []      |
|  6 | 6013617f-874f-4f44-9690-ec581d2f98fd | outbound  | hello world | 33xxxxxxxxx | 33yyyyyyyyy | SM7Ze1IZCMJkhi3pxV52UAhJy6385739m2 | delivered | -0.07600 | USD  | []      |
|  7 | 8c821e08-4a75-46a6-9459-e8fe10503b3f | outbound  | hello world | 33xxxxxxxxx | 33yyyyyyyyy | SM5ZFPrZYC9YA4COVmrmrWDtfQPX0XMtme | delivered | -0.07600 | USD  | []      |
|  8 | 0531a363-5dfa-4764-9365-6d638993ac02 | inbound   | Test        | 33yyyyyyyyy | 33xxxxxxxxx | SM5nAFUMkOFub9YuovkEbX0H5jBrJMETIw | receiving | -0.00750 | USD  | NULL    |
+----+--------------------------------------+-----------+-------------+-------------+-------------+------------------------------------+-----------+----------+------+---------+
```

In order to stay KISS, this bundle does not handle cost optimizations (transliteration, character set filtering etc). You should implement them by yourself.

## Installation

Set the following environment variables:

```
TWILIO_ACCOUNT_SID=your account sid
TWILIO_AUTH_TOKEN=your auth token
TWILIO_NUMBER=the phone number to send and receive messages
```

On Twilio website, to receive messages, add `<your website>/twilio/reply` in your "Phone Number"
configuration page (example: `https://d7185d61.ngrok.io/twilio/reply`).

## Send an SMS and keep a log of it

- Send an SMS through Twilio
- Store the message and its context for further use
- Fire a symfony Event to notify of the message sending

```
```

## Track every SMS delivery status

- This bundle manage delivery status webhook automatically.
- Store the delivery status for further uses
- Fire a symfony Event to notify of the new delivery status

```
```

## Get and store cost of all SMS sent

- Provides a command that you need to cron in order to store sms prices.
- Works for sms sent, but also sms received


```
```

## Receive SMS inbounds

- Store the inbound message for further operations
- Fire a symfony Event to notify of the new sms inbound

```
```

## Helpers

- Provides a command `twilio:sms` to send an sms to any phone number
