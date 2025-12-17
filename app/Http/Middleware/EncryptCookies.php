<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // XSRF-TOKEN と laravel_session を暗号化しない
        'XSRF-TOKEN',
        'laravel_session',
    ];
}