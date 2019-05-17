@section('content')
    <!-- Main Content -->
    <div class="contentWrapper" data-request="scroll" data-section="{{ \Request::get('section') }}">
        <div class="afterlogin-section has-nobanner viewProfile">
            <div class="container">
                @includeIf('employer.includes.talent-profile-header')
                <div class="clearfix"></div>
                @include('employer.job.includes.talent-profile-menu',$user)
                <div class="col-md-9 right-sidebar">
                    <div class="talent-profile-section">
                        <div class="view-information" id="personal-infomation">
                            <div class="no-table datatable-listing shift-up-5px">
                                {!! $html->table(); !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 right-sidebar">
                    @include('employer.includes.top-talent-listing')
                </div>                
            </div>
        </div>
    </div>
    <div class="modal fade upload-modal-box add-payment-cards" id="hire-me" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"></div>
@endsection
@push('inlinescript')
    <script src="{{ asset('js/jquery.dataTables.js') }}"></script>
    <script src="{{ asset('js/dataTables.bootstrap.js') }}"></script>
    {!! $html->scripts() !!}

    <script type="text/javascript">
        $(function(){
            $('#dataTableBuilder_wrapper .row:first').remove();

            $(document).on('keyup click','[name="search"],#search-list',function(){
                LaravelDataTables["dataTableBuilder"].on('preXhr.dt', function ( e, settings, data ) {
                    data.filter = $('[name="search"]').val();
                }); 

                window.LaravelDataTables.dataTableBuilder.draw();
            });
        });
    </script>
@endpush
