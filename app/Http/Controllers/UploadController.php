<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use \Input as Input;
use DB;

class UploadController extends Controller
{
    public function upload(Request $request){
    	ini_set('max_execution_time', 300);
    	$param = Input::get();
    	$cbir_type = $param['cbir_type'];
    	
    	if($cbir_type == 1){
    		$image_size_grid			= $param['image_size_grid']; // IMAGE SIZE FOR COLOR RETRIEVAL 3 x 3 / 5 x 5 / 7 x 7
    		$image_color_quantization 	= $param['image_color_quantization'];
    	}else if($cbir_type == 3){
    		$image_size_grid			= $param['image_size_grid']; // IMAGE SIZE FOR COLOR RETRIEVAL 3 x 3 / 5 x 5 / 7 x 7
    		$image_color_quantization 	= $param['image_color_quantization'];
    		$color_weight 				= $param['color_weight'];
    		$texture_weight 			= $param['texture_weight'];
    	}
    	
    	//upload image
    	if(Input::hasFile('file')){
			if(Input::file('file')->isValid()){
				$file = Input::file('file');
				$file_size = $file->getSize();

				// Image size must be < 2MB
				if($file_size/1000 > 2000){
					return redirect()->back()->with('error', ['Image size must not more than 2MB']);
				}else{

					$date = date("YmdHis");
					$file_name = $file->getClientOriginalName();
					$file_extension = $file->getClientOriginalExtension();
					$clean_file_name_temp = explode('.', $file_name);
					$clean_file_name = $clean_file_name_temp[0];
					$file_name = $clean_file_name.$date.'.'.$file_extension;
					$file->move('query_images', $file_name);
					$image_url = URL('/query_images') . '/' . $file_name;

					// $imgrey = imagecreatefromjpeg('query_images/'.$file_name);
					
					// imagefilter($imgrey, IMG_FILTER_GAUSSIAN_BLUR);
					// imagejpeg($imgrey, 'query_images/0kaybirdGREY.jpg');

					// imagedestroy($imgrey);

					// $this->sobel_edge_detection($image_url,$file_name,$file_extension,$clean_file_name);
					// return $this->canny_edge_detection($image_url,$file_name,$file_extension,$clean_file_name);

					// return $color_features = $this->local_color_histogram($image_url,$file_name,$file_extension,$clean_file_name,'query_images/');


					
					$id_image_query = DB::table('image_query')->insertGetId(['image_path' => 'query_images/'.$file_name]);
					
					if($cbir_type == 1 || $cbir_type == 3){
						##START COLOR HISTOGRAM
						//
						$histogram_query = $this->local_color_histogram($image_url,$file_name,$file_extension,$clean_file_name,'query_images/',$image_size_grid,$image_color_quantization);

						$image_data = DB::select('select * from image_data');
						foreach ($image_data as $value) {
							if($value->id > 0){
								$image_data_file_path = $value->image_path;
								$image_data_array_dot = explode('.', $image_data_file_path);
								$image_data_array_slash = explode('/', $image_data_file_path);
								$image_data_file_name = $image_data_array_slash[2];
								$image_data_clean_file_name_temp = explode('.',$image_data_file_name);
								$image_data_file_extension = $image_data_array_dot[1];
								$image_data_clean_file_name = $image_data_clean_file_name_temp[0];
								$image_data_url = URL('/query_images/database_image'). '/' . $image_data_file_name;

								$euclidean_distance = $this->local_color_histogram_distance($image_data_url,$image_data_file_name,$image_data_file_extension,$image_data_clean_file_name,'query_images/database_image/',$histogram_query,$image_size_grid,$image_color_quantization);

								DB::table('image_color_distance')->insert([
									'id_image_query'	 => $id_image_query,
								    'id_image_data' 	 => $value->id,
								    'euclidean_distance' => $euclidean_distance
									]
								);
							}
						}

						# QUERY RESULT FOR COLOR RETRIEVAL
						// $result_arr = DB::select('SELECT id.image_path
						// 					FROM image_color_distance icd
						// 					JOIN image_data id ON id.id = icd.id_image_data
						// 					JOIN image_query iq ON iq.id = icd.id_image_query
						// 					WHERE icd.id_image_query = '.$id_image_query.'
						// 					ORDER BY euclidean_distance ASC
						// 					LIMIT 10');
						
						// return view('result',[
						// 	'result' => $result_arr
						// ]);

						//
						##END COLOR HISTOGRAM
					}

					if($cbir_type == 2 || $cbir_type == 3){
						##START GLCM AREA
						//
						$texture_features = $this->glcm_image($image_url,$file_name,$file_extension,$clean_file_name,'query_images/');
						DB::table('image_query_texture')->insert([
								'id_image_query' => $id_image_query,
							    'energy' 		 => $texture_features['energy'],
							    'correlation' 	 => $texture_features['correlation'],
							    'idm' 			 => $texture_features['idm'],
							    'contrast' 		 => $texture_features['contrast']
								]
							);

						$image_query_texture = DB::select('select image_query_texture.* from image_query_texture
															JOIN image_query ON image_query.id = image_query_texture.id_image_query
															WHERE image_query_texture.id_image_query = '.$id_image_query);
						$image_data_texture = DB::select('select * from image_data_texture');

						foreach ($image_data_texture as $value) {
							foreach ($image_query_texture as $value2) {
								$euclidean_distance = sqrt (pow(($value2->energy - $value->energy),2) +
													  pow(($value2->correlation - $value->correlation),2) +
													  pow(($value2->idm - $value->idm),2) +
													  pow(($value2->contrast - $value->contrast),2) );
								$euclidean_distance = round($euclidean_distance,4);

								DB::table('image_texture_distance')->insert([
									'id_image_query'	 => $value2->id_image_query,
								    'id_image_data' 	 => $value->id_image_data,
								    'euclidean_distance' => $euclidean_distance
									]
								);
							}
							
						}

						# QUERY RESULT FOR TEXTURE RETRIEVAL
						// $result_arr = DB::select('SELECT id.image_path
						// 					FROM image_texture_distance itd
						// 					JOIN image_data id ON id.id = itd.id_image_data
						// 					JOIN image_query iq ON iq.id = itd.id_image_query
						// 					WHERE itd.id_image_query = '.$id_image_query.'
						// 					ORDER BY euclidean_distance ASC
						// 					LIMIT 10');
						
						// return view('result',[
						// 	'result' => $result_arr
						// ]);

						//
						##END GLCM AREA
					}
					
					
					

					if($cbir_type == 1){

						# QUERY RESULT FOR COLOR RETRIEVAL
						$result_arr = DB::select('SELECT id.image_path
											FROM image_color_distance icd
											JOIN image_data id ON id.id = icd.id_image_data
											JOIN image_query iq ON iq.id = icd.id_image_query
											WHERE icd.id_image_query = '.$id_image_query.'
											ORDER BY euclidean_distance ASC
											LIMIT 10');

					}else if($cbir_type == 2){

						# QUERY RESULT FOR TEXTURE RETRIEVAL
						$result_arr = DB::select('SELECT id.image_path
											FROM image_texture_distance itd
											JOIN image_data id ON id.id = itd.id_image_data
											JOIN image_query iq ON iq.id = itd.id_image_query
											WHERE itd.id_image_query = '.$id_image_query.'
											ORDER BY euclidean_distance ASC
											LIMIT 10');

					}else if($cbir_type == 3){

						$result_arr = DB::select('SELECT DISTINCT id.image_path, (('.($color_weight/100).'*(icd.euclidean_distance/(SELECT MAX(euclidean_distance) FROM image_color_distance where id_image_query = '.$id_image_query.')))+('.($texture_weight/100).'*(itd.euclidean_distance/(SELECT MAX(euclidean_distance) FROM image_texture_distance where id_image_query = '.$id_image_query.')))) Euclidean_Distance
										FROM image_color_distance icd
										JOIN image_data id ON id.id = icd.id_image_data
										JOIN image_query iq ON iq.id = icd.id_image_query
										JOIN image_texture_distance itd ON itd.id_image_data = icd.id_image_data
										WHERE icd.id_image_query = '.$id_image_query.' AND itd.id_image_query = '.$id_image_query.'
										ORDER BY 2 ASC
										LIMIT 10');

					}
					

					return view('result',[
						'result' => $result_arr
					]);

					return redirect()->back()->with('success', ['Image have been uploaded']);
				}
			}
		}
		else{
			return redirect()->back()->with('error', ['There is no image']);
		}
    }

    public function data_training_texture(){
    	## START GLCM DATA TRAINING

		# INSERT DATABASE TRAINING IMAGE
		// for ($i=1; $i <= 250; $i++) { 
		// 	DB::table('image_data')->insert([
		// 		'id' => $i,
		// 		'image_path' => 'query_images/database_image/'.$i.'.jpg'
		// 	]);
		// }

		# EXTRACT TEXTURE FEATURE TRAINING IMAGE
		
		$image_data = DB::select('select * from image_data');
		foreach ($image_data as $value) {
			if($value->id > 0){
				$image_data_file_path = $value->image_path;
				$image_data_array_dot = explode('.', $image_data_file_path);
				$image_data_array_slash = explode('/', $image_data_file_path);
				$image_data_file_name = $image_data_array_slash[2];
				$image_data_clean_file_name_temp = explode('.',$image_data_file_name);
				$image_data_file_extension = $image_data_array_dot[1];
				$image_data_clean_file_name = $image_data_clean_file_name_temp[0];
				$image_data_url = URL('/query_images/database_image'). '/' . $image_data_file_name;
				
				
				$texture_features = $this->glcm_image($image_data_url,$image_data_file_name,$image_data_file_extension,$image_data_clean_file_name,'query_images/database_image/');

				DB::table('image_data_texture')->insert([
					'id_image_data' => $value->id,
				    'energy' 		=> $texture_features['energy'],
				    'correlation' 	=> $texture_features['correlation'],
				    'idm' 			=> $texture_features['idm'],
				    'contrast' 		=> $texture_features['contrast']
					]
				);
			}
			
		}
		
		## END GLCM DATA TRAINING
    }

    // function to get the luminance value (intensitas warna)
	public function get_luminance($pixel){
	    $pixel = sprintf('%06x',$pixel);
	    $red = hexdec(substr($pixel,0,2))*0.30;
	    $green = hexdec(substr($pixel,2,2))*0.59;
	    $blue = hexdec(substr($pixel,4))*0.11;
	    return $red+$green+$blue;
	}

	public function convert_to_gray($pixel){
	    $pixel = sprintf('%06x',$pixel);
	    $red = hexdec(substr($pixel,0,2))*0.30;
	    $green = hexdec(substr($pixel,2,2))*0.59;
	    $blue = hexdec(substr($pixel,4))*0.11;
	    return $red+$green+$blue/3;
	}

	public function get_gray_color($pixel){
	    return ($pixel >> 16) & 0xFF;
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
		// imagefilter($starting_img,IMG_FILTER_EDGEDETECT);
		 
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
		// imagejpeg($starting_img,'query_images/'.$clean_file_name."_EDGEDETECT.".$file_extension);
		 
		##FREEING MEMORY
		imagedestroy($starting_img);
		imagedestroy($final);
	}

	public function canny_edge_detection($image_url,$file_name,$file_extension,$clean_file_name){

		$dimensions = getimagesize($image_url);
		$width = $dimensions[0]; // width
		$height = $dimensions[1]; // height
		$temp_x = 0;
		$temp_y = 0;

		$im = imagecreatefromjpeg('query_images/'.$file_name);

		imagefilter($im,IMG_FILTER_GAUSSIAN_BLUR);

		$edge_detect = imagecreatetruecolor($width, $height);

		##CONVERT RGB IMAGE INTO GRAYCALE IMAGE
		for($h = 0 ; $h < $height ; $h++){
			for($w = 0 ; $w < $width ; $w++){
				$rgb = imagecolorat($im,$w,$h);

				$red   = ($rgb >> 16) & 0xFF;
			    $green = ($rgb >> 8) & 0xFF;
			    $blue  = $rgb & 0xFF;
			    
				$gray = round(($red + $green + $blue) / 3);

				##INSERT GRAY COLOR PIXEL INTO ARRAY 2 DIMENSION
				$grayscale_image[$h][$w] = $gray;

				##SET PIXEL FOR THE NEW GRAYSCALE IMAGE
				imagesetpixel($im, $w, $h, imagecolorallocate($im, $gray, $gray, $gray));
			}
		}

		imagejpeg($im,'query_images/'.$clean_file_name."_GRAYSCALE.".$file_extension);

		##LOOPING THROUGH ALL PIXEL
		for($x=1;$x<$width-1;$x++){
		    for($y=1;$y<$height-1;$y++){
		        ##GETTING GRAY VALUE OF ALL SURROUNDING PIXELS (ALL NEIGHBOUR)
		        $pixel_up = $this->get_gray_color(imagecolorat($im,$x,$y-1));
		        $pixel_down = $this->get_gray_color(imagecolorat($im,$x,$y+1)); 
		        $pixel_left = $this->get_gray_color(imagecolorat($im,$x-1,$y));
		        $pixel_right = $this->get_gray_color(imagecolorat($im,$x+1,$y));
		        $pixel_up_left = $this->get_gray_color(imagecolorat($im,$x-1,$y-1));
		        $pixel_up_right = $this->get_gray_color(imagecolorat($im,$x+1,$y-1));
		        $pixel_down_left = $this->get_gray_color(imagecolorat($im,$x-1,$y+1));
		        $pixel_down_right = $this->get_gray_color(imagecolorat($im,$x+1,$y+1));
		        
		        ##APPLYING CONVOLUTION MASK
		        $conv_x = ($pixel_up_right+($pixel_right*2)+$pixel_down_right)-($pixel_up_left+($pixel_left*2)+$pixel_down_left);
		        $conv_y = ($pixel_up_left+($pixel_up*2)+$pixel_up_right)-($pixel_down_left+($pixel_down*2)+$pixel_down_right);
		        
		        // calculating the distance
		        $gray = sqrt($conv_x*$conv_x+$conv_y*$conv_y);
		        // if(abs($conv_x) > $temp_x){
		        // 	$temp_x = abs($conv_x);
		        // }
		        // if(abs($conv_y) > $temp_y){
		        // 	$temp_y = abs($conv_y);
		        // }
		        // $arc_tan = atan($conv_y/$conv_x);

		        ##APPLYING MANHATTAN DISTANCE
		        // $gray = abs($conv_x)+abs($conv_y);
		        

		        // if($gray > 0){
		        // 	$gray = 255;
		        // }else{
		        // 	$gray = 0;
		        // }
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
		        // $new_gray  = imagecolorallocate($edge_detect,$gray,$gray,$gray);
		        
		        ##ADDING / SET THE GRAY PIXEL TO THE NEW IMAGE
		        imagesetpixel($edge_detect,$x,$y,imagecolorallocate($edge_detect,$gray,$gray,$gray));
		    }
		}



		// ##GET IMAGE SIZE
		// $dimensions = getimagesize($image_url);
		// $width = $dimensions[0]; // width
		// $height = $dimensions[1]; // height

		// ##CREATE THE IMAGE
		// $im = imagecreatefromjpeg('query_images/'.$file_name);

		// ##INITIALIZE ARRAY
		// $grayscale_image = Array();

		

		##CREATION OF THE FINAL IMAGE
		imagejpeg($edge_detect,'query_images/'.$clean_file_name."_EDGECANNY.".$file_extension);

		##FREEING MEMORY
		imagedestroy($im);
		imagedestroy($edge_detect);
	}

	public function glcm_image($image_url,$file_name,$file_extension,$clean_file_name,$destination_base_path){
		##GET IMAGE SIZE
		$dimensions = getimagesize($image_url);
		$width 		= $dimensions[0]; // width
		$height 	= $dimensions[1]; // height

		##CREATE THE IMAGE
		$im = imagecreatefromjpeg($destination_base_path.$file_name);

		##INITIALIZE ARRAY
		$original_image 		= Array();
		$normalize_image 		= Array();

		$glcm_0 				= Array();
		$glcm_0_tr 				= Array(); // transpose matrix array
		$glcm_0_count 			= Array(); // Count Matrix glcm_0 and glcm_0_tr
		$glcm_0_sum 			= 0;
		$glcm_0_norm 			= Array(); // Result of probability value ($glcm_0_count / $glcm_0_sum)

		$glcm_45 				= Array();
		$glcm_45_tr 			= Array();
		$glcm_45_count 			= Array(); // Count Matrix glcm_45 and glcm_45_tr
		$glcm_45_sum 			= 0;
		$glcm_45_norm 			= Array(); // Result of probability value ($glcm_45_count / $glcm_45_sum)

		$glcm_90 				= Array();
		$glcm_90_tr 			= Array();
		$glcm_90_count 			= Array(); // Count Matrix glcm_90 and glcm_90_tr
		$glcm_90_sum 			= 0;
		$glcm_90_norm 			= Array(); // Result of probability value ($glcm_90_count / $glcm_90_sum)

		$glcm_135 				= Array();
		$glcm_135_tr 			= Array();
		$glcm_135_count 		= Array(); // Count Matrix glcm_135 and glcm_135_tr
		$glcm_135_sum 			= 0;
		$glcm_135_norm 			= Array(); // Result of probability value ($glcm_135_count / $glcm_135_sum)

		$glcm_norm_final		= Array(); // Merge 0,45,90,135 degree normalization matrix (for extracting feature)

		// sum of $glcm_0_norm / $glcm_45_norm / $glcm_90_norm / $glcm_135_norm
		$glcm_0_norm_result  	= 0;
		$glcm_45_norm_result 	= 0;
		$glcm_90_norm_result 	= 0;
		$glcm_135_norm_result	= 0;

		$ene 	 = 0; // Energy atau Angular Second Moment (ASM) untuk mengukur homogenitas sebuah citra
		$cor 	 = 0; // Correlation untuk menghitung keterkaitan piksel
		$idm 	 = 0; // Homogenity atau Inverse Different Moment (IDM) untuk mengukur homogenitas citra dengan level keabuan sejenis
		$con 	 = 0; // Contrast untuk mengukur variasi pasangan tingkat keabuan dalam sebuah citra

		$mean     = 0;
		$variance = 0; // The default is variance pow 2

		$result   = Array();

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

				##DIVIDE INTO 8 GRAY LEVEL (TOOK SO LONG TIME TO FINISH THE PROCESS)

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

				/*if($gray < 32){
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
				}*/

				##DIVIDE INTO 4 GRAY LEVEL

				/*

				0   - 63  = 0
				64  - 127 = 1
				128 - 191 = 2
				192 - 255 = 3
				
				*/

				if($gray < 64){
					$normalize_image[$h][$w] = 0;
				}else if($gray < 128){
					$normalize_image[$h][$w] = 1;
				}else if($gray < 192){
					$normalize_image[$h][$w] = 2;
				}else if($gray < 256){
					$normalize_image[$h][$w] = 3;
				}

				##END NORMALIZE GRAY COLOR

				##SET PIXEL FOR THE NEW GRAYSCALE IMAGE
				imagesetpixel($im, $w, $h, imagecolorallocate($im, $gray, $gray, $gray));
			}
		}

		##START GLCM FOR 0,45,90,135 DEGREE
		// CONVERT INTO GLCM MATRICES
		for($i = 0 ; $i < 4 ; $i++){
			for($j = 0 ; $j < 4 ; $j++){
				$glcm_0_pixel_count = 0;
				$glcm_45_pixel_count = 0;
				$glcm_90_pixel_count = 0;
				$glcm_135_pixel_count = 0;
				##COUNTING THE MATRICES FOR 0 DEGREE GLCM
				for($h = 0 ; $h < $height ; $h++){
					for($w = 0 ; $w < $width ; $w++){
						if($w < ($width-1)){
							if($normalize_image[$h][$w] == $i && $normalize_image[$h][$w+1] == $j){
								$glcm_0_pixel_count += 1;
							}
						}

						if($h < $height-1 && $w > 0){
							if($normalize_image[$h][$w] == $i && $normalize_image[$h+1][$w-1] == $j){
								$glcm_45_pixel_count += 1;
							}
						}

						if($h < ($height-1)){
							if($normalize_image[$h][$w] == $i && $normalize_image[$h+1][$w] == $j){
								$glcm_90_pixel_count += 1;
							}
						}

						if($h < ($height-1) && $w < ($width-1)){
							if($normalize_image[$h][$w] == $i && $normalize_image[$h+1][$w+1] == $j){
								$glcm_135_pixel_count += 1;
							}
						}
					}
				}
				$glcm_0[$i][$j] = $glcm_0_pixel_count;
				$glcm_45[$i][$j] = $glcm_45_pixel_count;
				$glcm_90[$i][$j] = $glcm_90_pixel_count;
				$glcm_135[$i][$j] = $glcm_135_pixel_count;
			}
		}

		// SET MATRIX TRANSPOSE FOR GLCM 0 DEGREE
		for($i = 0 ; $i < 4 ; $i++){
			for($j = 0 ; $j < 4 ; $j++){
				$glcm_0_tr[$j][$i] = $glcm_0[$i][$j];
				$glcm_45_tr[$j][$i] = $glcm_45[$i][$j];
				$glcm_90_tr[$j][$i] = $glcm_90[$i][$j];
				$glcm_135_tr[$j][$i] = $glcm_135[$i][$j];
			}
		}

		// COUNT MATRIX GLCM_0 and GLCM_0_tr 
		for($i = 0 ; $i < 4 ; $i++){
			for($j = 0 ; $j < 4 ; $j++){
				$glcm_0_count[$i][$j] = $glcm_0[$i][$j] + $glcm_0_tr[$i][$j];
				$glcm_0_sum += $glcm_0_count[$i][$j];

				$glcm_45_count[$i][$j] = $glcm_45[$i][$j] + $glcm_45_tr[$i][$j];
				$glcm_45_sum += $glcm_45_count[$i][$j];

				$glcm_90_count[$i][$j] = $glcm_90[$i][$j] + $glcm_90_tr[$i][$j];
				$glcm_90_sum += $glcm_90_count[$i][$j];

				$glcm_135_count[$i][$j] = $glcm_135[$i][$j] + $glcm_135_tr[$i][$j];
				$glcm_135_sum += $glcm_135_count[$i][$j];
			}
		}

		// SIMILARITY (Normalized GLCM of the image)
		for($i = 0 ; $i < 4 ; $i++){
			for($j = 0 ; $j < 4 ; $j++){
				$glcm_0_count[$i][$j] /= $glcm_0_sum;
				$glcm_0_norm[$i][$j] = round($glcm_0_count[$i][$j], 4);
				$glcm_0_norm_result += $glcm_0_count[$i][$j]; // NORMALIZE

				$glcm_45_count[$i][$j] /= $glcm_45_sum;
				$glcm_45_norm[$i][$j] = round($glcm_45_count[$i][$j], 4);
				$glcm_45_norm_result += $glcm_45_count[$i][$j]; // NORMALIZE

				$glcm_90_count[$i][$j] /= $glcm_90_sum;
				$glcm_90_norm[$i][$j] = round($glcm_90_count[$i][$j], 4);
				$glcm_90_norm_result += $glcm_90_count[$i][$j]; // NORMALIZE

				$glcm_135_count[$i][$j] /= $glcm_135_sum;
				$glcm_135_norm[$i][$j] = round($glcm_135_count[$i][$j], 4);
				$glcm_135_norm_result += $glcm_135_count[$i][$j]; // NORMALIZE

				$glcm_norm_final[$i][$j] = round((($glcm_0_norm[$i][$j] + $glcm_45_norm[$i][$j] + $glcm_90_norm[$i][$j] + $glcm_135_norm[$i][$j])/4),4);
			}
		}
		##END GLCM FOR 0,45,90,135 DEGREE
				
		for($i = 0 ; $i < 4 ; $i++){
			for($j = 0 ; $j < 4 ; $j++){
				$mean += $i * $glcm_norm_final[$i][$j];
			}
		}

		for($i = 0 ; $i < 4 ; $i++){
			for($j = 0 ; $j < 4 ; $j++){
				$variance += $glcm_norm_final[$i][$j] * pow(($i - $mean),2);
			}
		}

		// ROUND Variance variable
		$variance = round($variance,4);

		##START EXTRACT TEXTURES FEATURES (ENERGY, CORRLEATION, INVERSE DIFFERENCE MOMENT, CONTRAST)
		for($i = 0 ; $i < 4 ; $i++){
			for($j = 0 ; $j < 4 ; $j++){

				$ene += pow($glcm_norm_final[$i][$j],2);
				$cor += $glcm_norm_final[$i][$j] * ((($i-$mean)*($j-$mean))/$variance);
				$idm += ($glcm_norm_final[$i][$j] / (1 + pow(($i-$j),2)));
				$con += $glcm_norm_final[$i][$j] * pow(($i-$j),2);
			}
		}

		// ROUND THE TEXTURES FEATURES
		$ene = round($ene,4);
		$cor = round($cor,4);
		$idm = round($idm,4);
		$con = round($con,4);

		$result['energy'] 		= $ene;
		$result['correlation'] 	= $cor;
		$result['idm'] 			= $idm;
		$result['contrast'] 	= $con;

		##END EXTRACT TEXTURES FEATURES (ENERGY, CORRLEATION, INVERSE DIFFERENCE MOMENT, CONTRAST)

		##CREATION OF THE FINAL IMAGE
		// imagejpeg($im,'query_images/'.$clean_file_name.'_GRAYSCALE.'.$file_extension);

		##FREEING MEMORY
		imagedestroy($im);

		return $result;
	}

	public function local_color_histogram($image_url,$file_name,$file_extension,$clean_file_name,$destination_base_path,$image_size_grid,$image_color_quantization){
		$dimensions = getimagesize($image_url);
		$width 		= $dimensions[0]; // width
		$height 	= $dimensions[1]; // height

		$im = imagecreatefromjpeg($destination_base_path.$file_name);

		$image_size = $image_size_grid; // 3x3
		$width = floor($width/$image_size);
		$height = floor($height/$image_size);

		##INITIALIZE ARRAY

		$histogram 				  = Array(); //$histogram[0-8 jd total ada 9 (3x3) gambar dibagi menjadi 3 row dan 3 column][0-3 jumlah histogram]
		$histogram_ctr 			  = 0;

		##QUANTIZATION of the RGB COLOR into X COLOR
	    $quantization_size = $image_color_quantization;

		$quantization = floor(255/$quantization_size); // 4 (0-3) color

		for ($i=0; $i < $image_size; $i++) {
			for ($j=0; $j < $image_size; $j++) { 
				
				##INITIALIZE HISTOGRAM ARRAY
				for ($x=0; $x <= $quantization ; $x++) { 
					for ($y=0; $y <= $quantization ; $y++) { 
						for ($z=0; $z <= $quantization ; $z++) { 
							$histogram[$histogram_ctr][$x][$y][$z] = 0;
						}
					}				
				}

				for($h = ($height*$i) ; $h < ($height*($i+1)) ; $h++){
					for($w = ($width*$j) ; $w < ($width*($j+1)) ; $w++){
						$rgb = imagecolorat($im,$w,$h);

						$red   = ($rgb >> 16) & 0xFF;
					    $green = ($rgb >> 8) & 0xFF;
					    $blue  = $rgb & 0xFF;

						$red_quantization   = floor($red/$quantization_size);
						$green_quantization = floor($green/$quantization_size);
						$blue_quantization  = floor($blue/$quantization_size);

						//menambahkan data pada histogram ke $i
						$histogram[$histogram_ctr][$red_quantization][$green_quantization][$blue_quantization] += 1;
					}
				}

				$histogram_ctr += 1;
			}
		}

		##FREEING MEMORY
		imagedestroy($im);

		return $histogram;
		// return $this->print_block($histogram);
	}

	public function local_color_histogram_distance($image_url,$file_name,$file_extension,$clean_file_name,$destination_base_path,$histogram_query,$image_size_grid,$image_color_quantization){
		$histogram_data = $this->local_color_histogram($image_url,$file_name,$file_extension,$clean_file_name,$destination_base_path,$image_size_grid,$image_color_quantization);

		$dimensions = getimagesize($image_url);
		$width 		= $dimensions[0]; // width
		$height 	= $dimensions[1]; // height

		$image_size = $image_size_grid; // 3 x 3 OR 5 x 5 OR 7 x 7
		$width = floor($width/$image_size);
		$height = floor($height/$image_size);

		##INITIALIZE ARRAY

		$histogram_ctr 			  = 0;
		$histogram_distance_final = 0;

		##QUANTIZATION of the RGB COLOR into X COLOR
	    $quantization_size = $image_color_quantization;

		$quantization = floor(255/$quantization_size); // 4 (0-3) color

		for ($i=0; $i < $image_size; $i++) {
			for ($j=0; $j < $image_size; $j++) { 
				$histogram_distance[$histogram_ctr] = 0;

				##INITIALIZE HISTOGRAM ARRAY
				for ($x=0; $x <= $quantization ; $x++) { 
					for ($y=0; $y <= $quantization ; $y++) { 
						for ($z=0; $z <= $quantization ; $z++) { 
							$histogram_distance[$histogram_ctr] += pow(($histogram_query[$histogram_ctr][$x][$y][$z]-$histogram_data[$histogram_ctr][$x][$y][$z]),2);
						}
					}				
				}

				$histogram_distance[$histogram_ctr] = sqrt($histogram_distance[$histogram_ctr]);
				$histogram_distance_final += $histogram_distance[$histogram_ctr];

				$histogram_ctr += 1;
			}
		}

		return $histogram_distance_final;
		
	}

	public function print_block($data, $title="PRINT BLOCK") {
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

    public function glcm0_45_90_135(){
    	##START GLCM FOR 0 DEGREE
		// CONVERT INTO GLCM MATRICES
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

		// SET MATRIX TRANSPOSE FOR GLCM 0 DEGREE
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_0_tr[$j][$i] = $glcm_0[$i][$j];
			}
		}

		// COUNT MATRIX GLCM_0 and GLCM_0_tr 
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_0_count[$i][$j] = $glcm_0[$i][$j] + $glcm_0_tr[$i][$j];
				$glcm_0_sum += $glcm_0_count[$i][$j];
			}
		}

		// SIMILARITY (Normalized GLCM of the image)
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_0_count[$i][$j] /= $glcm_0_sum;
				$glcm_0_count[$i][$j] = round($glcm_0_count[$i][$j], 2);
				$glcm_0_norm_result += $glcm_0_count[$i][$j]; // NORMALIZE
			}
		}
		##END GLCM FOR 0 DEGREE


		##START GLCM FOR 45 DEGREE ( Diagonal )
		// CONVERT INTO GLCM MATRICES
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$count = 0;
				##COUNTING THE MATRICES FOR 0 DEGREE GLCM
				for($h = 0 ; $h < $height ; $h++){
					for($w = 0 ; $w < $width ; $w++){
						if($h < $height-1 && $w > 0){
							if($normalize_image[$h][$w] == $i && $normalize_image[$h+1][$w-1] == $j){
								$count += 1;
							}
						}
					}
				}
				$glcm_45[$i][$j] = $count;
			}
		}

		// SET MATRIX TRANSPOSE FOR GLCM 0 DEGREE
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_45_tr[$j][$i] = $glcm_45[$i][$j];
			}
		}

		// COUNT MATRIX GLCM_0 and GLCM_0_tr 
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_45_count[$i][$j] = $glcm_45[$i][$j] + $glcm_45_tr[$i][$j];
				$glcm_45_sum += $glcm_45_count[$i][$j];
			}
		}

		// SIMILARITY (Normalized GLCM of the image)
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_45_count[$i][$j] /= $glcm_45_sum;
				$glcm_45_count[$i][$j] = round($glcm_45_count[$i][$j], 2);
				$glcm_45_norm_result += $glcm_45_count[$i][$j]; // NORMALIZE
			}
		}
		##END GLCM FOR 45 DEGREE ( Diagonal )


		##START GLCM FOR 90 DEGREE
		// CONVERT INTO GLCM MATRICES
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$count = 0;
				##COUNTING THE MATRICES FOR 0 DEGREE GLCM
				for($h = 0 ; $h < $height ; $h++){
					for($w = 0 ; $w < $width ; $w++){
						if($h < ($height-1)){
							if($normalize_image[$h][$w] == $i && $normalize_image[$h+1][$w] == $j){
								$count += 1;
							}
						}
					}
				}
				$glcm_90[$i][$j] = $count;
			}
		}

		// SET MATRIX TRANSPOSE FOR GLCM 0 DEGREE
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_90_tr[$j][$i] = $glcm_0[$i][$j];
			}
		}

		// COUNT MATRIX GLCM_0 and GLCM_0_tr 
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_90_count[$i][$j] = $glcm_90[$i][$j] + $glcm_90_tr[$i][$j];
				$glcm_90_sum += $glcm_90_count[$i][$j];
			}
		}

		// SIMILARITY (Normalized GLCM of the image)
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_90_count[$i][$j] /= $glcm_90_sum;
				$glcm_90_count[$i][$j] = round($glcm_90_count[$i][$j], 2);
				$glcm_90_norm_result += $glcm_90_count[$i][$j]; // NORMALIZE
			}
		}
		##END GLCM FOR 90 DEGREE


		##START GLCM FOR 135 DEGREE ( Diagonal )
		// CONVERT INTO GLCM MATRICES
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$count = 0;
				##COUNTING THE MATRICES FOR 0 DEGREE GLCM
				for($h = 0 ; $h < $height ; $h++){
					for($w = 0 ; $w < $width ; $w++){
						if($h < ($height-1) && $w < ($width-1)){
							if($normalize_image[$h][$w] == $i && $normalize_image[$h+1][$w+1] == $j){
								$count += 1;
							}
						}
					}
				}
				$glcm_135[$i][$j] = $count;
			}
		}

		// SET MATRIX TRANSPOSE FOR GLCM 0 DEGREE
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_135_tr[$j][$i] = $glcm_135[$i][$j];
			}
		}

		// COUNT MATRIX GLCM_0 and GLCM_0_tr 
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_135_count[$i][$j] = $glcm_135[$i][$j] + $glcm_135_tr[$i][$j];
				$glcm_135_sum += $glcm_135_count[$i][$j];
			}
		}

		// SIMILARITY (Normalized GLCM of the image)
		for($i = 0 ; $i < 8 ; $i++){
			for($j = 0 ; $j < 8 ; $j++){
				$glcm_135_count[$i][$j] /= $glcm_135_sum;
				$glcm_135_count[$i][$j] = round($glcm_135_count[$i][$j], 2);
				$glcm_135_norm_result += $glcm_135_count[$i][$j]; // NORMALIZE
			}
		}
		##END GLCM FOR 135 DEGREE ( Diagonal )
    }
}
