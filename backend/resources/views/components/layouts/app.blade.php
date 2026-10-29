@props(['title' => null, 'branchSelect' => false])
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title . ' - ' : '' }}{{ config('app.name') }}</title>
    <link rel="stylesheet" href="@assetv('css/app.css')">
    @stack('styles')
</head>

<body class="dash" data-idle-minutes="{{ config('session.lifetime') }}">
    <x-navbar :branch-select="$branchSelect" />

    <main class="wrap">
        {{ $slot }}
    </main>

    <x-footer />

    <x-ui.confirm-dialog />
    <x-ui.idle-dialog />
    <div class="toasts" id="toasts" aria-live="polite"></div>

    <script src="@assetv('js/layout.js')" defer></script>
    <x-ui.flash />
    @stack('scripts')
</body>

</html>
