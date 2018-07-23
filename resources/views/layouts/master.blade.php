<!DOCTYPE html>
<html lang="en">
<head>
	<title>CBIR - Local Color Histogram & GLCM</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->
	<link rel="shortcut icon" type="image/x-icon" href="{{ URL('/') }}/images/icons/favicon.ico?v=1"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ URL('/') }}/vendor/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ URL('/') }}/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ URL('/') }}/fonts/iconic/css/material-design-iconic-font.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ URL('/') }}/vendor/animate/animate.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ URL('/') }}/vendor/css-hamburgers/hamburgers.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ URL('/') }}/vendor/animsition/css/animsition.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ URL('/') }}/vendor/select2/select2.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ URL('/') }}/vendor/daterangepicker/daterangepicker.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ URL('/') }}/vendor/noui/nouislider.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="{{ URL('/') }}/css/util.css">
	<link rel="stylesheet" type="text/css" href="{{ URL('/') }}/css/main.css">
<!--===============================================================================================-->
</head>
<body>
    @yield('content')

<!--===============================================================================================-->
	<script src="{{ URL('/') }}/vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="{{ URL('/') }}/vendor/animsition/js/animsition.min.js"></script>
<!--===============================================================================================-->
	<script src="{{ URL('/') }}/vendor/bootstrap/js/popper.js"></script>
	<script src="{{ URL('/') }}/vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="{{ URL('/') }}/vendor/select2/select2.min.js"></script>
	<script>
		$(".js-select2").each(function(){
			$(this).select2({
				minimumResultsForSearch: 20,
				dropdownParent: $(this).next('.dropDownSelect2')
			});


			$(".js-select2").each(function(){
				$(this).on('select2:close', function (e){
					if($(this).val() == "Please chooses") {
						$('.js-show-service').slideUp();
					}
					else {
						$('.js-show-service').slideUp();
						$('.js-show-service').slideDown();
					}
				});
			});
		})
	</script>
<!--===============================================================================================-->
	<script src="{{ URL('/') }}/vendor/daterangepicker/moment.min.js"></script>
	<script src="{{ URL('/') }}/vendor/daterangepicker/daterangepicker.js"></script>
<!--===============================================================================================-->
	<script src="{{ URL('/') }}/vendor/countdowntime/countdowntime.js"></script>
<!--===============================================================================================-->
	<script src="{{ URL('/') }}/vendor/noui/nouislider.min.js"></script>
	<script>
	    // var filterBar = document.getElementById('filter-bar');

	    // noUiSlider.create(filterBar, {
	    //     start: [ 1500, 3900 ],
	    //     connect: true,
	    //     range: {
	    //         'min': 1500,
	    //         'max': 7500
	    //     }
	    // });

	    // var skipValues = [
	    // document.getElementById('value-lower'),
	    // document.getElementById('value-upper')
	    // ];

	    // filterBar.noUiSlider.on('update', function( values, handle ) {
	    //     skipValues[handle].innerHTML = Math.round(values[handle]);
	    //     $('.contact100-form-range-value input[name="from-value"]').val($('#value-lower').html());
	    //     $('.contact100-form-range-value input[name="to-value"]').val($('#value-upper').html());
	    // });
	</script>
<!--===============================================================================================-->
	<script src="{{ URL('/') }}/js/main.js"></script>

@yield('js_footer')

</body>
</html>
