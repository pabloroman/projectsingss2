<div class="login-inner-wrapper">
    <h2 class="form-heading no-padding m-b-15"><?php echo e(trans('website.W0032')); ?>  <button class="btn-plus pull-right tgl-btn" data-target=".work-experience-form" data-request="toggle-hide-show" data-action="show"></button></h2>
    <div class="work-experience-box row box-item-data">
        <?php if ($__env->exists('talent.profile.includes.workexperience')) echo $__env->make('talent.profile.includes.workexperience', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    </div>
    <div class="clearfix"></div>
    <div class="work-experience-form add-form" style="display: none;">
        <form class="form-horizontal" role="work-experience" action="<?php echo e(url(sprintf('%s/work-experience',TALENT_ROLE_TYPE))); ?>" method="POST" accept-charset="utf-8">
            <input type="hidden" name="id_experience">
            <div class="row">
                <div class="col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(trans('website.W0094')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <input type="text" name="jobtitle" placeholder="<?php echo e(trans('website.W0095')); ?>" class="form-control" />
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(trans('website.W0096')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="custom-dropdown">
                                <!--  data-request="tags" -->
                                <select name="company_name" data-request="tag" class="form-control" data-placeholder="<?php echo e(trans('website.W0097')); ?>">
                                    <?php echo ___dropdown_options(___cache('companies'),''); ?>

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(trans('website.W0098')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-6 month-start">
                                    <div class="custom-dropdown">
                                        <select name="joining_month" class="form-control" data-placeholder="<?php echo e(trans('website.W0100')); ?>">
                                        <?php echo ___dropdown_options(trans('website.W0048'),''); ?>

                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-6 year-start">
                                    <div class="custom-dropdown">
                                        <select name="joining_year" class="form-control" data-placeholder="<?php echo e(trans('website.W0103')); ?>">
                                            <?php echo ___dropdown_options(___range(passing_year(),'multi_dimension'),''); ?>

                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>                                    
                    <div class="form-group">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(trans('website.W0099')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="radio radio-inline">                
                                <input data-request="show-hide" data-condition="yes" data-target="[name='is_currently_working']" data-true-condition=".joining-month-section" data-false-condition=".relieving-month-section" name="is_currently_working" type="radio" value="<?php echo e(DEFAULT_YES_VALUE); ?>" id="c_work01">
                                <label for="c_work01"><?php echo e(trans('website.W0101')); ?></label>
                            </div>
                            <div class="radio radio-inline">                
                                <input data-request="show-hide" data-condition="yes" data-target="[name='is_currently_working']" data-true-condition=".joining-month-section" data-false-condition=".relieving-month-section" name="is_currently_working" type="radio" value="<?php echo e(DEFAULT_NO_VALUE); ?>" id="c_work02">
                                <label for="c_work02"><?php echo e(trans('website.W0102')); ?></label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group relieving-month-section">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(trans('website.W0107')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-6 month-start">
                                    <div class="custom-dropdown">
                                        <select name="relieving_month" class="form-control" data-placeholder="<?php echo e(trans('website.W0100')); ?>"><?php echo ___dropdown_options(trans('website.W0048'),''); ?></select>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-6 year-start">
                                    <div class="custom-dropdown">
                                        <select name="relieving_year" class="form-control" data-placeholder="<?php echo e(trans('website.W0103')); ?>"><?php echo ___dropdown_options(___range(passing_year(),'multi_dimension'),''); ?></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(trans('website.W0104')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                        <?php $__currentLoopData = employment_types('talent_curriculum_vitae'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getFirstLoop(); ?>
                            <div class="radio radio-inline">                
                                <input name="job_type" type="radio" value="<?php echo e($value['type']); ?>" id="t_job0-<?php echo e($value['type']); ?>">
                                <label for="t_job0-<?php echo e($value['type']); ?>"><?php echo e($value['type_name']); ?></label>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getFirstLoop(); ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(sprintf(trans('website.W0055'),'')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="custom-dropdown">
                                <select class="form-control" name="country" data-request="option" data-url="<?php echo e(url('ajax/country-state-list')); ?>"></select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(sprintf(trans('website.W0056'),'')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="custom-dropdown">
                                <select class="form-control" name="state"></select>
                            </div>
                        </div>
                    </div>                                    
                </div>
            </div>
            <button type="button" class="btn btn-default pull-right m-t-30px" data-box=".work-experience-box" data- data-request="multi-ajax" data-target='[role="work-experience"]' data-toremove="experience"  data-box-id='[name="id_experience"]'><?php echo e(trans('website.W0243')); ?></button>
        </form>
    </div>
    <div class="clearfix"></div>
</div>        
<div class="login-inner-wrapper clearfix">
    <h2 class="form-heading no-padding m-b-15"><?php echo e(trans('website.W0172')); ?><button class="btn-plus pull-right tgl-btn" data-target=".education-form" data-request="toggle-hide-show" data-action="show"></button></h2>
    <div class="education-box row box-item-data">
        <?php if ($__env->exists('talent.profile.includes.education')) echo $__env->make('talent.profile.includes.education', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>
    </div>
    <div class="clearfix"></div>
    <div class="education-form add-form" style="display: none;">
        <form class="form-horizontal" role="add-education" action="<?php echo e(url(sprintf('%s/add-education',TALENT_ROLE_TYPE))); ?>" method="POST" accept-charset="utf-8">
            <input type="hidden" name="id_education">
            <div class="row">
                <div class="col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(trans('website.W0082')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="custom-dropdown">
                                <!--  data-request="tags" -->
                                <select name="college" data-request="tag" class="form-control" data-placeholder="<?php echo e(trans('website.W0083')); ?>">
                                    <?php echo ___dropdown_options(___cache('colleges'),''); ?>

                                </select>
                            </div>
                        </div>
                    </div>                                    
                    <div class="form-group">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(trans('website.W0086')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="custom-dropdown">
                                <select name="passing_year" class="form-control" data-placeholder="<?php echo e(trans('website.W0087')); ?>">
                                    <?php echo ___dropdown_options(___range(passing_year(),'multi_dimension'),''); ?>

                                </select>
                            </div>
                        </div>
                    </div>
                                                        
                </div>
                <div class="col-md-6 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(trans('website.W0084')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <div class="custom-dropdown">
                                <select name="degree" class="form-control" data-placeholder="<?php echo e(trans('website.W0085')); ?>">
                                    <?php echo ___dropdown_options(___cache('degree_name'),""); ?>

                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(trans('website.W0088')); ?></label>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <input type="text" name="area_of_study" placeholder="<?php echo e(trans('website.W0089')); ?>" class="form-control" />
                            <input type="text" class="hide" />
                        </div>
                    </div>
                    
                                                        
                </div>
            </div>
            <button type="button" class="btn btn-default pull-right m-t-30px" data-box=".education-box" data-request="multi-ajax" data-target='[role="add-education"]' data-box-id='[name="id_education"]' data-toremove="box"><?php echo e(trans('website.W0093')); ?></button>
        </form>
    </div>
    <div class="clearfix"></div>
    <?php if(\Auth::User()->company_profile == 'company'): ?>
        <form class="form-horizontal" role="talent-jurisdiction" action="<?php echo e(url('/talent/firm-jurisdiction')); ?>" method="post" accept-charset="utf-8">
            <div class="form-group">
                <div class="custom-dropdown countrycode-dropdown">
                    <label class="control-label col-md-12 col-sm-12 col-xs-12"><?php echo e(sprintf(trans('website.W0972'),'')); ?></label>
                    <select id="jurisdiction" name="jurisdiction[]" style="max-width: 400px;" class="filter form-control" data-request="tags" multiple="true" data-placeholder="<?php echo e(trans('website.W0973')); ?>">
                        <?php echo ___dropdown_options(___cache('countries'),'',array_column($firm_jurisdiction,'country_id'),false); ?>

                    </select>
                    <div class="js-example-tags-container white-tags"></div>
                </div>
            </div>
            <input data-request="ajax-submit" data-target='[role="talent-jurisdiction"]' type="button" class="btn btn-default pull-right m-t-30px" value="<?php echo e(trans('website.W0974')); ?>" />
        </form>
    <?php endif; ?>
</div>
<div class="form-group button-group">
    <div class="row form-btn-set">
        <div class="col-md-6 col-sm-6 col-xs-12">
            <?php if(in_array('two',$steps)): ?>
                <a href="<?php echo e(url(sprintf('%s/profile/%sstep/%s',TALENT_ROLE_TYPE,$edit_url,$steps[count($steps)-2]))); ?>" class="greybutton-line"><?php echo e(trans('website.W0196')); ?></a>
            <?php endif; ?>
        </div>
        <div class="col-md-6 col-sm-6 col-xs-12">
            <a class="button" href="<?php echo e($skip_url); ?>">
                <?php echo e(trans('website.W0229')); ?>

            </a>
        </div>
    </div>
</div>

<?php $__env->startPush('inlinescript'); ?>
    <script type="text/javascript">
        setTimeout(function(){
            $('[name="country"]').select2({
                ajax: {
                    url: base_url+'/countries',
                    dataType: 'json',
                    data: function (params) {
                        var query = {
                            search: params.term,
                            type: 'public'
                        }
                        return query;
                    }
                },
                data: [{id: '<?php echo e($user['country']); ?>', text: '<?php echo e($user['country_name']); ?>'}],
                placeholder: function(){
                    $(this).find('option[value!=""]:first').html();
                }
            }).on('change',function(){
                $('[name="state"]').val('').trigger('change');
            });

            $('[name="degree_country"]').select2({
                ajax: {
                    url: base_url+'/countries',
                    dataType: 'json',
                    data: function (params) {
                        var query = {
                            search: params.term,
                            type: 'public'
                        }
                        return query;
                    }
                },
                data: [{id: '<?php echo e($user['country']); ?>', text: '<?php echo e($user['country_name']); ?>'}],
                placeholder: function(){
                    $(this).find('option[value!=""]:first').html();
                }
            });


            $('[name="state"]').select2({
                ajax: {
                    url: base_url+'/states',
                    dataType: 'json',
                    data: function (params) {
                        var query = {
                            country: $('[name="country"]').val(),
                            search: params.term,
                            type: 'public'
                        }
                        return query;
                    }
                },
                data: [{id: '<?php echo e($user['state']); ?>', text: '<?php echo e($user['state_name']); ?>'}],
                placeholder: function(){
                    $(this).find('option[value!=""]:first').html();
                }
            });
        },2000);

        setTimeout(function(){
            if($(".work-experience-box div").html() == '<?php echo e(N_A); ?>'){
                $(".work-experience-box div").html('');
            }
            if($(".education-box div").html() == '<?php echo e(N_A); ?>'){
                $(".education-box div").html('');
            }
        },500);
    </script>
<?php $__env->stopPush(); ?>