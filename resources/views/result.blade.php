<!DOCTYPE html>
<html>
<head>
	<title></title>
</head>
<body>
	@foreach($result as $value)
		<div>
			<img src="{{ URL('/') .'/' .$value->image_path}}">
		</div>
	@endforeach
</body>
</html>