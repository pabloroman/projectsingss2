<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="login-inner-wrapper" style="padding: 35px;">
            <div class="company-section">
	            <form class="form-horizontal" role="existingjob" action="{{url(sprintf('%s/auth_delete_pwd?id_user=%s&type=%s&status=%s',ADMIN_FOLDER,$id_user,$type,$status))}}" method="post" accept-charset="utf-8" onkeypress="return event.keyCode != 13;">
                    <div class="form-group">
                        <label for="name">Password</label>
                        <input type="password" class="form-control" name="password" id="admin_password" placeholder="Enter Password">
                    </div>
                </form>	                    
            </div>
            <div class="button-group">
                <div>
                    <button type="button" class="btn btn-default" value="Submit" data-request="ajax-submit" data-target="[role=&quot;existingjob&quot;]">{{trans('website.W0013')}}</button>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    </div>
</div>