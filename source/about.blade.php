@extends('_layouts.master')

@push('meta')
    <meta property="og:title" content="About {{ $page->siteName }}" />
    <meta property="og:type" content="website" />
    <meta property="og:url" content="{{ $page->getUrl() }}"/>
    <meta property="og:description" content="A little bit about {{ $page->siteName }}" />
@endpush

@section('body')
    <div class="about">
        <img src="/assets/images/about.png" alt="About image" class="about__image">

        <p>I'm a software developer living off grid just outside of Asheville, NC.  I build a lot of web applications with the <a href="https://laravel.com" title="Laravel PHP Framework">Laravel PHP Framework</a>.

        <p>I mostly write about PHP, web APIs, and other topics that might be interesting to other software developers.</p>
    </div>
@endsection
