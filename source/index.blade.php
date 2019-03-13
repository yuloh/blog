---
pagination:
    collection: posts
    perPage: 4
---
@extends('_layouts.master')

@section('body')
    <div class="mw8 ph1 center">
        @foreach ($pagination->items as $post)
            <article class="pv4 bt bb b--light-gray">
                <div class="flex flex-column flex-row-ns">
                    <div class="ph1 w-100 order-2 order-1-ns {{ $post->cover_image ? 'w-60-ns' : '' }}">
                        <h2 class="mv0 black pv2"><a class="black-80 link hover-blue" href="{{ $post->getUrl() }}">{{ $post->title }}</a></h2>
                        <time class="f6 gray pr2" datetime="{{ $post->getDate()->format(DateTime::ATOM) }}">{{ $post->getDate()->format('F j, Y') }}</time>
                        @foreach ($post->tags as $tag)
                            <a class="f6 gray link hover-blue" href="{{ $page->baseUrl }}/tags/{{ $tag }}">#{{ $tag }}</a>
                        @endforeach
                        <div class="f4 pv2">{!! $post->getExcerpt() !!}</div>
                        <div>
                            <a class="black-80 link hover-blue" href="{{ $post->getUrl() }}">Read more »</a>
                        </div>
                    </div>
                    @if ($post->cover_image)
                        <div class="order-1 order-2-ns w-100 w-40-ns">
                            <a class="dim" href="{{ $post->getUrl() }}"/>
                                <img src="{{ $post->cover_image }}"/>
                            </a>
                        </div>
                    @endif
                </div>
            </article>
        @endforeach

        @if ($pagination->pages->count() > 1)
            <div class="flex items-center justify-center pa4">
                @if ($previous = $pagination->previous)
                    <a href="{{ $previous }}" class="f5 no-underline black link hover-blue inline-flex items-center pa3 mr4">
                        <svg class="w1" data-icon="chevronLeft" viewBox="0 0 32 32" style="fill:currentcolor">
                            <title>chevronLeft icon</title>
                            <path d="M20 1 L24 5 L14 16 L24 27 L20 31 L6 16 z"></path>
                        </svg>
                        <span class="pl1">Previous</span>
                    </a>
                @endif
                @if ($next = $pagination->next)
                    <a href="{{ $next }}" class="f5 no-underline black link hover-blue inline-flex items-center pa3">
                        <span class="pr1">Next</span>
                        <svg class="w1" data-icon="chevronRight" viewBox="0 0 32 32" style="fill:currentcolor">
                            <title>chevronRight icon</title>
                            <path d="M12 1 L26 16 L12 31 L8 27 L18 16 L8 5 z"></path>
                        </svg>
                    </a>
                @endif
            </div>
        @endif
    </div>
@stop
