<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    protected $proxies = '*';

    // Render / AWS ELB 環境ではこれが最適
    protected $headers = Request::HEADER_X_FORWARDED_PROTO;
}
