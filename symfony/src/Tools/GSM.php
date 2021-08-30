<?php

namespace App\Tools;

class GSM
{
    const ALPHABET = [
        'A',
        'B',
        'C',
        'D',
        'E',
        'F',
        'G',
        'H',
        'I',
        'J',
        'K',
        'L',
        'M',
        'N',
        'O',
        'P',
        'Q',
        'R',
        'S',
        'T',
        'U',
        'V',
        'W',
        'X',
        'Y',
        'Z',
        'a',
        'b',
        'c',
        'd',
        'e',
        'f',
        'g',
        'h',
        'i',
        'j',
        'k',
        'l',
        'm',
        'n',
        'o',
        'p',
        'q',
        'r',
        's',
        't',
        'u',
        'v',
        'w',
        'x',
        'y',
        'z',
        '0',
        '1',
        '2',
        '3',
        '4',
        '5',
        '6',
        '7',
        '8',
        '9',
        '!',
        '#',
        ' ',
        '"',
        '%',
        '&',
        '\'',
        '(',
        ')',
        '*',
        ',',
        '.',
        '?',
        '+',
        '-',
        '/',
        ';',
        ':',
        '<',
        '=',
        '>',
        '¡',
        '¿',
        '_',
        '@',
        '§',
        '$',
        '£',
        '¥',
        'è',
        'é',
        'ù',
        'ì',
        'ò',
        'Ç',
        'Ø',
        'ø',
        'Æ',
        'æ',
        'ß',
        'É',
        'Å',
        'å',
        'Ä',
        'Ö',
        'Ñ',
        'Ü',
        'ä',
        'ö',
        'ñ',
        'ü',
        'à',
        "\n",
        // On iOS v15, \r\n[ = Ä and ]\r\n = Ñ
        // "\r",
        'Δ',
        'Φ',
        'Γ',
        'Λ',
        'Ω',
        'Π',
        'Ψ',
        'Σ',
        'Θ',
        'Ξ',
        '¤',
        '€',
        '[',
        ']',
        '{',
        '}',
        '\\',
        '^',
        '~',
        '|',
    ];

    const ESCAPED = [
        '€',
        '[',
        ']',
        '{',
        '}',
        '\\',
        '^',
        '~',
        '|',
    ];

    const TRANSLITERATION = [
        '/[ \t]{2,}/'                         => ' ',
        '/\r\n/'                              => "\n",
        '/ /'                                 => ' ',
        '/–|—/'                               => '-',
        '/₹/'                                 => 'Rs',
        '/₴/'                                 => 'UAH',
        '/₽/'                                 => 'p',
        '/·/'                                 => '.',
        '/ѣ|Ѣ|́|Ь|ь|Ъ|ъ/'                     => '',
        '/º|°/'                               => 0,
        '/¹/'                                 => 1,
        '/²/'                                 => 2,
        '/³/'                                 => 3,
        '/ǽ/'                                 => 'ae',
        '/œ/'                                 => 'oe',
        '/À|Á|Â|Ã|Ǻ|Ā|Ă|Ą|Ǎ|А|Α/'             => 'A',
        '/á|â|ã|ǻ|ā|ă|ą|ǎ|ª|а/'               => 'a',
        '/Б/'                                 => 'B',
        '/б/'                                 => 'b',
        '/Ç|Ć|Ĉ|Ċ|Č|Ћ/'                       => 'C',
        '/ç|ć|ĉ|ċ|č|ћ/'                       => 'c',
        '/Д/'                                 => 'D',
        '/д/'                                 => 'd',
        '/Ð|Ď|Đ|Ђ/'                           => 'Dj',
        '/ð|ď|đ|ђ/'                           => 'dj',
        '/È|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě|Е|Ё|ЬЭ|Э|Є|Ѧ|Ễ/'    => 'E',
        '/ê|ë|ē|ĕ|ė|ę|ě|е|ё|ьэ|э|є|ѧ|ə|ɘ|ễ/'  => 'e',
        '/Ф/'                                 => 'F',
        '/ƒ|ф/'                               => 'f',
        '/Ĝ|Ğ|Ġ|Ģ|Г|Ґ/'                       => 'G',
        '/ĝ|ğ|ġ|ģ|г|ґ/'                       => 'g',
        '/Ĥ|Ħ/'                               => 'H',
        '/ĥ|ħ/'                               => 'h',
        '/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ|И|Й|І/'         => 'I',
        '/í|î|ï|ĩ|ī|ĭ|ǐ|į|ı|и|й|і/'           => 'i',
        '/Ĵ/'                                 => 'J',
        '/ĵ/'                                 => 'j',
        '/Ķ|К/'                               => 'K',
        '/ķ|к/'                               => 'k',
        '/Х/'                                 => 'Kh',
        '/х/'                                 => 'kh',
        '/Ĺ|Ļ|Ľ|Ŀ|Ł|Л/'                       => 'L',
        '/ĺ|ļ|ľ|ŀ|ł|л/'                       => 'l',
        '/М/'                                 => 'M',
        '/м/'                                 => 'm',
        '/Ń|Ņ|Ň|Н|№/'                         => 'N',
        '/ń|ņ|ň|ŉ|н/'                         => 'n',
        '/Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ǿ|О|Ѡ|Ѫ|Ờ/'       => 'O',
        '/ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ǿ|º|о|ѡ|ѫ|ờ/'       => 'o',
        '/П/'                                 => 'P',
        '/п/'                                 => 'p',
        '/Ŕ|Ŗ|Ř|Р/'                           => 'R',
        '/ŕ|ŗ|ř|р/'                           => 'r',
        '/Ś|Ŝ|Ş|Ș|Š|С/'                       => 'S',
        '/ś|ŝ|ş|ș|š|ſ|с/'                     => 's',
        '/Ţ|Ț|Ť|Ŧ|Т/'                         => 'T',
        '/ţ|ț|ť|ŧ|т/'                         => 't',
        '/Ц/'                                 => 'Tc',
        '/ц/'                                 => 'tc',
        '/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ|У|Ў/' => 'U',
        '/ú|û|ũ|ū|ŭ|ů|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ|у|ў/'   => 'u',
        '/В/'                                 => 'V',
        '/в/'                                 => 'v',
        '/Ý|Ÿ|Ŷ|Ỳ|Ы/'                         => 'Y',
        '/ý|ÿ|ŷ|ỳ|ы/'                         => 'y',
        '/Ŵ/'                                 => 'W',
        '/ŵ/'                                 => 'w',
        '/Ź|Ż|Ž|З/'                           => 'Z',
        '/ź|ż|ž|з/'                           => 'z',
        '/Ǽ/'                                 => 'AE',
        '/Ĳ/'                                 => 'IJ',
        '/ĳ/'                                 => 'ij',
        '/Œ/'                                 => 'OE',
        '/Ч/'                                 => 'Ch',
        '/ч/'                                 => 'ch',
        '/Ю/'                                 => 'Iu',
        '/ю/'                                 => 'iu',
        '/Я/'                                 => 'Ia',
        '/я/'                                 => 'ia',
        '/Ї/'                                 => 'Ji',
        '/ї/'                                 => 'ji',
        '/Ш/'                                 => 'Sh',
        '/ш/'                                 => 'sh',
        '/Щ/'                                 => 'Shch',
        '/щ/'                                 => 'shch',
        '/Ж/'                                 => 'Zh',
        '/ж/'                                 => 'zh',
        '/ѕ|џ/'                               => 'dz',
        '/Ѕ|Џ/'                               => 'Dz',
        '/ј/'                                 => 'j',
        '/љ/'                                 => 'lj',
        '/Љ/'                                 => 'Lj',
        '/њ/'                                 => 'nj',
        '/Њ/'                                 => 'Nj',
        '/ќ/'                                 => 'kj',
        '/Ќ/'                                 => 'Kj',
        '/ѩ/'                                 => 'je',
        '/Ѩ/'                                 => 'Je',
        '/ѭ/'                                 => 'jo',
        '/Ѭ/'                                 => 'Jo',
        '/ѯ/'                                 => 'ks',
        '/Ѯ/'                                 => 'Ks',
        '/ѱ/'                                 => 'ps',
        '/Ѱ/'                                 => 'Ps',
        '/ѥ/'                                 => 'je',
        '/Ѥ/'                                 => 'Je',
        '/ꙗ/'                                 => 'ja',
        '/Ꙗ/'                                 => 'ja',
        '/«|»/'                               => '"',
        '/’|`/'                               => '\'',
    ];

    /**
     * @param string $message
     *
     * @return string
     */
    public static function isGSMCompatible(string $message) : string
    {
        foreach (preg_split('//u', $message, null, PREG_SPLIT_NO_EMPTY) as $letter) {
            if (!in_array($letter, self::ALPHABET)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $message
     *
     * @return string
     */
    public static function transliterate(string $message) : string
    {
        return trim(preg_replace(
            array_keys(self::TRANSLITERATION),
            array_values(self::TRANSLITERATION),
            $message
        ));
    }

    /**
     * @param string $message
     *
     * @return string
     */
    public static function enforceGSMAlphabet(string $message) : string
    {
        $sanitized = '';
        foreach (preg_split('//u', self::transliterate($message), null, PREG_SPLIT_NO_EMPTY) as $letter) {
            if (!in_array($letter, self::ALPHABET)) {
                $sanitized .= '?';
            } else {
                $sanitized .= $letter;
            }
        }

        return $sanitized;
    }

    /**
     * @param string $message
     *
     * @return array
     */
    public static function getSMSParts(string $message) : array
    {
        $unicode = false;
        if (!self::isGSMCompatible($message)) {
            $unicode = true;
        }

        $length = 0;
        foreach (preg_split('//u', $message, null, PREG_SPLIT_NO_EMPTY) as $letter) {
            if (!$unicode && in_array($letter, self::ESCAPED)) {
                $length += 1;
            }

            $length++;
        }

        $multipart = false;
        if ((!$unicode && $length > 160) || ($unicode && $length > 70)) {
            $multipart = true;
        }

        if (!$multipart) {
            return [$message];
        }

        $parts  = [];
        $part   = '';
        $length = 0;
        foreach (preg_split('//u', $message, null, PREG_SPLIT_NO_EMPTY) as $letter) {
            if (!$unicode && in_array($letter, self::ESCAPED)) {
                if ($length == 152) {
                    $parts[] = $part;
                    $part    = '';
                    $length  = 0;
                }
                $part   .= '-'; // Escaping character
                $length += 1;
            }

            if ((!$unicode && $length == 153) || ($unicode && $length == 67)) {
                $parts[] = $part;
                $part    = '';
                $length  = 0;
            }

            $part   .= $letter;
            $length += 1;
        }

        if ($part) {
            $parts[] = $part;
        }

        return $parts;
    }
}