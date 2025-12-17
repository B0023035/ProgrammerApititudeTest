<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        {{-- ★★★ CSRFトークンを追加（app.tsが必要とする） ★★★ --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @if(app()->environment('production'))
            @php
                $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
                if ($manifest && isset($manifest['resources/js/app.ts'])) {
                    $appAsset = $manifest['resources/js/app.ts'];
                    echo '<script type="module" src="/build/'. $appAsset['file'] .'"></script>';
                    if (isset($appAsset['css'])) {
                        foreach ($appAsset['css'] as $css) {
                            echo '<link rel="stylesheet" href="/build/'. $css .'">';
                        }
                    }
                }
            @endphp
        @else
            @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @endif
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>