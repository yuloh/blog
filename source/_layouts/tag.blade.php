@extends('_layouts.master')

@push('meta')
    <meta property="og:title" content="{{ $page->title }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ $page->getUrl() }}"/>
    <meta property="og:description" content="{{ $page->description }}" />
@endpush

@section('body')
    <h1>{{ $page->title }}</h1>

    <div class="">
        @yield('content')
    </div>

    @foreach ($page->posts($posts) as $post)
      {{-- preview  --}}

    @endforeach
    {{-- newsletter --}}
@stop
