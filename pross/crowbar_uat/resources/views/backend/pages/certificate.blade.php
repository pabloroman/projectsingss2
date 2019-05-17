<div style="padding-top:10px;">
    <form role="form-add-certificate" action="{{url(sprintf("%s/%s",ADMIN_FOLDER,'add-certificate'))}}" method="post">
        <div class="col-md-1 form-group">
            <i class="fa fa-save fa-2x"></i>
        </div>
        <div class="col-md-5 form-group">
            <input type="text" class="form-control" name="certificate_name" value="{{ !empty($certificate) ? $certificate->certificate_name : ''}}" placeholder="{{ trans('admin.A0043') }}" style="width:100%;"/>
        </div>
        <input type="hidden" name="id_certificate" value="{{ !empty($certificate) ? ___encrypt($certificate->id_cetificate) : ''}}">
        <input type="hidden" name="action" value="submit">
        <div class="col-md-2 form-group">
            <button type="button" class="btn btn-default btn-block" data-request="inline-submit" data-target="[role=form-add-certificate]">Save</button>
        </div>
    </form>
</div>
<div class="clearfix"></div>