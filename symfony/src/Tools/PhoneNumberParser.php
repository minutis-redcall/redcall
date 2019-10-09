<?php

namespace App\Tools;

class PhoneNumberParser
{
    /**
     * @param string $phone
     *
     * @return null|string
     */
    static public function parse(string $phone) : ?string
    {
        $phone = ltrim(preg_replace('/[^0-9]/', '', $phone), 0);
        if (strlen($phone) == 9) {
            $phone = '33'.ltrim($phone, 0);
        }

        if (strlen($phone) != 11) {
            return null;
        }

        if (!in_array(substr($phone, 0, 3), ['336', '337'])) {
            return null;
        }

        return $phone;
    }
}