<div class="panel-body">
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th width="3%">#</th>
                    <th>Job Title</th>
                    <th>Company Name</th>
                    <th>Start Date</th>
                    <th>Currently Working?</th>
                    <th>Type of Job</th>
                    <th>End Date</th>
                    <th>Country</th>
                    <th>State/ Province</th>
                    <th width="10">Action</th>
                </tr>
            </thead>
            <tbody>
                @php
                $counter = 1;
                @endphp
                @foreach($work_experience_list as $e)
                    <tr id="list-{{$e['id_experience']}}">
                        <td width="3%">{{$counter++}}</td>
                        <td>{{$e['jobtitle']}}</td>
                        <td>{{$e['company_name']}}</td>
                        <td>{{$e['joining']}}</td>

                        <td>{{$e['is_currently_working'] == 'yes' ? 'Yes' : 'No'}}</td>
                        <td>{{$e['job_type'] == 'fulltime' ? 'Fulltime' : 'Temporary'}}</td>
                        <td>{{$e['joining']}}</td>

                        <td>{{$e['country_name']}}</td>
                        <td>{{$e['state_name']}}</td>
                        <td width="10">
                            <a href="javascript:;" data-url="{{url('administrator/talent/delete-experience/'.$e['id_experience'].'/'.$id_user)}}" data-id-user="{{$id_user}}" data-id-experience="{{$e['id_experience']}}" class="delete-exp badge bg-red" >Delete</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('inlinescript')
<script type="text/javascript">
$(document).ready(function(){
    $('.delete-exp').click(function(){
        var id_experience = $(this).data('id-experience');
        var url = $(this).data('url');
        var res = confirm('Do you really want to continue with this action?');

        if(res){
            $.ajax({
            method: "GET",
            url: url
            })
            .done(function(data) {
                $('#list-'+id_experience).remove();
            });
        }
    });
});
</script>
@endpush
