<?php 
/*
 * MAY NEED ANOTHER SCRIPT FOR TAGGING SCRAPED INFO.
 * SCRIPT WOULD EXTRACT URL, PAGE TITLE, IMG ALT & TITLE TAGS,
 * <H*> TAGS, SIBLING CONTENT IN PARENT DIV
 * ALL WOULD BE INSERTED INTO CONTENT, TAGS, TAGS/CONTENT TABLES
 * MYSQL STORED PROCEDURE COULD THEN OUTPUT DATA BASED ON TAG RELEVANCE
 */
class Parser
{			
	public function __construct() {}
	
	public function parseFile($regex, $file, $arr = false)
	{
		if($arr == false)
		{
			preg_match($regex, $file, $match);
			return $match[1];
		}
		
		preg_match_all($regex, $file, $matches);
		return $matches[1];
	}
	
	
	/*
	 * WE NEED TO BE REPLACING MUCH OF THIS CODE WITH pathinfo()
	 * pathinfo($link [, PATHINFO_DIRNAME, | PATHINFO_BASENAME | PATHINFO_EXTENSION | PATHINFO_FILENAME])
	 * dirname() & basename() may also help in reducing amount of current code
	 * $_SERVER['REQUEST_URI'] WILL ALSO BE HELFUL // SERVER['REQUEST_URI'] WILL NOT BE SUITABLE BECAUSE 
	 * $_SERVER IS ONLY PROVIDED BY PHP BUILT SYSTEMS NOT WOULD NOT IT BE AVAILABLE IF WE CONVERTED THIS
	 * PROCESS TO A SHELL SCRIPT TO BE EXECUTED BY A CRON
	 * 
	 * FORGET ABOUT pathinfo()
	 * 
	 * WE NEED TO BE USING parse_url($url [,PHP_URL_*(SCHEME, HOST, PATH, QUERY)])
	 * IF ENTIRE URL IS EXPLODED INTO ARRAY WITHOUT SPECIFIED COMPONENTS, COMPONENTS WILL BE ACCESSED VIA scheme, host, path, query
	 */
	
	public function resolveAddress($link, $base)
	{		
		/*
		 * $link_compenents = $parse_url($link)
		 */
		
		/*
		 * if(isset($link_compenents['scheme'])
		 * 	return $link;
		 */
		# Is link already fully resolved
		$firstThreeChars = substr($link, 0, 3);
		
		if ($firstThreeChars == 'www' || $firstThreeChars == 'htt')
			return $link;
			
		# Is link pointing to root directory
		if(strpos($link, '/') === 0)
			return $this->getURLBase($base) . substr($link, strpos($link, '/') + 1);
		
		# Is link pointing at a parent directory
		if($firstThreeChars == '../')
		{
			#determine how many directories to move up
			$parent_count = substr_count($link, '../');
			$link = str_replace('../', '', $link);
			$base = explode('/', $base);
			
			if(count($base) < $parent_count)
				return false;
			
			#traverse up the directory
			for($i = 0; $i < $parent_count; $i++)
				array_pop($base);
			
			$base = implode('/', $base);
			return $base . '/' . $link;
		}
		
		/* If we have made it this far, link is either already fully resolved
		 * with neither protocal or 'www', or link is pointing to
		 * immediate parent directory
		 */
					
		# Make sure our base doesn't contain any QSA's
		$qsa_pos = strpos($base, '?');
		if($qsa_pos !== false)
		{
			$base = substr($base, 0, $qsa_pos);
			if(strrpos($base, '/' + 1) == strlen($base))
				$base = substr($base, 0, strlen($base) - 1);	
		}
		
		/*
		 * Make sure base isn't pointing at a sibling file
		 * If there is a file extension after the last directory sperator,
		 * and the directory seperator isn't part of protocal, then we have a sibling file needing removal
		 */
		$dir_sep_pos = strrpos($base, '/');
		if($dir_sep_pos !== false && $dir_sep_pos > 6 && $dir_sep_pos < strrpos($base, '.'))
			$base = substr($base, 0, $dir_sep_pos);

		# Link is pointing at immediate parent directory and $base contains no QSA's or sibling file
		if($dir_sep_pos == strlen($base) + 1)
			return $base . $link;

		# I think we've accounted for everything. Just add the directory seperator
		return $base . '/' . $link;	
	}
	
	# Extract the root directory of a website's URL
	public function getURLBase($url)
	{
		if(strpos($url, 'http') !== false)
		{
			$urlBits = explode('//', $url);
			$proto   = $urlBits[0] . '//';
			$base    = (strpos($urlBits[1], '/')) ? substr($urlBits[1], 0, strrpos($urlBits[1], '/')) : $urlBits[1];
		}
		else 
		{
			$proto = '';
			$base  = (strpos($url, '/')) ? substr($url, 0, strpos($url, '/')) : $url;
		}
		
		return $proto . $base . '/';
	}
	
}