<div style="padding-top:10px;">
    <form role="form-add-sub-industry" action="{{url(sprintf("%s/%s",ADMIN_FOLDER,'add-sub-industry'))}}" method="post">
        <div class="col-md-3 form-group">
            <div>
                <select class="form-control" name="industry_parent_id">
                    {!! ___dropdown_options($industries,trans("admin.A0018"),!empty($industry) ? $industry->parent : '') !!}
                </select>
            </div>
        </div>
        <div class="col-md-3 form-group">
            <input type="text" class="form-control" name="en" value="{{ !empty($industry) ? $industry->en : '' }}" placeholder="ENGLISH" style="width:100%;"/>
        </div>
        <div class="col-md-3 form-group">
            <input type="text" class="form-control" name="id" value="{{ !empty($industry) ? $industry->id : '' }}" placeholder="INDONESIA" style="width:100%;"/>
        </div>
        <div class="col-md-3 form-group">
            <input type="text" class="form-control" name="cz" value="{{ !empty($industry) ? $industry->cz : '' }}" placeholder="MANDARIN" style="width:100%;"/>
        </div>
        <div class="col-md-3 form-group">
            <input type="text" class="form-control" name="ta" value="{{ !empty($industry) ? $industry->ta : '' }}" placeholder="TAMIL" style="width:100%;"/>
        </div>
        <div class="col-md-3 form-group">
            <input type="text" class="form-control" name="hi" value="{{ !empty($industry) ? $industry->hi : '' }}" placeholder="HINDI" style="width:100%;"/>
        </div>
        <input type="hidden" name="id_industry" value=" {{ !empty($industry) ? ___encrypt($industry->id_industry) : ''}}">
        <input type="hidden" name="action" value="submit">
        <div class="col-md-2 form-group">
            <input type="button" class="btn btn-default btn-block" value="Save" data-request="inline-submit" data-target="[role=form-add-sub-industry]">
        </div>
    </form>
</div>
<div class="clearfix"></div>