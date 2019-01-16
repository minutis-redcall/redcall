<?php

namespace App\Component\HttpFoundation;

use Symfony\Component\HttpFoundation\StreamedResponse;

class EventStreamResponse extends StreamedResponse
{
    public function __construct(callable $callback, $status = 200, array $headers = [], int $ttl = 1)
    {
        parent::__construct(function () use ($callback, $ttl) {
            while (true) {

                if ($return = $callback()) {
                    echo sprintf("data:%s\n\n", $return);
                    ob_flush();
                    flush();
                }

                sleep($ttl);
            }
        }, $status, array_merge($headers, [
            'Content-Type'  => 'text/event-stream',
            'Cache-Control' => 'no-cache',
        ]));
    }
}