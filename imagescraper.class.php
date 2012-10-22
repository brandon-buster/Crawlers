<?php 
class ImgScraper
{
	/*
	 * $link: (string) URI of image we need to download
	 * $folder: (string) URL of folder we are dowloading the image to
	 * $base: (string) if $link is not already fully resolved, $base will be the parent directory path of $link
	 */
	public function downloadImage($link, $folder, $base = '')
	{
		# Do we need to resolve img url?
		if(!empty($base))
		{
			require_once 'parser.class.php';
			$link	  = Parser::resolveAddress($link, $base);
		}
		
		$img_name = rtrim($link, '/');
		$img_name = substr($img_name, strrpos($img_name, '/'));
		
		$img_resource = self::curlImg($link);
		
		
		self::saveImage($img_resource, $img_name, $folder);
	}
	
	public function saveImage($img, $img_name, $folder)
	{		
		$folder = __DIR__ .'/imgs/'. $folder;
		if(!is_dir($folder))
			mkdir($folder, 777, true);

		
		//if(!file_exists("$folder/$img_name"))
	//	{
			$f_handle = fopen($folder . '/' . $img_name, 'w');
			fputs($f_handle, $img);
			
			fclose($f_handle);
		//}
	}
	
	public function curlImg($link)
	{
		$c = curl_init();
		curl_setopt_array($c, array(
			CURLOPT_URL			   => $link,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_BINARYTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS	   => 4
			)
		);
		
		$img = curl_exec($c);
		curl_close($c);
		return $img;
	}
	
}