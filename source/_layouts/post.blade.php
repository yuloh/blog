@extends('_layouts.master')

@push('meta')
    <meta property="og:title" content="{{ $page->title }}" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="{{ $page->getUrl() }}"/>
    <meta property="og:description" content="{{ $page->description }}" />
@endpush

@section('body')
    <div class="mw7 ph1 center">
        <article>
            <header>
                <h1>{{ $page->title }}</h1>
                <time class="f6 gray" datetime="{{ $page->getDate()->format(DateTime::ATOM) }}">{{ $page->getDate()->format('F j, Y') }}</time>
                 @foreach ($page->tags as $tag)
                    <a class="f6 gray link hover-blue" href="{{ $page->baseUrl }}/tags/{{ $tag }}">#{{ $tag }}</a>
                @endforeach
            </header>
            <section class="lh-copy f4">
                @yield('content')
            </section>
        </article>
    </div>

    <div class="flex items-center justify-center pa4">
        @if ($previous = $page->getPrevious())
            <a href="{{ $previous->getUrl() }}" class="f5 no-underline black link hover-blue inline-flex items-center pa3 mr4">
                <svg class="w1" data-icon="chevronLeft" viewBox="0 0 32 32" style="fill:currentcolor">
                    <title>chevronLeft icon</title>
                    <path d="M20 1 L24 5 L14 16 L24 27 L20 31 L6 16 z"></path>
                </svg>
                <span class="pl1">Previous</span>
            </a>
        @endif
        @if ($next = $page->getNext())
            <a href="{{ $next->getUrl() }}" class="f5 no-underline black link hover-blue inline-flex items-center pa3">
                <span class="pr1">Next</span>
                <svg class="w1" data-icon="chevronRight" viewBox="0 0 32 32" style="fill:currentcolor">
                    <title>chevronRight icon</title>
                    <path d="M12 1 L26 16 L12 31 L8 27 L18 16 L8 5 z"></path>
                </svg>
            </a>
        @endif
    </div>
@endsection
