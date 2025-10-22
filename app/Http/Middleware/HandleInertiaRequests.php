<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;
use Illuminate\Support\Facades\Auth;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        // 管理者エリアかどうかを判定（修正）
        $isAdminArea = $request->is('admin') || $request->is('admin/*');
        
        // 適切なガードからユーザー情報を取得
        $user = null;
        if ($isAdminArea) {
            $user = Auth::guard('admin')->user();
        } else {
            $user = Auth::guard('web')->user();
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user,
                'isAdmin' => $isAdminArea && $user !== null,
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ]);
    }
}