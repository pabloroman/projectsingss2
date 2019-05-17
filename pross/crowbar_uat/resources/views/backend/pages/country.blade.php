<div style="padding-top:10px;">
    <form role="form-add-country" action="{{url(sprintf("%s/%s",ADMIN_FOLDER,'general/countries/add'))}}" method="post">
        <div class="clearfix">
            <div class="col-md-3 form-group">
                <input type="text" class="form-control" name="iso_code" value="{{ !empty($country) ? $country->iso_code : '' }}" placeholder="ISO CODE" style="width:100%;"/>
            </div>
            <div class="col-md-3 form-group">
                <input type="text" class="form-control" name="phone_country_code" value="{{ !empty($country) ? $country->phone_country_code : '' }}" placeholder="PHONE CODE" style="width:100%;"/>
            </div>
            <div class="col-md-3 form-group">
                <input type="text" class="form-control" name="en" value="{{ !empty($country) ? $country->en : '' }}" placeholder="ENGLISH" style="width:100%;"/>
            </div>
            <div class="col-md-3 form-group">
                <input type="text" class="form-control" name="id" value="{{ !empty($country) ? $country->id : '' }}" placeholder="INDONESIA" style="width:100%;"/>
            </div>
        </div>
        <div class="clearfix">
            <div class="col-md-3 form-group">
                <input type="text" class="form-control" name="cz" value="{{ !empty($country) ? $country->cz : '' }}" placeholder="MNADARIN" style="width:100%;"/>
            </div>
            <div class="col-md-3 form-group">
                <input type="text" class="form-control" name="ta" value="{{ !empty($country) ? $country->ta : '' }}" placeholder="TAMIL" style="width:100%;"/>
            </div>
            <div class="col-md-3 form-group">
                <input type="text" class="form-control" name="hi" value="{{ !empty($country) ? $country->hi : '' }}" placeholder="HINDI" style="width:100%;"/>
            </div>         
            <input type="hidden" name="country_id" value="{{ !empty($country) ? ___encrypt($country->id_country) : '' }}">
            <input type="hidden" name="action" value="submit">
            <div class="col-md-3 form-group">
                <button type="button" class="btn btn-default btn-block" data-request="inline-submit" data-target="[role=form-add-country]">Save</button>
            </div>
        </div>
    </form>
</div>
<div class="clearfix"></div>
<hr style="margin-top:0;">