<form class="form-horizontal" role="existingjob" action="<?php echo e(url(sprintf('%s/existingjob?talent_id=%s',EMPLOYER_ROLE_TYPE,$talent_id))); ?>" method="post" accept-charset="utf-8">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <h3 class="form-heading m-b-10px no-padding"><?php echo e(trans('website.W0829')); ?></h3>
            <div class="login-inner-wrapper" style="padding: 35px;">
                <div class="form-group">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="custom-dropdown">
                            <select id="job" name="job" class="filter form-control" data-placeholder="<?php echo e(trans('website.W0829')); ?>">
                                <?php echo ___dropdown_options(
                                        $submitted_jobs,
                                        trans('website.W0829'),
                                        '',
                                        false
                                    ); ?>

                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="button-group">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="form-btn-set">                                    
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <button type="button" class="button" value="Submit" data-request="ajax-submit" data-target="[role=&quot;existingjob&quot;]"><?php echo e(trans('website.W0013')); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</form>
<script type="text/javascript">
    $(function(){
        $('[name="job"]').trigger('change');
    })
</script>