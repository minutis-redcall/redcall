<?php

namespace App\Services\InstancesNationales;

class LogService
{
    static private $debug     = [];
    static private $impactful = false;

    static public function info(string $message, array $parameters = [], bool $impactful = false) : int
    {
        return self::push(sprintf('%s %s', self::colorize(null), $message), $parameters, $impactful);
    }

    static private function push(string $message, array $parameters = [], bool $impactful = false) : int
    {
        self::$debug[] = [
            'message'    => sprintf('%s: %s', date('H:i:s'), $message),
            'parameters' => $parameters,
        ];

        if ($impactful) {
            self::$impactful = true;
        }

        return count(self::$debug);
    }

    static private function colorize(?bool $value) : string
    {
        if (null === $value) {
            return '⚫';
        }

        return $value ? '🟢' : '🔴';
    }

    static public function pass(string $message, array $parameters = [], bool $impactful = false) : int
    {
        return self::push(sprintf('%s %s', self::colorize(true), $message), $parameters, $impactful);
    }

    static public function fail(string $message, array $parameters = [], bool $impactful = false) : int
    {
        return self::push(sprintf('%s %s', self::colorize(false), $message), $parameters, $impactful);
    }

    static public function flush() : void
    {
        self::$debug = [];
    }

    static public function dump() : void
    {
        foreach (self::getFormattedDebug() as $message) {
            echo $message.PHP_EOL;
        }
    }

    static private function getFormattedDebug() : array
    {
        return array_map(function (array $data) {
            return sprintf(
                '%s%s',
                $data['message'],
                $data['parameters'] ? ' ('.json_encode($data['parameters'], JSON_UNESCAPED_UNICODE).')' : ''
            );
        }, self::$debug);
    }

    static public function isImpactful() : bool
    {
        return self::$impactful;
    }
}