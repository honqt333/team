<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="no-js">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title inertia>{{ app()->getLocale() === 'ar' ? config('app.name_ar', 'خدمة برو') : config('app.name', 'Khidmh Pro') }}</title>
    <link rel="icon" type="image/png" href="/images/logo.png">

    <!-- Inline Dark Mode Check -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!--
        Remove the `no-js` marker as soon as JavaScript runs.

        The landing page hides below-the-fold sections with `opacity: 0` and
        reveals them with an IntersectionObserver. Without this class flip,
        a JavaScript failure would leave the page permanently blank below the
        hero.
    -->
    <script>
        document.documentElement.classList.remove('no-js');
    </script>

    <!--
        Fonts.

        Self-hosted IBM Plex Sans Arabic with 4 weights, split into an Arabic
        and a Latin subset so each page downloads only the script it needs.
        This replaces a Google Fonts stylesheet that pulled 5 weights from a
        third-party origin and blocked the first paint.
    -->
    <link
        rel="preload"
        as="font"
        type="font/woff2"
        href="/fonts/ibm-plex-sans-arabic-arabic-400.woff2"
        crossorigin
    >
    <link
        rel="preload"
        as="font"
        type="font/woff2"
        href="/fonts/ibm-plex-sans-arabic-latin-400.woff2"
        crossorigin
    >
    <link rel="stylesheet" href="/css/fonts.css">

    <!--
        Structured data (JSON-LD).

        Rendered SERVER-SIDE rather than through the page component's
        <Head> block. Two reasons:

          1. Inertia's Head renders a `<script>` whose content comes from a
             `v-text` binding as an EMPTY element — the binding is applied
             by Vue at mount time, but Inertia serialises the tag before
             that. The structured data simply never reached the DOM.
          2. Server-rendered JSON-LD is in the document at first paint.
             A crawler that does not execute JavaScript still sees it.

        The graph itself is built by the controller (PublicLandingController
        and TenantSiteController) so it cannot drift from the data the page
        actually renders.
    -->
    @php
        // The landing page exposes the graph as a top-level prop; the tenant
        // public site nests it inside `site`. Support both shapes so the
        // structured data is present whichever page is being rendered.
        $jsonLd = $page['props']['jsonLd'] ?? ($page['props']['site']['json_ld'] ?? null);
    @endphp
    @if (! empty($jsonLd))
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif

    <!-- Scripts -->
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>

<body class="font-sans antialiased">
    @inertia
</body>

</html>
