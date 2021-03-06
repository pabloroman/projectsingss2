<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>{{ !empty($title) ? $title.' | '.SITE_TITLE : SITE_TITLE }}</title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <meta name="_token" content="{{ csrf_token() }}">
        <link href="{{ asset('favicon.ico') }}" rel="icon">
        <link rel="stylesheet" href="{{ asset('backend/css/loader.css') }}">
        <link href="{{ asset("/backend/bootstrap/css/bootstrap.min.css") }}" rel="stylesheet" type="text/css" />
        <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <link href="{{ asset('/bower_components/sweetalert2/dist/sweetalert2.css') }}" rel="stylesheet">
        <link href="http://code.ionicframework.com/ionicons/2.0.0/css/ionicons.min.css" rel="stylesheet" type="text/css" />
        <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
        <link href="{{ asset("/backend/dist/css/admin.min.css")}}" rel="stylesheet" type="text/css" />
        <link href="{{ asset("/backend/dist/css/skins/skin-black-light.min.css")}}" rel="stylesheet" type="text/css" />
        <link href="{{ asset("/backend/dist/css/custom.css")}}" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="{{ asset("/backend/plugins/datatables/dataTables.bootstrap.css")}}">
        <script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
        <link href="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/css/bootstrap-editable.css" rel="stylesheet"/>
        <script src="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/bootstrap3-editable/js/bootstrap-editable.min.js"></script>
        <script>//https://github.com/rails/jquery-ujs/blob/master/src/rails.js
            (function(e,t){if(e.rails!==t){e.error("jquery-ujs has already been loaded!")}var n;var r=e(document);e.rails=n={linkClickSelector:"a[data-confirm], a[data-method], a[data-remote], a[data-disable-with]",buttonClickSelector:"button[data-remote], button[data-confirm]",inputChangeSelector:"select[data-remote], input[data-remote], textarea[data-remote]",formSubmitSelector:"form",formInputClickSelector:"form input[type=submit], form input[type=image], form button[type=submit], form button:not([type])",disableSelector:"input[data-disable-with], button[data-disable-with], textarea[data-disable-with]",enableSelector:"input[data-disable-with]:disabled, button[data-disable-with]:disabled, textarea[data-disable-with]:disabled",requiredInputSelector:"input[name][required]:not([disabled]),textarea[name][required]:not([disabled])",fileInputSelector:"input[type=file]",linkDisableSelector:"a[data-disable-with]",buttonDisableSelector:"button[data-remote][data-disable-with]",CSRFProtection:function(t){var n=e('meta[name="csrf-token"]').attr("content");if(n)t.setRequestHeader("X-CSRF-Token",n)},refreshCSRFTokens:function(){var t=e("meta[name=csrf-token]").attr("content");var n=e("meta[name=csrf-param]").attr("content");e('form input[name="'+n+'"]').val(t)},fire:function(t,n,r){var i=e.Event(n);t.trigger(i,r);return i.result!==false},confirm:function(e){return confirm(e)},ajax:function(t){return e.ajax(t)},href:function(e){return e.attr("href")},handleRemote:function(r){var i,s,o,u,a,f,l,c;if(n.fire(r,"ajax:before")){u=r.data("cross-domain");a=u===t?null:u;f=r.data("with-credentials")||null;l=r.data("type")||e.ajaxSettings&&e.ajaxSettings.dataType;if(r.is("form")){i=r.attr("method");s=r.attr("action");o=r.serializeArray();var h=r.data("ujs:submit-button");if(h){o.push(h);r.data("ujs:submit-button",null)}}else if(r.is(n.inputChangeSelector)){i=r.data("method");s=r.data("url");o=r.serialize();if(r.data("params"))o=o+"&"+r.data("params")}else if(r.is(n.buttonClickSelector)){i=r.data("method")||"get";s=r.data("url");o=r.serialize();if(r.data("params"))o=o+"&"+r.data("params")}else{i=r.data("method");s=n.href(r);o=r.data("params")||null}c={type:i||"GET",data:o,dataType:l,beforeSend:function(e,i){if(i.dataType===t){e.setRequestHeader("accept","*/*;q=0.5, "+i.accepts.script)}if(n.fire(r,"ajax:beforeSend",[e,i])){r.trigger("ajax:send",e)}else{return false}},success:function(e,t,n){r.trigger("ajax:success",[e,t,n])},complete:function(e,t){r.trigger("ajax:complete",[e,t])},error:function(e,t,n){r.trigger("ajax:error",[e,t,n])},crossDomain:a};if(f){c.xhrFields={withCredentials:f}}if(s){c.url=s}return n.ajax(c)}else{return false}},handleMethod:function(r){var i=n.href(r),s=r.data("method"),o=r.attr("target"),u=e("meta[name=csrf-token]").attr("content"),a=e("meta[name=csrf-param]").attr("content"),f=e('<form method="post" action="'+i+'"></form>'),l='<input name="_method" value="'+s+'" type="hidden" />';if(a!==t&&u!==t){l+='<input name="'+a+'" value="'+u+'" type="hidden" />'}if(o){f.attr("target",o)}f.hide().append(l).appendTo("body");f.submit()},formElements:function(t,n){return t.is("form")?e(t[0].elements).filter(n):t.find(n)},disableFormElements:function(t){n.formElements(t,n.disableSelector).each(function(){n.disableFormElement(e(this))})},disableFormElement:function(e){var t=e.is("button")?"html":"val";e.data("ujs:enable-with",e[t]());e[t](e.data("disable-with"));e.prop("disabled",true)},enableFormElements:function(t){n.formElements(t,n.enableSelector).each(function(){n.enableFormElement(e(this))})},enableFormElement:function(e){var t=e.is("button")?"html":"val";if(e.data("ujs:enable-with"))e[t](e.data("ujs:enable-with"));e.prop("disabled",false)},allowAction:function(e){var t=e.data("confirm"),r=false,i;if(!t){return true}if(n.fire(e,"confirm")){r=n.confirm(t);i=n.fire(e,"confirm:complete",[r])}return r&&i},blankInputs:function(t,n,r){var i=e(),s,o,u=n||"input,textarea",a=t.find(u);a.each(function(){s=e(this);o=s.is("input[type=checkbox],input[type=radio]")?s.is(":checked"):s.val();if(!o===!r){if(s.is("input[type=radio]")&&a.filter('input[type=radio]:checked[name="'+s.attr("name")+'"]').length){return true}i=i.add(s)}});return i.length?i:false},nonBlankInputs:function(e,t){return n.blankInputs(e,t,true)},stopEverything:function(t){e(t.target).trigger("ujs:everythingStopped");t.stopImmediatePropagation();return false},disableElement:function(e){e.data("ujs:enable-with",e.html());e.html(e.data("disable-with"));e.bind("click.railsDisable",function(e){return n.stopEverything(e)})},enableElement:function(e){if(e.data("ujs:enable-with")!==t){e.html(e.data("ujs:enable-with"));e.removeData("ujs:enable-with")}e.unbind("click.railsDisable")}};if(n.fire(r,"rails:attachBindings")){e.ajaxPrefilter(function(e,t,r){if(!e.crossDomain){n.CSRFProtection(r)}});r.delegate(n.linkDisableSelector,"ajax:complete",function(){n.enableElement(e(this))});r.delegate(n.buttonDisableSelector,"ajax:complete",function(){n.enableFormElement(e(this))});r.delegate(n.linkClickSelector,"click.rails",function(r){var i=e(this),s=i.data("method"),o=i.data("params"),u=r.metaKey||r.ctrlKey;if(!n.allowAction(i))return n.stopEverything(r);if(!u&&i.is(n.linkDisableSelector))n.disableElement(i);if(i.data("remote")!==t){if(u&&(!s||s==="GET")&&!o){return true}var a=n.handleRemote(i);if(a===false){n.enableElement(i)}else{a.error(function(){n.enableElement(i)})}return false}else if(i.data("method")){n.handleMethod(i);return false}});r.delegate(n.buttonClickSelector,"click.rails",function(t){var r=e(this);if(!n.allowAction(r))return n.stopEverything(t);if(r.is(n.buttonDisableSelector))n.disableFormElement(r);var i=n.handleRemote(r);if(i===false){n.enableFormElement(r)}else{i.error(function(){n.enableFormElement(r)})}return false});r.delegate(n.inputChangeSelector,"change.rails",function(t){var r=e(this);if(!n.allowAction(r))return n.stopEverything(t);n.handleRemote(r);return false});r.delegate(n.formSubmitSelector,"submit.rails",function(r){var i=e(this),s=i.data("remote")!==t,o,u;if(!n.allowAction(i))return n.stopEverything(r);if(i.attr("novalidate")==t){o=n.blankInputs(i,n.requiredInputSelector);if(o&&n.fire(i,"ajax:aborted:required",[o])){return n.stopEverything(r)}}if(s){u=n.nonBlankInputs(i,n.fileInputSelector);if(u){setTimeout(function(){n.disableFormElements(i)},13);var a=n.fire(i,"ajax:aborted:file",[u]);if(!a){setTimeout(function(){n.enableFormElements(i)},13)}return a}n.handleRemote(i);return false}else{setTimeout(function(){n.disableFormElements(i)},13)}});r.delegate(n.formInputClickSelector,"click.rails",function(t){var r=e(this);if(!n.allowAction(r))return n.stopEverything(t);var i=r.attr("name"),s=i?{name:i,value:r.val()}:null;r.closest("form").data("ujs:submit-button",s)});r.delegate(n.formSubmitSelector,"ajax:send.rails",function(t){if(this==t.target)n.disableFormElements(e(this))});r.delegate(n.formSubmitSelector,"ajax:complete.rails",function(t){if(this==t.target)n.enableFormElements(e(this))});e(function(){n.refreshCSRFTokens()})}})(jQuery)
        </script>
        <style>
            a.status-1{
                font-weight: bold;
            }
        </style>
        <script>
            jQuery(document).ready(function($){

                $.ajaxSetup({
                    beforeSend: function(xhr, settings) {
                        console.log('beforesend');
                        settings.data += "&_token=<?= csrf_token() ?>";
                    }
                });

                $(document).on('click','.editable',function(){
                    //$('.editableform').attr('action',admin_url+'/translations/edit');
                });

                $('.editable').editable().on('hidden', function(e, reason){
                    var locale = $(this).data('locale');
                    if(reason === 'save'){
                        $(this).removeClass('status-0').addClass('status-1');
                    }
                    if(reason === 'save' || reason === 'nochange') {
                        var $next = $(this).closest('tr').next().find('.editable.locale-'+locale);
                        setTimeout(function() {
                            $next.editable('show');
                        }, 300);
                    }
                });

                $('.group-select').on('change', function(){
                    var group = $(this).val();
                    if (group) {
                        window.location.href = admin_url+'/translations/view/'+$(this).val();
                    }else{
                        window.location.href = admin_url+'/translations';
                    } 
                });

                $("a.delete-key").click(function(event){
                  event.preventDefault();
                  var row = $(this).closest('tr');
                  var url = $(this).attr('href');
                  var id = row.attr('id');
                  $.post( url, {id: id}, function(){
                      row.remove();
                  } );
                });

                $('.form-import').on('ajax:success', function (e, data) {
                    $('div.success-import strong.counter').text(data.counter);
                    $('div.success-import').slideDown();
                });

                $('.form-find').on('ajax:success', function (e, data) {
                    $('div.success-find strong.counter').text(data.counter);
                    $('div.success-find').slideDown();
                });

                $('.form-publish').on('ajax:success', function (e, data) {
                    $('div.success-publish').slideDown();
                });

            })
        </script>
        @yield('requirecss')
        @yield('inlinecss')
        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
            <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
        <script type="text/javascript"> 
            @php $agent = new Jenssegers\Agent\Agent; @endphp
            window.Laravel = <?php echo json_encode(['csrfToken' => csrf_token(),]); ?>;
            var $is_mobile_device   = '{{ (!empty($agent->isMobile())?DEFAULT_YES_VALUE:DEFAULT_NO_VALUE) }}';
            var $alert_message_text     = '{{ trans("website.W0548") }}';
            var $confirm_botton_text    = '{{ trans("website.W0551") }}';
            var $close_botton_text      = '{{ trans("website.W0549") }}';
            var $no_thanks_botton_text  = '{{ trans("website.W0552") }}';
            var $cancel_botton_text     = '{{ trans("website.W0550") }}';
            
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
            
            var admin_url               = "{{ url('/administrator/') }}";
            var base_url                = "{{ url('/') }}";
            var asset_url               = "{{ asset('/') }}";
            var $image_upload_text      = "{{trans('website.W0623')}}";
            var $image_upload_select    = "{{trans('website.W0624')}}";
        </script>
        @yield('inlinejs-top')
    </head>
    {{-- sidebar-collapse --}}
    <body class="hold-transition skin-black-light sidebar-mini">
        <div class="wrapper">
            @include('backend.includes.header')
            @include('backend.includes.sidebar')
            <div id="content-wrapper" class="content-wrapper" style="min-height: 578px;">
                @if(!empty($top_buttons)) 
                    <section class="content-header">
                        <span class="pull-right">
                            {{$top_buttons}}
                        </span>
                        <div class="clearfix"><br></div>
                    </section>
                 @endif
                @yield('content')
            </div>
            @include('backend.includes.footer')
        </div>
        <script src="{{ asset ("/backend/dist/js/app.min.js") }}" type="text/javascript"></script>
        <script src="{{ asset ("/script/common.js") }}" type="text/javascript"></script>
        <script src="{{ asset('/bower_components/sweetalert2/dist/sweetalert2.min.js') }}"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>
        <script src="{{ asset ("/js/api.js") }}" type="text/javascript"></script>
        <script type="text/javascript">$(function () { $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')}});});</script>
        @yield('requirejs')

        @yield('inlinejs')
        @stack('inlinescript')
        <script src="{{ asset ("/script/backend.js") }}" type="text/javascript"></script>
        <div id="popup" class="popup">
            <div class="loading">
                <div class="logo-wrapper"></div>
                <div class="spinning"></div>
            </div>
            <div class="popup_align"></div>
        </div>
    </body>
</html>
