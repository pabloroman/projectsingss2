<?php $__env->startSection('content'); ?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="panel">
                <div class="panel-body">
                    <?php if(Session::has('success')): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            <?php echo e(Session::get('success')); ?>

                        </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                        <?php echo $html->table();; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" id="delete-question-url" value="<?php echo e(url('administrator/forum/question/delete')); ?>" />
    <input type="hidden" id="update-status-url" value="<?php echo e(url('administrator/forum/question/update')); ?>" />
</section>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('inlinescript'); ?>
    <?php echo $html->scripts(); ?>

    <script type="text/javascript">
    function updateStatus(id_question){
        var url = $('#update-status-url').val();
        var isconfirm = confirm('Do you really want to continue with this action?');

        if(isconfirm && id_question > 0){
            $.ajax({
                method: "POST",
                url: url,
                data: { id_question: id_question}
            })
            .done(function(data) {
                LaravelDataTables["dataTableBuilder"].draw();
                swal({
                    title: '',
                    html: data.message,
                    showLoaderOnConfirm: false,
                    showCancelButton: false,
                    showCloseButton: false,
                    allowEscapeKey: false,
                    allowOutsideClick:false,
                    confirmButtonText: 'Okay',
                    cancelButtonText: '<i class="fa fa-times-circle-o"></i> Cancel',
                    confirmButtonColor: '#0FA1A8',
                    cancelButtonColor: '#CFCFCF'
                });
            });
        }
    }
    function deleteQues(id_question){
        var url = $('#delete-question-url').val();
        var isconfirm = confirm('Do you really want to continue with this action?');

        if(isconfirm && id_question > 0){
            $.ajax({
                method: "POST",
                url: url,
                data: { id_question: id_question}
            })
            .done(function(data) {
                LaravelDataTables["dataTableBuilder"].draw();
                swal({
                    title: '',
                    html: data.message,
                    showLoaderOnConfirm: false,
                    showCancelButton: false,
                    showCloseButton: false,
                    allowEscapeKey: false,
                    allowOutsideClick:false,
                    confirmButtonText: 'Okay',
                    cancelButtonText: '<i class="fa fa-times-circle-o"></i> Cancel',
                    confirmButtonColor: '#0FA1A8',
                    cancelButtonColor: '#CFCFCF'
                });
            });
        }
    }
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.backend.dashboard', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>