<form role="form" method="post" enctype="multipart/form-data" action="{{ url($url.'/talent-users/'.$user['id_user'].'/update-education') }}">
    <input type="hidden" name="_method" value="PUT">
    {{ csrf_field() }}

    <div class="panel-body">
        <div class="form-group">
            <label for="name">Availability</label>
            @foreach($availability as $a)
                <div class="form-group">
                    {{ucfirst($a['repeat'])}}
                </div>
            @endforeach

        </div>

    </div>
    <div class="panel-footer">
        <a href="{{url($backurl.'/user-list?page=talent')}}" class="btn btn-default">Back</a>
    </div>
</form>
