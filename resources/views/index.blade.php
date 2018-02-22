<!DOCTYPE html>
<html>
<head>
	<title></title>
	<link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
</head>
<body>
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