<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Questionnaire - NMU Questionnaire System">
    <meta name="author" content="Khaled Zahran">
    <meta name="keywords" content="Nmu, Questionnaire, NMU Questionnaire System, Questionnaire System">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('Questionnaire - NMU Questionnaire System') }}</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/images/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/images/favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('assets/images/favicon/site.webmanifest') }}">

    <!-- Styles -->
    <x-layouts.partials.styles />
</head>
<body class="vertical-layout">
    <!-- Start Containerbar -->
    <div id="containerbar">
        <!-- Start Leftbar -->
        <div class="leftbar">
            <x-layouts.sidebar />
        </div>
        <!-- End Leftbar -->
        <!-- Start Rightbar -->
        <div class="rightbar">
        <x-layouts.top-bar-mobile />
        <x-layouts.topbar :user="auth()->user() ? auth()->user()->toArray() : null" :notifications="[]" />
        @if (View::hasSection('breadcrumb'))
                @yield('breadcrumb')
            @endif

            <div class="contentbar">
                @yield('content')
            </div>

            <x-layouts.footer name="{{ config('app.name', 'Laravel') }}" />
        </div>
        <!-- End Rightbar -->
    </div>
    <!-- End Containerbar -->

    <!-- Scripts -->
    <x-layouts.partials.scripts />
</body>
</html>
