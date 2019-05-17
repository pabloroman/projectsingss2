@section('content')
	<div class="container">
	    <div class="process text-center">
	        <p class="init-title">{!! trans('website.W0365') !!}</p>
	        <img src="{{ asset('images/loader.gif') }}">
	    </div>
	</div>
@endsection

@push('inlinescript')
	<script type="text/javascript">
		var $redirect = '{{ $payment["redirection"] }}';
		setTimeout(function(){
			window.location = $redirect;
		},2000);
	</script>
@endpush
