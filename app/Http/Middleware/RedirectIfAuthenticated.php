<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // 管理者ガードの場合
                if ($guard === 'admin') {
                    return redirect()->route('admin.dashboard');
                }
                
                // 一般ユーザーの場合
                return redirect('/test-start');
            }
        }

        return $next($request);
    }
}