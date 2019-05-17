@extends('layouts.backend.dashboard')

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="panel">
                    <form role="add-employer" method="post" enctype="multipart/form-data" action="{{ url(sprintf('%s/users/employer/add',ADMIN_FOLDER)) }}">
                        <input type="hidden" name="_method" value="PUT">
                        {{ csrf_field() }}

                        <div class="panel-body">
                            <div class="form-group">
                                <label for="name">First Name</label>
                                <input type="text" class="form-control" name="first_name" placeholder="First Name" value="{{ old('first_name') }}">
                            </div>
                            <div class="form-group">
                                <label for="name">Last Name</label>
                                <input type="text" class="form-control" name="last_name" placeholder="Last Name" value="{{ old('last_name') }}">
                            </div>
                            <div class="form-group">
                                <label for="name">Company Name</label>
                                <input type="text" class="form-control" name="company_name" placeholder="Company Name" value="{{ old('company_name') }}">
                            </div>
                            <div class="form-group">
                                <label for="name">Email</label>
                                <input type="text" class="form-control" name="email" placeholder="Email" value="{{ old('email') }}">
                            </div>

                        </div>
                        <div class="panel-footer">
                            <a href="{{ $backurl }}" class="btn btn-default">Back</a>
                            <button type="button" data-request="ajax-submit" data-target='[role="add-employer"]' class="btn btn-default">Save</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </section>
@endsection

@push('inlinescript')
<script type="text/javascript">

</script>
@endpush
