@extends('layouts.master')
<style>
    .jsThumbnailImageWrapper figure {
        position: relative;
        display: inline-block;
        margin-right: 20px;
        margin-bottom: 20px;
        background-color: #fff;
        border: 1px solid #eee;
        padding: 3px;
        border-radius: 3px;
    }
    .jsThumbnailImageWrapper i.removeIcon {
        position: absolute;
        top:-10px;
        right:-10px;
        color: #f56954;
        font-size: 2em;
        background: white;
        border-radius: 20px;
        height: 25px;
    }
    .jsThumbnailImageWrapper.jsSingleThumbnailWrapper {
        max-width: 270px;
    }
    .jsThumbnailImageWrapper.jsSingleThumbnailWrapper img {
        max-width: 100%;
    }
</style>
@section('content-header')
    <h1>
        {{ trans('banner::banners.title.edit banner') }}
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('dashboard.index') }}"><i class="fa fa-dashboard"></i> {{ trans('core::core.breadcrumb.home') }}</a></li>
        <li><a href="{{ route('admin.banner.banner.index') }}">{{ trans('banner::banners.title.banners') }}</a></li>
        <li class="active">{{ trans('banner::banners.title.edit banner') }}</li>
    </ol>
@stop

@section('styles')
    {!! Theme::script('js/vendor/ckeditor/ckeditor.js') !!}
@stop

@section('content')
    {!! Form::open(['route' => ['admin.banner.banner.update', $banner->id], 'method' => 'put']) !!}
    <div class="row">
        <div class="col-md-12">
            <div class="nav-tabs-custom">
                @include('partials.form-tab-headers')
                <div class="tab-content">
                    <?php $i = 0; ?>
                    @foreach (LaravelLocalization::getSupportedLocales() as $locale => $language)
                        <?php $i++; ?>
                        <div class="tab-pane {{ locale() == $locale ? 'active' : '' }}" id="tab_{{ $i }}">
                            @include('banner::admin.banners.partials.edit-fields', ['lang' => $locale])
                        </div>
                    @endforeach

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary btn-flat">{{ trans('core::core.button.update') }}</button>
                        <button class="btn btn-default btn-flat" name="button" type="reset">{{ trans('core::core.button.reset') }}</button>
                        <a class="btn btn-danger pull-right btn-flat" href="{{ route('admin.banner.banner.index')}}"><i class="fa fa-times"></i> {{ trans('core::core.button.cancel') }}</a>
                    </div>
                </div>
            </div> {{-- end nav-tabs-custom --}}
        </div>
    </div>
    {!! Form::close() !!}
@stop

@section('footer')
    <a data-toggle="modal" data-target="#keyboardShortcutsModal"><i class="fa fa-keyboard-o"></i></a> &nbsp;
@stop
@section('shortcuts')
    <dl class="dl-horizontal">
        <dt><code>b</code></dt>
        <dd>{{ trans('core::core.back to index') }}</dd>
    </dl>
@stop

@section('scripts')
    <script type="text/javascript">
        $( document ).ready(function() {
            $(document).keypressAction({
                actions: [
                    { key: 'b', route: "<?= route('admin.banner.banner.index') ?>" }
                ]
            });
        });
    </script>
    <script>
        $( document ).ready(function() {
            $('input[type="checkbox"].flat-blue, input[type="radio"].flat-blue').iCheck({
                checkboxClass: 'icheckbox_flat-blue',
                radioClass: 'iradio_flat-blue'
            });
        });
    </script>

    <script>
        $(function(){
            // alert($('#oldtype').val());
            if($('#oldtype').val()==1){
                $('#predefined_filters').show();
                $('#external_link').hide();
            }
            if($('#oldtype').val()==0){
                $('#predefined_filters').hide();
                $('#external_link').show(); 
            }
            $(".country,.category,.vendor,.subcategory").select2();
            // $(".category").select2();
            // $(".vendor").select2();
            // $('#predefined_filters').hide();


            // if($("#oldstatus").val()==1){
            //     $('#predefined_filters').show();
            //     $('#external_link').hide();
            // }
            // if($("#oldstatus").val()==0 && $("#oldstatus").val()!=''){
            //     $('#predefined_filters').hide();
            //     $('#external_link').show();   
            // }

            var wrapper         = $(".input_fields_wrap"); //Fields wrapper
            
            var x = 1; //initlal text box count
            var max_fields      = 5; //maximum input boxes allowed
            var wrapper         = $(".input_fields_wrap"); //Fields wrapper
            var add_button      = $(".add_field_button"); //Add button ID

            // var x = 1; //initlal text box count
            $(add_button).on("click",function(e){ //on add input button click
            e.preventDefault();
            if(x < max_fields){ //max input box allowed
                x++; //text box increment
                $(wrapper).append('<div style="margin-top: 5px;overflow: hidden;"><input class="form-control" type="text" name="keywords[]" value="" /><a href="javascript:void(0);" class="remove_field">Remove</a></div>'); //add input box
            }
            });

            $(wrapper).on("click",".remove_field", function(e){ //user click on remove text
            e.preventDefault(); $(this).parent('div').remove(); x--;
            })

            // $(wrapper).on("click",".remove_field", function(e){ //user click on remove text
            //     $('#row'+$(this).attr("id")+'').remove();  
            // })


            /******  ###########################
                        Image JS
             ##############################  ***/
             $('.jsThumbnailImageWrapper').on('click', '.jsRemoveSimpleLink', function (e) {
                e.preventDefault();
                var imageableId = $(this).data('id');

                // alert(imageableId);
                $('#oldimageablesid').val(imageableId);
                $('.labelhide').hide();
                $('.browse-image').show();
            });

             /******  ###########################
                        Checkbox Select all JS
             ##############################  ***/

            $("#countrycheckbox").click(function(){
                if($("#countrycheckbox").is(':checked') ){
                    $("#country > option").prop("selected","selected");
                    $("#country").trigger("change");
                }else{
                    $("#country > option").removeAttr("selected");
                     $("#country").trigger("change");
                 }
            });

            $("#catcheckbox").click(function(){
                if($("#catcheckbox").is(':checked') ){
                    $("#category > option").prop("selected","selected");
                    $("#category").trigger("change");
                }else{
                    $("#category > option").removeAttr("selected");
                     $("#category").trigger("change");
                 }
            });


            $("#vendorcheckbox").click(function(){
                if($("#vendorcheckbox").is(':checked') ){
                    $("#vendor > option").prop("selected","selected");
                    $("#vendor").trigger("change");
                }else{
                    $("#vendor > option").removeAttr("selected");
                     $("#vendor").trigger("change");
                 }
            });

            $("#subcatcheckbox").click(function(){
                if($("#subcatcheckbox").is(':checked') ){
                    $("#subcategory > option").prop("selected","selected");
                    $("#subcategory").trigger("change");
                }else{
                    $("#subcategory > option").removeAttr("selected");
                     $("#subcategory").trigger("change");
                 }
            });

            $('#subcategory').on('select2:unselecting', function (e) {
                $("#subcatcheckbox"). prop("checked", false);
            });
            $('#category').on('select2:unselecting', function (e) {
                $("#catcheckbox"). prop("checked", false);
            });
            $('#vendor').on('select2:unselecting', function (e) {
                $("#vendorcheckbox"). prop("checked", false);
            });
            $('#country').on('select2:unselecting', function (e) {
                $("#countrycheckbox"). prop("checked", false);
            });



        });

        $(document).on('click','.status', function(e){
            var status = $(this).val();
            if(status==1){
                $('#predefined_filters').show();
                $('#external_link').hide();
            }
            else{
                $('#predefined_filters').hide();
                $('#external_link').show();
            }
        });
    </script>
@stop
