<?php

namespace App\Services\InstancesNationales;

class LogService
{
    static private $debug = [];

    static public function info(string $message, array $parameters = []) : int
    {
        return self::push(sprintf('%s %s', self::colorize(null), $message), $parameters);
    }

    static public function pass(string $message, array $parameters = []) : int
    {
        return self::push(sprintf('%s %s', self::colorize(true), $message), $parameters);
    }

    static public function fail(string $message, array $parameters = []) : int
    {
        return self::push(sprintf('%s %s', self::colorize(false), $message), $parameters);
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

    static private function push(string $message, array $parameters = []) : int
    {
        self::$debug[] = [
            'message'    => sprintf('%s: %s', date('H:i:s'), $message),
            'parameters' => $parameters,
        ];

        return count(self::$debug);
    }

    static private function colorize(?bool $value) : string
    {
        if (null === $value) {
            return '⚫';
        }

        return $value ? '🟢' : '🔴';
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
}