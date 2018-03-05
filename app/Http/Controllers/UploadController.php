<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use \Input as Input;

class UploadController extends Controller
{
    public function upload(Request $request){
    	ini_set('max_execution_time', 300);

    	//upload image
    	if(Input::hasFile('file')){
			if(Input::file('file')->isValid()){
				$file = Input::file('file');
				$file_size = $file->getSize();

				// Image size must be < 2MB
				if($file_size/1000 > 2000){
					return redirect()->back()->with('error', ['Image size must not more than 2MB']);
				}else{
					
					$file_name = $file->getClientOriginalName();
					$file_extension = $file->getClientOriginalExtension();
					$file->move('query_images', $file_name);
					$image_url = URL('/query_images') . '/' . $file_name;

					$clean_file_name_temp = explode('.', $file_name);
					$clean_file_name = $clean_file_name_temp[0];

					// $imgrey = imagecreatefromjpeg('query_images/'.$file_name);
					
					// imagefilter($imgrey, IMG_FILTER_GAUSSIAN_BLUR);
					// imagejpeg($imgrey, 'query_images/0kaybirdGREY.jpg');

					// imagedestroy($imgrey);

					$this->sobel_edge_detection($image_url,$file_name,$file_extension,$clean_file_name);
					$this->canny_edge_detection($image_url,$file_name,$file_extension,$clean_file_name);
					$this->glcm_image($image_url,$file_name,$file_extension,$clean_file_name);

					// $imrgb = imagecreatefromjpeg($image_url);
					// $imgrey = imagecreatefromjpeg($image_url);
					// $imgaussianblur = imagecreatefromjpeg($image_url);
					return redirect()->back()->with('success', ['Image have been uploaded']);
				}
			}
		}
		else{
			return redirect()->back()->with('error', ['There is no image']);
		}
    }

    // function to get the luminance value
	public function get_luminance($pixel){
	    $pixel = sprintf('%06x',$pixel);
	    $red = hexdec(substr($pixel,0,2))*0.30;
	    $green = hexdec(substr($pixel,2,2))*0.59;
	    $blue = hexdec(substr($pixel,4))*0.11;
	    return $red+$green+$blue;
	}

	public function RGBtoHSV($r, $g, $b) {
		$r = $r/255.; // convert to range 0..1
		$g = $g/255.;
		$b = $b/255.;
		$cols = array("r" => $r, "g" => $g, "b" => $b);
		asort($cols, SORT_NUMERIC);
		$min = key(array_slice($cols, 1)); // "r", "g" or "b"
		$max = key(array_slice($cols, -1)); // "r", "g" or "b"
		// hue
		if($cols[$min] == $cols[$max]) {
			$h = 0;
		} else {
			if($max == "r") {
				$h = 60. * ( 0 + ( ($cols["g"]-$cols["b"]) / ($cols[$max]-$cols[$min]) ) );
			} elseif ($max == "g") {
				$h = 60. * ( 2 + ( ($cols["b"]-$cols["r"]) / ($cols[$max]-$cols[$min]) ) );
			} elseif ($max == "b") {
				$h = 60. * ( 4 + ( ($cols["r"]-$cols["g"]) / ($cols[$max]-$cols[$min]) ) );
			}
			if($h < 0) {
				$h += 360;
			}
		}
		// saturation
		if($cols[$max] == 0) {
			$s = 0;
		} else {
			$s = ( ($cols[$max]-$cols[$min])/$cols[$max] );
			$s = $s * 255;
		}
		// lightness
		$v = $cols[$max];
		$v = $v * 255;
		return(array($h, $s, $v));
	}

	public function sobel_edge_detection($image_url,$file_name,$file_extension,$clean_file_name){
		// a butterfly image picked on flickr
		// $source_image = "https://assets-a1.kompasiana.com/items/album/2016/08/09/kupu-kupu-57a95a61b17a61520786ca96.jpg";
		 
		##CREATE THE IMAGE
		$starting_img = imagecreatefromjpeg('query_images/'.$file_name);
		
		##APPLY GAUSSIAN BLUR IN IMAGE
		imagefilter($starting_img,IMG_FILTER_GAUSSIAN_BLUR);
		 
		##GET IMAGE SIZE (WIDTH AND HEIGHT)
		$im_data = getimagesize($image_url);
		 
		##this will be the final image, same width and height of the original
		$final = imagecreatetruecolor($im_data[0],$im_data[1]);
		 
		##LOOPING THROUGH ALL PIXEL
		for($x=1;$x<$im_data[0]-1;$x++){
		    for($y=1;$y<$im_data[1]-1;$y++){
		        ##GETTING GRAY VALUE OF ALL SURROUNDING PIXELS (ALL NEIGHBOUR)
		        $pixel_up = $this->get_luminance(imagecolorat($starting_img,$x,$y-1));
		        $pixel_down = $this->get_luminance(imagecolorat($starting_img,$x,$y+1)); 
		        $pixel_left = $this->get_luminance(imagecolorat($starting_img,$x-1,$y));
		        $pixel_right = $this->get_luminance(imagecolorat($starting_img,$x+1,$y));
		        $pixel_up_left = $this->get_luminance(imagecolorat($starting_img,$x-1,$y-1));
		        $pixel_up_right = $this->get_luminance(imagecolorat($starting_img,$x+1,$y-1));
		        $pixel_down_left = $this->get_luminance(imagecolorat($starting_img,$x-1,$y+1));
		        $pixel_down_right = $this->get_luminance(imagecolorat($starting_img,$x+1,$y+1));
		        
		        ##APPLYING CONVOLUTION MASK
		        $conv_x = ($pixel_up_right+($pixel_right*2)+$pixel_down_right)-($pixel_up_left+($pixel_left*2)+$pixel_down_left);
		        $conv_y = ($pixel_up_left+($pixel_up*2)+$pixel_up_right)-($pixel_down_left+($pixel_down*2)+$pixel_down_right);
		        
		        // calculating the distance
		        // $gray = sqrt($conv_x*$conv_x+$conv_y+$conv_y);

		        ##APPLYING MANHATTAN DISTANCE
		        $gray = abs($conv_x)+abs($conv_y);
		        
		        ##inverting the distance not to get the negative image                
		        $gray = 255-$gray;
		        
		        ##adjusting distance if it's greater than 255 or less than zero (out of color range)
		        if($gray > 255){
		            $gray = 255;
		        }
		        if($gray < 0){
		            $gray = 0;
		        }
		        
		        ##creation of the new gray
		        $new_gray  = imagecolorallocate($final,(int)$gray,(int)$gray,(int)$gray);
		        
		        ##ADDING / SET THE GRAY PIXEL TO THE NEW IMAGE
		        imagesetpixel($final,$x,$y,$new_gray);            
		    }
		}
		 
		##telling the browser we are going to output a jpeg image
		// header('Content-Type: image/jpeg');
		 
		##CREATION OF THE FINAL IMAGE
		imagejpeg($final,'query_images/'.$clean_file_name."_EDGESOBEL.".$file_extension);
		 
		##FREEING MEMORY
		imagedestroy($starting_img);
		imagedestroy($final);
	}

	public function canny_edge_detection($image_url,$file_name,$file_extension,$clean_file_name){
		$dimensions = getimagesize($image_url);
		$w = $dimensions[0]; // width
		$h = $dimensions[1]; // height
		$im = imagecreatefromjpeg('query_images/'.$file_name);
		imagefilter($im,IMG_FILTER_GAUSSIAN_BLUR);
		for($hi=0; $hi < $h; $hi++) {
			for($wi=0; $wi < $w; $wi++) {
				$rgb = imagecolorat($im, $wi, $hi);
				$r = ($rgb >> 16) & 0xFF;
				$g = ($rgb >> 8) & 0xFF;
				$b = $rgb & 0xFF;
				$hsv = $this->RGBtoHSV($r, $g, $b);
				if($hi < $h-1) {
					// compare pixel below with current pixel
					$brgb = imagecolorat($im, $wi, $hi+1);
					$br = ($brgb >> 16) & 0xFF;
					$bg = ($brgb >> 8) & 0xFF;
					$bb = $brgb & 0xFF;
					$bhsv = $this->RGBtoHSV($br, $bg, $bb);
					// if difference in hue is bigger than 20, make this pixel white (edge), otherwise black
					if($bhsv[2]-$hsv[2] > 20) {
						imagesetpixel($im, $wi, $hi, imagecolorallocate($im, 255, 255, 255));
					} else {
						imagesetpixel($im, $wi, $hi, imagecolorallocate($im, 0, 0, 0));
					}
					
				}
			}
		}
		// header('Content-Type: image/jpeg');

		##CREATION OF THE FINAL IMAGE
		imagejpeg($im,'query_images/'.$clean_file_name."_EDGECANNY.".$file_extension);

		##FREEING MEMORY
		imagedestroy($im);
	}

	public function glcm_image($image_url,$file_name,$file_extension,$clean_file_name){
		##GET IMAGE SIZE
		$dimensions = getimagesize($image_url);
		$width = $dimensions[0]; // width
		$height = $dimensions[1]; // height

		##CREATE THE IMAGE
		$im = imagecreatefromjpeg('query_images/'.$file_name);
		// imagefilter($im,IMG_FILTER_GRAYSCALE); // convert image color into grayscale

		##INITIALIZE ARRAY
		$original_image = Array();
		$normalize_image = Array();
		$glcm_0 = Array();
		$glcm_0_tr = Array();
		$glcm_45 = Array();
		$glcm_45_tr = Array();
		$glcm_90 = Array();
		$glcm_90_tr = Array();
		$glcm_135 = Array();
		$glcm_135_tr = Array();

		##CONVERT RGB IMAGE INTO GRAYCALE IMAGE
		for($h = 0 ; $h < $height ; $h++){
			for($w = 0 ; $w < $width ; $w++){
				$rgb = imagecolorat($im,$w,$h);

				$red   = ($rgb >> 16) & 0xFF;
			    $green = ($rgb >> 8) & 0xFF;
			    $blue  = $rgb & 0xFF;
			    
				$gray = round(($red + $green + $blue) / 3);

				##INSERT GRAY COLOR PIXEL INTO ARRAY 2 DIMENSION
				$original_image[$h][$w] = $gray;

				##START NORMALIZE GRAY COLOR

				/*
				0   - 31  = 0
				32  - 63  = 1
				64  - 95  = 2
				96  - 127 = 3
				128 - 159 = 4
				160 - 191 = 5
				192 - 223 = 6
				224 - 255 = 7
				*/

				if($gray < 32){
					$normalize_image[$h][$w] = 0;
				}else if($gray < 64){
					$normalize_image[$h][$w] = 1;
				}else if($gray < 96){
					$normalize_image[$h][$w] = 2;
				}else if($gray < 128){
					$normalize_image[$h][$w] = 3;
				}else if($gray < 160){
					$normalize_image[$h][$w] = 4;
				}else if($gray < 192){
					$normalize_image[$h][$w] = 5;
				}else if($gray < 224){
					$normalize_image[$h][$w] = 6;
				}else if($gray < 256){
					$normalize_image[$h][$w] = 7;
				}

				##END NORMALIZE GRAY COLOR

				##SET PIXEL FOR THE NEW GRAYSCALE IMAGE
				imagesetpixel($im, $w, $h, imagecolorallocate($im, $gray, $gray, $gray));
			}
		}

		##CONVERT INTO GLCM MATRICES
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$count = 0;
				##COUNTING THE MATRICES FOR 0 DEGREE GLCM
				for($h = 0 ; $h < $height ; $h++){
					for($w = 0 ; $w < $width ; $w++){
						if($w < ($width-1)){
							if($normalize_image[$h][$w] == $i && $normalize_image[$h][$w+1] == $j){
								$count += 1;
							}
						}
					}
				}
				$glcm_0[$i][$j] = $count;
			}
		}

		
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_0_tr[$j][$i] = $glcm_0[$i][$j];
			}
		}


		// for($i = 0 ; $i < 8 ; $i++){
		// 	for($j = 0 ; $j < 8 ; $j++){
		// 		echo $glcm_0[$i][$j] . "&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp";
		// 	}
		// 	echo "<br>";
		// }

		##CREATION OF THE FINAL IMAGE
		imagejpeg($im,'query_images/'.$clean_file_name.'_GRAYSCALE.'.$file_extension);

		##FREEING MEMORY
		imagedestroy($im);
	}
}
