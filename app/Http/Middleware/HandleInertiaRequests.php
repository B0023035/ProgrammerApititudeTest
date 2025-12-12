<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

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
        // 適切なガードからユーザー情報を取得
        $adminUser = Auth::guard('admin')->user();
        $webUser = Auth::guard('web')->user();

        // CSRF トークンを常に新規に生成（ページ遷移ごとに更新）
        $csrfToken = $request->session()->token();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $adminUser ?? $webUser,
                'isAdmin' => $adminUser !== null,
            ],
            'csrf' => $csrfToken,
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ]);
    }
}
