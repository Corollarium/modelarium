<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" prefix="og: http://ogp.me/ns#">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{csrf_token()}}">
        <title>{{ config('app.name') }}</title>
        {{head}}
    </head>
    <body class="mode-{{ env('APP_ENV') }}">
        <div id="app">
            @if (isset($vuecomponent))
                <component is="{{ $vuecomponent }}"></component>
            @endif
        </div>
        <script src="{{ mix('js/app.js') }}"></script>
    </body>
</html>
