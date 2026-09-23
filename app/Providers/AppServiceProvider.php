<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** Quantas tentativas por minuto cada ação sensível aceita. */
    private const LIMITS = [
        'login' => 8,
        'register' => 5,
        'invite' => 5,
        'join' => 10,
        'password' => 5,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // O `throttle:N,M` genérico identifica visitantes apenas por domínio e IP, então
        // login e cadastro dividiriam o mesmo contador: gastar um consumiria o outro, e
        // um escritório atrás de um único IP travaria com poucas tentativas legítimas.
        // Cada ação recebe um contador próprio, por conta autenticada ou por IP.
        foreach (self::LIMITS as $action => $perMinute) {
            RateLimiter::for($action, fn (Request $request) => Limit::perMinute($perMinute)
                ->by($action.'|'.($request->user()?->getAuthIdentifier() ?? $request->ip())));
        }
    }
}
