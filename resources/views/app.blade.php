<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        @php
            $settings = \Illuminate\Support\Facades\Cache::rememberForever('app_settings', function () {
                return \App\Models\Setting::all()->pluck('value', 'key')->toArray();
            });
            $favicon = isset($settings['app_favicon']) && $settings['app_favicon'] 
                ? asset('storage/' . $settings['app_favicon']) 
                : asset('favicon.ico');
        @endphp
        <link rel="icon" type="image/x-icon" href="{{ $favicon }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        {{-- <script src="https://app.sandbox.xendit.co/snap/snap.js" data-client-key="{{ config('xendit.client_key') }}"></script> --}}
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
