@extends('layouts.master')

@section('content')
<style type="text/css">
	* {
	    box-sizing: border-box;
	}

	.column {
	    float: left;
	    width: 50%;
	    padding: 5px;
	}

	/* Clearfix (clear floats) */
	.row::after {
	    content: "";
	    clear: both;
	    display: table;
	}
</style>
<div class="container-contact100">
	<div class="wrap-contact100">
		<div class="contact100-form">
			<span class="contact100-form-title">
				CBIR Result
			</span>
			<div class="wrap-input100 bg1">
				@php $ctr = 0; @endphp
				@foreach($result as $value)
					@php $ctr += 1; @endphp
					@if($ctr % 2  != 0)
						<div class="row">
					@endif
						<div class="column wrap-input100 input100-select bg1" style="padding: 10px;">
							<span class="label-input100">{{ $ctr }}</span>
							<img class="input100" src="{{ URL('/') .'/' .$value->image_path}}" style="width:100%">
						</div>
					@if($ctr % 2 == 0)
						</div>
					@endif
				@endforeach
			</div>

			<div class="container-contact100-form-btn">
				<button class="contact100-form-btn" id="btn-home">
					<span>
						Back to Home
						<i class="fa fa-long-arrow-left m-l-7" aria-hidden="true"></i>
					</span>
				</button>
			</div>
		</div>
	</div>
</div>
@endsection

@section('js_footer')

<script type="text/javascript">
	$(document).ready(function(){
		$("#btn-home").on('click',function(e){
			window.location = "{{ URL('/') }}";
		});
	})
</script>

@endsection