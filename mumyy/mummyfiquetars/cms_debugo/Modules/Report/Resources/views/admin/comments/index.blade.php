@extends('layouts.master')

@section('content-header')
    <h1>
        {{ trans('report::comments.title.comments') }}
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('dashboard.index') }}"><i class="fa fa-dashboard"></i> {{ trans('core::core.breadcrumb.home') }}</a></li>
        <li class="active">{{ trans('report::comments.title.comments') }}</li>
    </ol>
@stop
<?php
    $current_url                    = Request::url();
    $query_url                      = $_SERVER['QUERY_STRING'];
?>
@section('content')
    <div class="row">
        <div class="col-xs-12">
            <div class="row">

                <div class="btn-group pull-right" style="margin: 0 15px 15px 0;">
                    <a href="{{ route('admin.report.comment.exportcsv') }}" class="btn btn-primary btn-flat" style="padding: 4px 10px;">
                        <i class="fa fa-cloud-download"></i> Download CSV
                    </a>
                </div>
            </div>
            <div class="box box-primary">
                <div class="box-header">
                </div>
                <!-- /.box-header -->
                <div class="box-body">
                     {!! Form::open(array('url' => Request::fullUrl(), 'method'=>"GET", 'name' => "form_searchResult", 'id' => "form_searchResult") ) !!}
                    <div class="row">
                        <div class="col-sm-6 col-md-6">
                            <div style="white-space:nowrap;" id="dataTables_length">
                                <label>
                                <span>Show</span> <select style="display:inline-block; font-weight: normal;" name="limit" aria-controls="DataTables_Table_0" class="form-control input-sm">
                                        <option value="10" @if($limit == 10) selected @endif>10</option>
                                        <option value="25" @if($limit == 25) selected @endif>25</option>
                                        <option value="50" @if($limit == 50) selected @endif>50</option>
                                        <option value="100" @if($limit == 100) selected @endif>100</option>
                                    </select> <span>entries</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-6">
                            <div style="white-space:nowrap; text-align: right;">
                                <label>
                                    <span>Search: </span><input style="display: inline-block; font-weight: normal;" type="text" name="keyword" value="{{ isset($keyword) ? $keyword : "" }}" class="form-control-right" placeholder="">
                                    <button type="submit" class="form-control btn-primary" style="width: auto; display: inline-block;"><span>Search</span></button>
                                </label>
                            </div>
                        </div>
                    </div>
                    <table class="data-table table table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>{{ trans('report::reviews.table.commented by email') }}</th>
                            <th>{{ trans('report::reviews.table.reported by email') }}</th>
                            <th>{{ trans('report::reviews.table.vendor') }}</th>
                            <th>{{ trans('report::reviews.table.content') }}</th>
                            <th>{{ trans('core::core.table.created date') }}</th>
                            <th data-sortable="false">{{ trans('core::core.table.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (isset($comments)): ?>
                        <?php foreach ($comments as $comment): ?>

                        <?php 
                            $vendorComment = \Modules\Comment\Entities\Vendorcomment::find($comment->comment_id);
                            $emailReporter = '';
                            $emailCommenter = '';
                            $vendorBusinessName = '';
                            $reporter = \Modules\User\Entities\User::find($comment->user_id);
                            if(isset($vendorComment) && !empty($vendorComment))
                            {
                                $commenter = \Modules\User\Entities\User::find($vendorComment->user_id);
                                $portfolio = \Modules\Portfolio\Entities\Portfolio::find($vendorComment->portfolios_id);
                                if(isset($portfolio) && !empty($portfolio))
                                {
                                    $vendor = \Modules\Vendor\Entities\VendorProfile::where('user_id',$portfolio->vendor_id)->first();
                                }
                                
                            }
                            if(isset($commenter->email) && !empty($commenter->email)){
                                $emailCommenter = $commenter->email;
                            }
                            if(isset($reporter->email) && !empty($reporter->email)){
                                $emailReporter = $reporter->email;
                            }
                            if(isset($vendor->business_name) && !empty($vendor->business_name)){
                                $vendorBusinessName = $vendor->business_name;
                            }
                            $content = $comment->content;
                        ?>
                        <tr>
                            <td>{{ $emailCommenter }}</td>
                            <td>{{ $emailReporter }}</td>
                            <td>{{ $vendorBusinessName }}</td>
                            <td>{{ str_limit($content, 50) }}</td>
                            <td>{{ $comment->created_at && $comment->created_at != \Carbon\Carbon::create(0, 0, 0, 0) ? \Carbon\Carbon::parse($comment->created_at)->format('d/m/Y H:i:s') : "" }}</td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('admin.report.comment.detail', [$comment->id]) }}" class="btn btn-default btn-flat"><i class="fa fa-eye"></i></a>
                                    <button type="button" class="btn btn-danger btn-flat" data-toggle="modal" data-target="#modal-delete-confirmation" data-action-target="{{ route('admin.report.comment.destroy', [$comment->id]) }}"><i class="fa fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th>{{ trans('report::reviews.table.commented by email') }}</th>
                            <th>{{ trans('report::reviews.table.reported by email') }}</th>
                            <th>{{ trans('report::reviews.table.vendor') }}</th>
                            <th>{{ trans('report::reviews.table.content') }}</th>
                            <th>{{ trans('core::core.table.created date') }}</th>
                            <th>{{ trans('core::core.table.actions') }}</th>
                        </tr>
                        </tfoot>
                    </table>
                    <div class="row">
                        <div class="col-sm-5" style="margin-top: 15px;"> 
                            <span>Showing {{ $start }} to {{ $offset }} of {{ $count }} entries</span>
                        </div>
                        <div class="col-sm-7" style="text-align: right">
                            {!! $comments->appends(['limit' => $limit, 'keyword' => $keyword, 'page' => $page])->render() !!}
                        </div>
                    </div>
                    {!! Form::close() !!}
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
        </div>
    </div>
    @include('core::partials.delete-modal')
@stop


@section('custom-styles')
    <style>
    td.cell-image{
        overflow: auto;
        width: 250px !important;
        height: 100px !important;
        display: block;
    }
    .span-header-table{
        float: right;
        color: grey;
        margin-top: 3px;
    }
    a { 
        color: black; 
    } 
    a:hover { 
        color: black; 
    } 
    a:selected { 
        color: black; 
    } 
    </style>
@stop

@section('footer')
    <a data-toggle="modal" data-target="#keyboardShortcutsModal"><i class="fa fa-keyboard-o"></i></a> &nbsp;
@stop
@section('shortcuts')
    <dl class="dl-horizontal">
        <dt><code>c</code></dt>
        <dd>{{ trans('report::comments.title.create comment') }}</dd>
    </dl>
@stop

@section('scripts')
    <?php $locale = locale(); ?>
    <script type="text/javascript">
        $(function () {
            $('#dataTables_length').change(function(){   
                var url_string = window.location.href;
                var url = new URL(url_string);
                // var limit = url.searchParams.get("limit");
                var page = url.searchParams.get("page");
                //var keyword = url.searchParams.get("keyword");

                if(page){
                    $('#form_searchResult').append('<input type="hidden" name="page" value="'+page+'">');
                }
                //if(keyword){
                //    $('#form_searchResult').append('<input type="hidden" name="keyword" value="'+keyword+'">');
                //}

                $('#form_searchResult').submit();
            });

            $('.data-table1').dataTable({
                "paginate": true,
                "lengthChange": true,
                "filter": true,
                "sort": true,
                "info": true,
                "autoWidth": true,
                "order": [[ 0, "desc" ]],
                "language": {
                    "url": '<?php echo Module::asset("core:js/vendor/datatables/{$locale}.json") ?>'
                }
            });
        });
    </script>
@stop