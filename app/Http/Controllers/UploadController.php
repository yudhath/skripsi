<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use \Input as Input;

class UploadController extends Controller
{
    public function upload(Request $request){

    	//upload image
    	if(Input::hasFile('file')){
			if(Input::file('file')->isValid()){
				$file = Input::file('file');
				$file_size = $file->getSize();

				// Image size must be < 2MB
				if($file_size/1000 > 2000){
					echo 'Image size more than 2MB.';
				}else{
					echo 'Image have been uploaded<br>';
					$file_name = $file->getClientOriginalName();
					$file_extension = $file->getClientOriginalExtension();
					$file->move('query_images', $file_name);
					$image_url = URL('/query_images') . '/' . $file_name;

					$clean_file_name_temp = explode('.', $file_name);
					$clean_file_name = $clean_file_name_temp[0];

					// $imgrey = imagecreatefromjpeg('query_images/'.$file_name);
					
					// imagefilter($imgrey, IMG_FILTER_GRAYSCALE);
					// imagejpeg($imgrey, 'query_images/0kaybirdGREY.jpg');

					// imagedestroy($imgrey);
					$this->sobel_edge_detection($image_url,$file_name,$file_extension,$clean_file_name);
					// $imrgb = imagecreatefromjpeg($image_url);
					// $imgrey = imagecreatefromjpeg($image_url);
					// $imgaussianblur = imagecreatefromjpeg($image_url);
				}
			}
		}
		else{
			echo 'There is no image';
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

	public function sobel_edge_detection($image_url,$file_name,$file_extension,$clean_file_name){
		// a butterfly image picked on flickr
		// $source_image = "https://assets-a1.kompasiana.com/items/album/2016/08/09/kupu-kupu-57a95a61b17a61520786ca96.jpg";
		 
		// creating the image
		$starting_img = imagecreatefromjpeg('query_images/'.$file_name);
		// for gaussian blur
		imagefilter($starting_img,IMG_FILTER_GAUSSIAN_BLUR);
		 
		// getting image information (I need only width and height)
		$im_data = getimagesize($image_url);
		 
		// this will be the final image, same width and height of the original
		$final = imagecreatetruecolor($im_data[0],$im_data[1]);
		 
		// looping through ALL pixels!!
		for($x=1;$x<$im_data[0]-1;$x++){
		    for($y=1;$y<$im_data[1]-1;$y++){
		        // getting gray value of all surrounding pixels
		        $pixel_up = $this->get_luminance(imagecolorat($starting_img,$x,$y-1));
		        $pixel_down = $this->get_luminance(imagecolorat($starting_img,$x,$y+1)); 
		        $pixel_left = $this->get_luminance(imagecolorat($starting_img,$x-1,$y));
		        $pixel_right = $this->get_luminance(imagecolorat($starting_img,$x+1,$y));
		        $pixel_up_left = $this->get_luminance(imagecolorat($starting_img,$x-1,$y-1));
		        $pixel_up_right = $this->get_luminance(imagecolorat($starting_img,$x+1,$y-1));
		        $pixel_down_left = $this->get_luminance(imagecolorat($starting_img,$x-1,$y+1));
		        $pixel_down_right = $this->get_luminance(imagecolorat($starting_img,$x+1,$y+1));
		        
		        // appliying convolution mask
		        $conv_x = ($pixel_up_right+($pixel_right*2)+$pixel_down_right)-($pixel_up_left+($pixel_left*2)+$pixel_down_left);
		        $conv_y = ($pixel_up_left+($pixel_up*2)+$pixel_up_right)-($pixel_down_left+($pixel_down*2)+$pixel_down_right);
		        
		        // calculating the distance
		        // $gray = sqrt($conv_x*$conv_x+$conv_y+$conv_y);
		        // applying Manhattan Distance
		        $gray = abs($conv_x)+abs($conv_y);
		        
		        // inverting the distance not to get the negative image                
		        $gray = 255-$gray;
		        
		        // adjusting distance if it's greater than 255 or less than zero (out of color range)
		        if($gray > 255){
		            $gray = 255;
		        }
		        if($gray < 0){
		            $gray = 0;
		        }
		        
		        // creation of the new gray
		        $new_gray  = imagecolorallocate($final,(int)$gray,(int)$gray,(int)$gray);
		        
		        // adding the gray pixel to the new image        
		        imagesetpixel($final,$x,$y,$new_gray);            
		    }
		}
		 
		// telling the browser we are going to output a jpeg image
		// header('Content-Type: image/jpeg');
		 
		// creation of the final image
		imagejpeg($final,'query_images/'.$clean_file_name."EDGESOBEL.".$file_extension);
		 
		// freeing memory
		imagedestroy($starting_img);
		imagedestroy($final);
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
}
