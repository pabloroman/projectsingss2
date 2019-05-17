@extends('layouts.chat.main')

    {{-- ******INCLUDE CSS PAGE-WISE****** --}}
    @section('requirecss')

    @endsection
    {{-- ******INCLUDE CSS PAGE-WISE****** --}}

    {{-- ******INCLUDE INLINE-JS PAGE-WISE****** --}}
    @section('inlinecss')
        {{-- CODE WILL GO HERE --}}
    @endsection
    {{-- ******INCLUDE INLINE-JS PAGE-WISE****** --}}

    {{-- ******INCLUDE JS PAGE-WISE****** --}}
    @section('toprequirejs')
        <script src="{{ asset('js/chat/socket.io') }}.js"></script>
        <script src="{{ asset('js/chat/slimscroll.js') }}"></script>
        <script src="{{ asset('js/chat/moment.js') }}"></script>
        <script src="{{ asset('js/chat/moment-timezone.js') }}"></script>
        <script src="{{ asset('js/chat/moment-timezone-with-data-2012-2022.js') }}"></script>
        <script src="{{ asset('js/chat/livestamp.js') }}"></script>
    @endsection
    {{-- ******INCLUDE JS PAGE-WISE****** --}}
    
    {{-- ******INCLUDE JS PAGE-WISE****** --}}
    @section('requirejs')
        <script src="{{ asset('js/app.js') }}" type="text/javascript"></script>
        <script src="{{ asset('js/moment-with-locales.min.js') }}" type="text/javascript"></script>
        <script src="{{ asset('js/moment-timezone-with-data.min.js') }}" type="text/javascript"></script>
        <script src="{{ asset('js/chat/chat.js') }}"></script>
        <script src="{{ asset('js/chat/notification.js') }}"></script>
    @endsection
    {{-- ******INCLUDE JS PAGE-WISE****** --}}
    
    {{-- ******INCLUDE INLINE-JS PAGE-WISE****** --}}
    @section('inlinejs')
        {{-- CODE WILL GO HERE --}}
    @endsection
    {{-- ******INCLUDE INLINE-JS PAGE-WISE****** --}}

    @section('content')
        @include($view)
    @endsection
