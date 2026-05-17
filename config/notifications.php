<?php

return [

    /*
    |--------------------------------------------------------------------------
    | External provider (webhook.site mock)
    |--------------------------------------------------------------------------
    */

    'webhook_url' => env('WEBHOOK_PROVIDER_URL'),
    'timeout' => (int) env('WEBHOOK_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Per-channel rate limits (messages per second)
    |--------------------------------------------------------------------------
    |
    | Used by ProcessNotification to throttle outbound calls. The limiter is
    | a Redis-backed token bucket — the count is shared across every worker
    | process / container, so scaling out workers does NOT scale up the rate.
    |
    | Set `null` to disable rate limiting for a specific channel.
    |
    */

    'rate_limits' => [
        'sms' => (int) env('RATE_LIMIT_SMS', 100),
        'email' => (int) env('RATE_LIMIT_EMAIL', 100),
        'push' => (int) env('RATE_LIMIT_PUSH', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry policy (seconds between attempts)
    |--------------------------------------------------------------------------
    | Read by ProcessNotification::$backoff via this config if you want to
    | move it out of the class. Currently the class declares its own copy.
    */

    'retry' => [
        'max_attempts' => 5,
        'backoff' => [10, 30, 90, 300, 900],
    ],

];
