<?php

namespace App\Tools;

use App\Entity\Phone;

class PhoneNumber
{
    static public function getSmsSender(?Phone $to) : string
    {
        return self::getSender($to, 'SENDER_SMS');
    }

    static public function getCallSender(?Phone $to) : string
    {
        return self::getSender($to, 'SENDER_CALL');
    }

    static public function listAllNumbers() : array
    {
        $sms   = json_decode(getenv('SENDER_SMS'), true);
        $calls = json_decode(getenv('SENDER_CALL'), true);

        $phones = [];
        foreach ($sms as $country => $phone) {
            $phones[] = $phone;
        }
        foreach ($calls as $country => $phone) {
            $phones[] = $phone;
        }

        return array_unique($phones);
    }


    static private function getSender(?Phone $to, string $key)
    {
        $config = json_decode(getenv($key), true);

        if ($to && $from = $config[$to->getCountryCode()] ?? false) {
            return $from;
        }

        return $config['default'];
    }
}