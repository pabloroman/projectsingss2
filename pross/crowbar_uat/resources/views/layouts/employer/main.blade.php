<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="_token" content="{{ csrf_token() }}">
        <meta name="viewport" content="width=device-width, initial-scale=1,user-scalable=no">
        <link href="{{ asset('favicon.ico') }}" rel="icon">
        <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
        <title>{{ !empty($title) ? $title.' | '.SITE_TITLE : SITE_TITLE }}</title>
        <!-- Google Font -->
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800&amp;subset=cyrillic,greek,latin-ext" rel="stylesheet">
        <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
        @yield('requirecss')    
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
        <link href="{{ asset('/bower_components/sweetalert2/dist/sweetalert2.css') }}" rel="stylesheet">
        <link href="{{ asset('css/dataTables.bootstrap.css') }}" rel="stylesheet" type="text/css" />
        <!-- fancybox css -->
        <link href="{{ asset('css/jquery.fancybox.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('css/loader.css') }}" rel="stylesheet">
        <link href="{{ asset('css/style.css') }}" rel="stylesheet">
        <link href="{{ asset('css/responsive.css') }}" rel="stylesheet">
        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
        <script>
            @php $agent = new Jenssegers\Agent\Agent; @endphp
            window.Laravel = <?php echo json_encode(['csrfToken' => csrf_token(),]); ?>;
            var $is_mobile_device   = '{{ (!empty($agent->isMobile())?DEFAULT_YES_VALUE:DEFAULT_NO_VALUE) }}';
            var $alert_message_text     = '{{ trans("website.W0548") }}';
            var $confirm_botton_text    = '{{ trans("website.W0551") }}';
            var $close_botton_text      = '{{ trans("website.W0549") }}';
            var $no_thanks_botton_text  = '{{ trans("website.W0552") }}';
            var $proposal_botton_text   = '{{ trans("website.W0753") }}';
            var $cancel_botton_text     = '{{ trans("website.W0550") }}';
            var $userID                 = '0';

            @if(auth()->guard('web')->check())
                var $userID = '{{auth()->user()->id_user}}';
            @endif
            
            var month = [
                "{{trans('general.M0451')}}",
                "{{trans('general.M0452')}}",
                "{{trans('general.M0453')}}",
                "{{trans('general.M0454')}}",
                "{{trans('general.M0455')}}",
                "{{trans('general.M0456')}}",
                "{{trans('general.M0457')}}",
                "{{trans('general.M0458')}}",
                "{{trans('general.M0459')}}",
                "{{trans('general.M0460')}}",
                "{{trans('general.M0461')}}",
                "{{trans('general.M0462')}}",
            ];
            
            var weekday = [
                "{{trans('general.M0463')}}",
                "{{trans('general.M0464')}}",
                "{{trans('general.M0465')}}",
                "{{trans('general.M0466')}}",
                "{{trans('general.M0467')}}",
                "{{trans('general.M0468')}}",
                "{{trans('general.M0469')}}",
            ];
            
            var base_url                = "{{ url('/') }}";
            var asset_url               = "{{ asset('/') }}";
            var $image_upload_text      = "{{trans('website.W0623')}}";
            var $image_upload_select    = "{{trans('website.W0624')}}";
            var $valid_email_note       = "{{trans('website.W0848')}}";
        </script>
        <script src="https://use.fontawesome.com/e26fdfdad2.js"></script>
        {!! \Cache::get('configuration')['google_analytics_code'] !!}
        @yield('inlinecss')
        @stack('inlinecss')
    </head>

    <body>
        <div class="wrapper">
            @include(sprintf('%s.includes.%s',EMPLOYER_ROLE_TYPE,$header))
            @yield('content')
            @include('footer')
            {{-- @include(sprintf('%s.includes.%s',EMPLOYER_ROLE_TYPE,$footer)) --}}
        </div>        
        <div id="popup" class="popup">
            <div class="loader">
                <div class="spinning">
                    <img src="{{ asset('images/loading.png') }}" style="border-radius: 30px;"/>
                    <span class="loader-text">{!! trans('website.W0672') !!}</span>
                </div>
            </div>
            <div class="popup_align"></div>
        </div>
        <script src="{{ asset('https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js') }}"></script>
        <script src="{{ asset('/js/bootstrap.min.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
        <script src="{{ asset('/bower_components/sweetalert2/dist/sweetalert2.min.js') }}"></script>
        <script src="{{ asset ("/script/common.js") }}" type="text/javascript"></script>
        <script src="{{ asset('/js/jquery.fancybox.min.js') }}"></script>
        <script type="text/javascript">
            $(function () {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },isLocal: false
                });
            });
        </script>
        <script src="{{ asset('/script/app.js') }}"></script>
        <script src="{{ asset('/js/chat/notification.js') }}"></script>
        @yield('requirejs')
        @yield('inlinejs')
        @stack('inlinescript')
        <script src="{{ asset('/script/employer.js') }}"></script>
    </body>

</html>
