<!DOCTYPE html>
<html>
<head>
	<title></title>
	<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
	<script type="text/javascript" src="{{URL('/')}}/js/jquery.js"></script>
</head>
<body>
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
	<div class="container">
		<div class="content">
			<h1>CBIR - Local Color Histogram & GLCM</h1>
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
			<form action="{{ URL::to('upload') }}" method="post" enctype="multipart/form-data">
				<label>Choose CBIR Type :</label>
				<select name="cbir_type">
					<option value="1">Color</option>
					<option value="2">Texture</option>
					<option value="3">Color & Texture</option>
				</select>
				<br>
				<div id="image_section">
					<label>Choose Image Size :</label>
					<select name="image_size_grid">
						<option value="3">3 x 3</option>
						<option value="5">5 x 5</option>
						<option value="7">7 x 7</option>
					</select>
					<br>
					<label>Choose Image Color Quantization :</label>
					<select name="image_color_quantization">
						<option value="32">32</option>
						<option value="64">64</option>
						<option value="128">128</option>
					</select>
				</div>

				<div id="weight_section">
					<label>Weight :</label>
					<br>
					<label>Color : </label>
					<input type="number" name="color_weight" value="100">
					<label><strong>%</strong></label>
					<br>
					<label>Texture : </label>
					<input type="number" name="texture_weight" value="0">
					<label><strong>%</strong></label>
				</div>

				<label>Select image to upload :</label>
			    <input type="file" name="file" id="file">
			    <input type="submit" value="Upload" name="submit">
				<input type="hidden" value="{{ csrf_token() }}" name="_token">
			</form>
		</div>
	</div>

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

			
		});
	</script>
</body>
</html>