<div class="modal fade modal-default" id="modal-approve-confirmation" tabindex="-1" role="dialog" aria-labelledby="approve-confirmation-title" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="approve-confirmation-title">{{ trans('core::core.modal.title') }}</h4>
            </div>
            <div class="modal-body">
                Are you sure you want to approve this record?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default btn-flat" data-dismiss="modal">{{ trans('core::core.button.cancel') }}</button>
                {!! Form::open(['method' => 'approve', 'class' => 'pull-left']) !!}
                <a href="javascript:avoid(0)" class="btn btn-default btn-flat" id="approve-url"> Yes</a>
                {!! Form::close() !!}
            </div>
        </div>
    </div>
</div>

<script>
    $( document ).ready(function() {
        $('#modal-approve-confirmation').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var actionTarget = button.data('action-target');
            var modal = $(this);
            $("#approve-url").attr("href", actionTarget);
            // modal.find('form').attr('action', actionTarget);
        });
    });
</script>
