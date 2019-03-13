<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="description" content="{{ $page->meta_description ?? $page->siteDescription }}">

        <meta property="og:title" content="{{ $page->title ?  $page->title . ' | ' : '' }}{{ $page->siteName }}"/>
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{{ $page->getUrl() }}"/>
        <meta property="og:description" content="{{ $page->siteDescription }}" />

        <title>{{ $page->siteName }}{{ $page->title ? ' | ' . $page->title : '' }}</title>

        <link rel="home" href="{{ $page->baseUrl }}">
        <link href="/posts/feed.atom" type="application/atom+xml" rel="alternate" title="{{ $page->siteName }} Atom Feed">

        @stack('meta')

        <link rel="stylesheet" href="{{ $page->baseUrl }}/assets/css/tachyons.min.css">
        <link rel="stylesheet" href="{{ $page->baseUrl }}/assets/css/main.css">
    </head>

    <body>
        <header role="banner" class="flex flex-wrap items-center shadow-1">
            <div class="inline-flex items-center pa2">
                <a href="/" title="{{ $page->siteName }} home">
                    <img src="/assets/images/logo.png" alt="{{ $page->siteName }} logo" class="h3 ph2" />
                </a>
                <h1>
                    <a class="f4 f3-ns fw6 black-80" href="/" title="{{ $page->siteName }} home">{{ $page->siteName }}</a>
                </h1>
            </div>

            <nav class="flex flex-auto items-center justify-center justify-end-ns">
                <a title="{{ $page->siteName }} About" href="/about" class="sans-serif f5 link black-70 hover-blue ttu fw6 pr1 {{ $page->isActive('/about') ? 'blue' : '' }}">About</a>
            </nav>
        </header>

        <main role="main">
            @yield('body')
        </main>

        <footer role="contentinfo">
           {{-- Social links, built with etc --}}
        </footer>

        @stack('scripts')
    </body>
</html>
