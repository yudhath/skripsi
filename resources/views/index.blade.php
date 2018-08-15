@extends('layouts.master')
@php
	function print_block($data, $title="PRINT BLOCK") {
        echo "<div style='margin:20px; padding:10px; border:1px solid #777; box-shadow:0px 0px 10px #ccc; border-radius:7px;'>";
        echo "  <div style='padding:10px 5px; margin-bottom:10px; font-weight:bold; font-size:120%; border-bottom:1px solid #777'>".$title."</div>";
        if(is_array($data) OR is_object($data)) {
            echo "<pre>";
            print_r($data);
            echo "</pre>";
        } else {
            echo $data;
        }
        echo "</div>";
    }
@endphp

@section('content')
	<div class="container-contact100">
		<div class="wrap-contact100">
			<form class="contact100-form validate-form" action="{{ URL::to('upload') }}" method="post" enctype="multipart/form-data">
			
				<span class="contact100-form-title">
					CBIR - Local Color Histogram & GLCM
				</span>
				@if (Session::has('success'))
				    <div class="alert alert-success">
				        <ul>
				            <li>{!! Session::get('success')[0] !!}</li>
				        </ul>
				    </div>
			    @elseif(Session::has('error'))
				    <div class="alert alert-danger">
				        <ul>
				            <li>{!! Session::get('error')[0] !!}</li>
				        </ul>
				    </div>
				@endif

				
				<div class="wrap-input100 input100-select bg1">
					<span class="label-input100">Choose CBIR Type :</span>
					<div>
						<select class="js-select2" name="cbir_type">
							<option value="1">Color</option>
							<option value="2">Texture</option>
							<option value="3">Color & Texture</option>
						</select>
						<div class="dropDownSelect2"></div>
					</div>
				</div>
				<div class="wrap-input100 bg1 rs1-wrap-input100" id="image_section">
					<div class="wrap-input100 input100-select bg1">
						<span class="label-input100">Choose Image Size :</span>
						<div>
							<select class="js-select2" name="image_size_grid">
								<option value="4">4 x 4</option>
								<option value="6">6 x 6</option>
								<option value="8">8 x 8</option>
							</select>
							<div class="dropDownSelect2"></div>
						</div>
					</div>
					<div class="wrap-input100 input100-select bg1">
						<span class="label-input100">Choose Image Color Quantization :</span>
						<div>
							<select class="js-select2" name="image_color_quantization">
								<option value="32">32</option>
								<option value="64">64</option>
								<option value="128">128</option>
							</select>
							<div class="dropDownSelect2"></div>
						</div>
					</div>
				</div>

				<div class="wrap-input100 bg1 rs1-wrap-input100" id="weight_section">
					<div class="wrap-input100 bg1 rs1-wrap-input100">
						<span class="label-input100">Color Weight :</span>
						<input class="input100" type="number" name="color_weight" value="100">
					</div>

					<div class="wrap-input100 bg1 rs1-wrap-input100">
						<span class="label-input100">Texture Weight :</span>
						<input class="input100" type="number" name="texture_weight" value="0">
					</div>
				</div>

				<div class="wrap-input100 bg1 rs1-wrap-input100">
					<span class="label-input100">Select image to upload :</span>
					<input class="input100" type="file" name="file" id="file">
					<span id="imgPrevDiv"></span>
				</div>
			    
			    <div class="container-contact100-form-btn">
					<button class="contact100-form-btn">
						<span>
							Upload
							<i class="fa fa-long-arrow-right m-l-7" aria-hidden="true"></i>
						</span>
					</button>
				</div>
				<input type="hidden" value="{{ csrf_token() }}" name="_token">
				
			</form>
		</div>
		<div class="container-contact100-form-btn">
			<a href="{{ URL('/') }}/about-us">
				<span>
					<i class="fa fa-user m-l-7" aria-hidden="true"></i>
					ABOUT US
				</span>
			</a>
		</div>
	</div>
	
@endsection

@section('js_footer')
	<script type="text/javascript">
		$(document).ready(function(){
			var cbir_type = $("select[name='cbir_type']").val();

			if(cbir_type == 2){
				$("#image_section").css({'display' : 'none'});
			}else{
				$("#image_section").css({'display' : 'block'});
			}

			if(cbir_type == 3){
				$("#weight_section").css({'display' : 'block'});
			}else{
				$("#weight_section").css({'display' : 'none'});
			}

			$("select[name='cbir_type']").on("change",function(){
				var cbir_type = $("select[name='cbir_type']").val();
				if(cbir_type == 2){
					$("#image_section").css({'display' : 'none'});
				}else{
					$("#image_section").css({'display' : 'block'});
				}

				if(cbir_type == 3){
					$("#weight_section").css({'display' : 'block'});
				}else{
					$("#weight_section").css({'display' : 'none'});
				}
			});

			$("input[name='color_weight']").on("keyup change",function(){
				var color_weight = $("input[name='color_weight']").val();

				if(color_weight < 0){
					$("input[name='color_weight']").val(0);
					$("input[name='texture_weight']").val(0);
				}else if(color_weight > 100){
					$("input[name='color_weight']").val(100);
					$("input[name='texture_weight']").val(0);
				}else{
					$("input[name='color_weight']").val(color_weight);
					$("input[name='texture_weight']").val(100-color_weight);
				}
			});

			$("input[name='texture_weight']").on("keyup change",function(){
				var texture_weight = $("input[name='texture_weight']").val();

				if(texture_weight < 0){
					$("input[name='color_weight']").val(0);
					$("input[name='texture_weight']").val(0);
				}else if(texture_weight > 100){
					$("input[name='color_weight']").val(0);
					$("input[name='texture_weight']").val(100);
				}else{
					$("input[name='color_weight']").val(100-texture_weight);
					$("input[name='texture_weight']").val(texture_weight);
				}
			});

			function readURL(input) {
			    if (input.files && input.files[0]) {
			        var reader = new FileReader();

			        reader.onload = function (e) {
			            $('#imgPrev').attr('src', e.target.result);
			        }

			        if(input.files[0] != ""){
			        	$("#imgPrevDiv").html('<img class="wrap-input100" src="#" id="imgPrev" alt="Query Image">');
			        }else{
			        	$("#imgPrevDiv").html('<span></span>');
			        }
			        reader.readAsDataURL(input.files[0]);
			    }
			}

			$("#file").change(function(){
			    readURL(this);
			});

			
		});
	</script>
@endsection