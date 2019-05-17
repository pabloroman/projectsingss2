@extends('layouts.backend.dashboard')

@section('content')
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="box">
                    <div class="box-body">
                        <table id="message-table" class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="10%">Type</th>
                                    <th width="25%">Subject</th>
                                    <th width="40%">Content</th>
                                    <th width="10%">Status</th>
                                    <th width="15%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($messageconfig as $key=>$message)
                                    <tr>
                                        <td>{{ strtoupper($message->message_type) }}</td>
                                        <td>{{ $message->subject }}</td>
                                        <td><?php echo $message->content?></td>
                                        <td><span class="label label-@if ($message->status=='active')success @elseif ($message->status=='inactive')warning @endif">{{ ucfirst($message->status)}}</span></td>
                                        <td>
                                          	<div class="btn-group">
                                                <button type="button" class="btn btn-default">Action</button>
                                                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
                            	                    <span class="caret"></span>
                            	                    <span class="sr-only">Toggle Dropdown</span>
                                                </button>
                                                <ul class="dropdown-menu" role="menu">
                                                    <li><a href="{{ url('admin/message-config/'.$message->id.'/edit') }}">Edit</a></li>
                                                </ul>
                        	                </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection