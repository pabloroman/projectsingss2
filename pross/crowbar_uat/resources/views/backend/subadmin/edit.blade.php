@extends('layouts.backend.dashboard')

@section('requirecss')
    <link rel="stylesheet" type="text/css" href="{{asset('backend/plugins/iCheck/square/square.css')}}">
@endsection

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="panel">
                    <form role="add-talent" method="post" enctype="multipart/form-data" action="{{ url(sprintf('%s/users/sub-admin/update/%s',ADMIN_FOLDER,$user['id_user'])) }}">
                        <input type="hidden" name="_method" value="PUT">
                        {{ csrf_field() }}

                        <div class="panel-body">
                            <div class="form-group">
                                <label for="name">First Name</label>
                                <input type="text" class="form-control" name="first_name" placeholder="First Name" value="{{ (old('first_name'))?old('first_name'):$user['first_name'] }}">
                            </div>
                            <div class="form-group">
                                <label for="name">Last Name</label>
                                <input type="text" class="form-control" name="last_name" placeholder="Last Name" value="{{ (old('last_name'))?old('last_name'):$user['last_name'] }}">
                            </div>
                            <div class="form-group">
                                <label for="name">Email</label>
                                <input readonly="readonly" type="text" class="form-control" name="email" placeholder="Email" value="{{ (old('email'))?old('email'):$user['email'] }}">
                            </div>

                            <div class="form-group">
                                <label for="name">Access Permission</label>
                                <?php echo user_menu($menu_visibility); ?>
                                <div class="clearfix"></div>
                            </div>

                            <div class="form-group">
                                <input type="hidden" class="form-control" name="menus_error">
                            </div>

                        </div>
                        <div class="panel-footer">
                            <a href="{{ $backurl }}" class="btn btn-default">Back</a>
                            <button type="button" data-request="ajax-submit" data-target='[role="add-talent"]' class="btn btn-default">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('inlinescript')
<script type="text/javascript" src="{{asset('backend/plugins/iCheck/icheck.min.js')}}"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $("input").iCheck({
            checkboxClass: "icheckbox_square",
            radioClass: "iradio_square",
            increaseArea: "10%"
        });

        $(document).on("click",".mjs-nestedSortable-collapsed .disclose",function() {
            $(this).parent().find("input").iCheck("check");
        });

        $(document).on("click",".mjs-nestedSortable-expanded .disclose",function() {
            $(this).parent().find("input").iCheck("uncheck");
        });

        $("input").on("ifChecked", function(event){
            if($(this).data("request") == "has-child"){
                $(this).closest("li").toggleClass("mjs-nestedSortable-collapsed").toggleClass("mjs-nestedSortable-expanded");
                $(this).closest("li").children("ol").find("input").iCheck("check");
            }
        });

        $("input").on("ifUnchecked", function(event){
            if($(this).data("request") == "has-child"){
                $(this).closest("li").toggleClass("mjs-nestedSortable-collapsed").toggleClass("mjs-nestedSortable-expanded");
                $(this).closest("li").children("ol").find("input").iCheck("uncheck");
            }else if($(this).data("request") == "is-child"){
                if($(this).closest("ol").find("input:checked").length == 0){
                    $(this).closest("ol").parent("li").children(".ui-sortable-handle").find("input").iCheck("uncheck");
                }
            }
        });
    });
</script>
@endpush
