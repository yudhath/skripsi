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
					$image_url = URL('/uploads') . '/' . $file_name;
					$file->move('uploads', $file_name);
				}
				
			}
		}
		else{
			echo 'There is no image';
		}
    }
}
