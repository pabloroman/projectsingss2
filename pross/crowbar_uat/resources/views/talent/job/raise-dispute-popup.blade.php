<!-- Modal Window for Upload -->
<div class="modal fade upload-modal-box raise-dispute-popup" id="raiseDisputeModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <h3 class="modal-title">{{trans('website.W0409')}}</h3>
            <div class="modal-body bg-white">
                <div>
                    <p><b>{{ sprintf(trans('website.W0410'),$job_details['company_person_name']) }}</b></p>
                    <p>{{ trans('website.W0411') }}</p>
                    <br>
                    <form role="add-raise-dispute" method="POST" action={{ url(sprintf('%s/raise/dispute?project_id=%s',TALENT_ROLE_TYPE,___encrypt($project_id))) }}> 
                        {{ csrf_field() }}
                        <div class="form-group">
                            <textarea rows="12" class="form-control" name="reason" placeholder="{{ trans('website.W0412') }}"></textarea>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <div class="button-group">
                    <button type="button" class="button-line" value="cancel" data-dismiss="modal">
                        {{trans('website.W0355')}}
                    </button>
                    <button type="button" class="button" data-request="ajax-submit" data-target='[role="add-raise-dispute"]'>
                        {{trans('website.W0409')}}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- /.Modal Window for Upload -->
@push('inlinescript')
<script type="text/javascript">
</script>
@endpush
