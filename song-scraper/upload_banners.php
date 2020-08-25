<?php

include ('config.php');

$banners_copied = 0;

function findFiles($directory) {
    $dir_paths = array ();
	foreach(glob("{$directory}/*", GLOB_ONLYDIR) as $filename) {
            $dir_paths[] = $filename;
    }
    return $dir_paths;
}

function curl_upload($file,$pack_name){
	global $target_url;
	global $security_key;
	//special curl function to create the information needed to upload files
	//renaming the banner images to be consistent with the pack name
	$cFile = curl_file_create($file,'',$pack_name.'.'.strtolower(pathinfo($file,PATHINFO_EXTENSION)));
	//add the security_key to the array
	$post = array('security_key' => $security_key,'file_contents'=> $cFile);
	
	//this curl method only works with PHP 5.5+
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$target_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //this being false is probaby bad?
	curl_setopt($ch, CURLOPT_POST,1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$result = curl_exec ($ch);
	$error = curl_strerror(curl_errno($ch));
	curl_close ($ch);
	echo $result; //echo from the server-side script

	return $error;
}

// find all the pack/group folders
$pack_dir = findFiles($songsDir);

$img_arr = array();

foreach ($pack_dir as $path){
	
	$pack_name = "";
	$img_path = "";
	//get pack name from folder
	$pack_name = substr($path,strrpos($path,"/")+1);
	//clean up pack name and replace spaces with underscore
	$pack_name = strtolower(preg_replace('/\s+/', '_', trim($pack_name)));
	//look for any picture file in the pack directory
	$img_path = glob("{$path}/*{jpg,JPG,jpeg,JPEG,png,PNG,gif,GIF}",GLOB_BRACE);
	
	if (isset($img_path) && !empty($img_path)){
		//use the first result as the pack banner and add to array
		$img_arr[] = array('img_path' => $img_path[0],'pack_name' => $pack_name);
	}else{
		echo "No banner image for ".$pack_name."\n";
	}
}

//print_r($img_arr);

foreach ($img_arr as $img){
	//upload banner images
	$cError = curl_upload($img['img_path'],$img['pack_name']);
	//output any errors from the curl upload
	if ($cError != "No error"){
		echo "CURL Error: ".$cError."\n";
	}else{
		$banners_copied++;
	}
}

//STATS!
echo "Uploaded ".$banners_copied." of ".count($pack_dir)." banner images.";

?>