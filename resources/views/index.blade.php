<!DOCTYPE html>
<html>
<head>
	<title></title>
	<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body>
	@php
		function print_block($data, $title="PRINT BLOCK") {
	        echo "<div style='margin:20px; padding:10px; border:1px solid #666; box-shadow:0px 0px 10px #ccc; border-radius:6px;'>";
	        echo "  <div style='padding:10px 5px; margin-bottom:10px; font-weight:bold; font-size:120%; border-bottom:1px solid #666'>".$title."</div>";
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
			<h1>File Upload</h1>
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
				<label>Select image to upload:</label>
			    <input type="file" name="file" id="file">
			    <input type="submit" value="Upload" name="submit">
				<input type="hidden" value="{{ csrf_token() }}" name="_token">
			</form>
		</div>
	</div>
</body>
</html>