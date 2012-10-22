<?php 
require 'parser.class.php';
class Scraper
{
	public $target
		   ,$ref
		   ,$curl_array
		   ,$protocal
		   ,$base
		   ,$parser
		   ,$curl_file
		   ,$curl_status
		   ,$curl_error;

    # $data_array holds any data we need to pass to target URI along with our request
	function __construct($target, $ref='', $method='get', array $data_array = array(), $binary = false)
	{		
		#configure "primary settings" for $target which will run on EVERY curl request
		$this->parser = new Parser;
		$this->setProtoAndDomain($target);
		$this->target	= $target; 
		$this->ref 		= $ref;
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
				CURLOPT_NOBODY 			=> false,
				CURLOPT_COOKIEJAR 		=> dirname(__FILE__).'/cookies.txt',
				CURLOPT_COOKIEFILE 		=> dirname(__FILE__).'/cookies.txt',
				CURLOPT_TIMEOUT			=> 25,
				CURLOPT_USERAGENT		=> 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.56 Safari/536.5',
				CURLOPT_VERBOSE			=> false,
				CURLOPT_SSL_VERIFYPEER	=> false,
				CURLOPT_FOLLOWLOCATION	=> true,
				CURLOPT_MAXREDIRS		=> 4,
				CURLOPT_RETURNTRANSFER	=> true
			)
		);
		
		##	Primary configs set. Let's see if we have any special requests...	##
		
		#Do we need to tell target server we're coming from a specific page? CURLOPT_REFERER will lie for us.
		if(!empty($ref))
			curl_setopt($curl, CURLOPT_REFERER, $ref);
			
		#are we passing any data to our target page?
		if(!empty($data_array))
		{
			$qsa = '';	
			foreach($data_array as $key => $value)
				$qsa .= "&$key=$value";
				
			$qsa = ltrim($qsa, '&');
		}
		if(isset($method))
		{			
			if($method == 'get')
			{
				if(isset($qsa))
					$target .= '?' . $qsa;
				curl_setopt($curl, CURLOPT_HTTPGET, TRUE);
				curl_setopt($curl, CURLOPT_POST, FALSE);
			}
			elseif($method == 'post')
			{
				if(isset($qsa)) 
					curl_setopt($curl, CURLOPT_POSTFIELDS, $qsa);
				
				curl_setopt($curl, CURLOPT_HTTPGET, FALSE);
				curl_setopt($curl, CURLOPT_POST, TRUE);
				
			}
		}
		
		# Are we dowloading an image?
		if($binary == true)
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);

		# We'll know by now if there was a GET request along with any Query String Arguments. It is safe to set CURLOPT_URL now.
		curl_setopt($curl, CURLOPT_URL, $target);
							
		$this->curl_file   = curl_exec($curl);
		$this->curl_status = curl_getinfo($curl);
		$this->curl_error  = curl_error($curl);

		curl_close($curl);
	}
	
	function setProtoAndDomain($target)
	{

		$this->protocal = substr($target, 0, strpos($target, '//') + 2);
		$base 			= substr($target, strlen($this->protocal));

		if (strpos($base, '/'))
			$this->base		= $this->protocal . substr($base, 0, strrpos($base, '/') + 1);
		else
			$this->base		= $this->protocal . $base;
	}
}