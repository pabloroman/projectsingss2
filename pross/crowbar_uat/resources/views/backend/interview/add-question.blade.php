@extends('layouts.backend.dashboard')
@section('content')
    <section class="content">
        <div class="row">
            <div class="col-xs-12">
                <div class="panel">
                    <form role="add-question" method="post" enctype="multipart/form-data" action="{{ url('administrator/question/add') }}">
                        {{ csrf_field() }}

                        <div class="panel-body">
                            <div class="form-group">
                                <label for="id_industry">Industries</label>
                                <select class="form-control" name="id_industry">
                                    @foreach($subindustries_name as $key => $item)
                                        <option value="{{ $key }}">{{ $item }}</option>
                                    @endforeach
                                </select>
                            </div>                        
                            <div class="form-group">
                                <label for="question">Question</label>
                                <input type="text" class="form-control" name="question" placeholder="Question" value="{{ old('question') }}">
                            </div>
                            <div class="form-group">
                                <label for="question_type">Question Type</label>
                                <select class="form-control" name="question_type">
                                    @foreach($question_type as $key => $item)
                                        <option value="{{ $item->id }}">{{ $item->question_type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="panel-footer">
                            <a href="{{url($backurl)}}" class="btn btn-default">Back</a>
                            <button type="button" data-request="ajax-submit" data-target='[role="add-question"]' class="btn btn-default">Save</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </section>
@endsection
