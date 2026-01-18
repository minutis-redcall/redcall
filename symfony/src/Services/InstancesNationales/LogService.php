<?php

namespace App\Services\InstancesNationales;

class LogService
{
    static private $debug = [];
    static private $summary = [
        'new'     => [],
        'updated' => [],
        'deleted' => [],
        'errors'  => [],
    ];
    static private $impactful = 0;

    static public function info(string $message, array $parameters = [], bool $impactful = false) : int
    {
        return self::push(sprintf('%s %s', self::colorize(null), $message), $parameters, $impactful);
    }

    static public function success(string $type, string $message, array $parameters = []) : void
    {
        if (!isset(self::$summary[$type])) {
            $type = 'updated';
        }

        self::$summary[$type][] = [
            'message'    => $message,
            'parameters' => $parameters,
        ];

        self::push(sprintf('%s %s', self::colorize(true), $message), $parameters, true);
    }

    static public function error(string $message, array $parameters = []) : void
    {
        self::$summary['errors'][] = [
            'message'    => $message,
            'parameters' => $parameters,
        ];

        self::push(sprintf('%s %s', self::colorize(false), $message), $parameters, true);
    }

    static private function push(string $message, array $parameters = [], bool $impactful = false) : int
    {
        self::$debug[] = [
            'message'    => sprintf('%s: %s', date('H:i:s'), $message),
            'parameters' => $parameters,
        ];

        if ($impactful) {
            self::$impactful++;
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
        // Backward compatibility shim or generic pass
        return self::push(sprintf('%s %s', self::colorize(true), $message), $parameters, $impactful);
    }

    static public function fail(string $message, array $parameters = [], bool $impactful = false) : int
    {
        self::error($message, $parameters);

        return count(self::$debug);
    }

    static public function flush() : void
    {
        self::$debug = [];
        self::$summary = [
            'new'     => [],
            'updated' => [],
            'deleted' => [],
            'errors'  => [],
        ];
        self::$impactful = 0;
    }

    static public function dump(bool $return = false) : ?string
    {
        if ($return) {
            ob_start();
        }

        foreach (self::getFormattedDebug() as $message) {
            echo $message.PHP_EOL;
        }

        if ($return) {
            return ob_get_clean();
        }

        return null;
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
        return self::$impactful > 0;
    }

    static public function getNbImpacts() : int
    {
        return self::$impactful;
    }

    static public function getSummary() : array
    {
        return self::$summary;
    }
}