<?php 
require_once 'scraper.class.php';

/*
 * Crawl a website / network caching and following links
 */
class Crawler
{
	private $seed_url,
			$base_url,
			$scraper,
			$max_level,
			$offsite,
			$images_folder,
			$exclude 		   = array(),
			$visited_links	   = array(),
			$links_cache 	   = array(),
			$follow_links      = array(),
			$unprocessed_links = array(),
			$media_array 	   = array('.jpg', '.jpeg', '.gif', '.png', '.swf', '.mpeg', '.pdf'),
			$ext_array		   = array('.com', 'org', '.biz', '.ru', '.xxx', '.us', '.edu');
			
	public function __construct($seed_url, $max_level = 1, $offsite = false, $images_folder = '', $exclude = array())
	{
		$this->seed_url  	   = $seed_url;
		$this->max_level 	   = $max_level;
		$this->offsite 	 	   = $offsite;
		$this->exclude 	 	   = $exclude;
		
		if(!empty($images_folder))
		{
			include 'imagescraper.class.php';
			$this->images_folder = $images_folder;
		}
	}
	
	public function get_links_cache()
	{
		return $this->links_cache;
	}
	
	public function get_follow_links()
	{
		return $this->follow_links;
	}
						
	/*
	 * Extract all links on page, excluding JS files
	 * We should add functionality to extract images and links seperately
	 * Images would not be crawled but could be downloaded
	 * Both images and links should be indexed into array containing metadata
	 */
	
	function harvest_links($url = '')
	{
		$url 			 = ($url == '') ? $this->seed_url : $url;
		$this->scraper   = new Scraper($url);
		
		$this->base_url  = $this->scraper->parser->getURLBase($this->seed_url);

		$links_matches = $this->scraper->parser->parseFile('/(?:src|href)="(.*?)"/', $this->scraper->curl_file);
		$links_array = $links_matches[1];

		foreach($links_array as $key => $link)
		{
			# Clear javascript files from collection
			if(preg_match('/.+\.js/', $link))
			{
				unset($links_array[$key]);
				continue;
			}
			
			$links_array[$key] = $this->scraper->parser->resolveAddress($link, $url);
		}
	
		# Remove any duplicate links before we set them to class
		$this->unprocessed_links = array_unique($links_array);	
		$this->process_links();
	}
	
	# Save links, making sure we are caching only the types of links we want, and there are no duplicates
	# Also create array of fresh links to crawl
	function process_links()
	{	
		$tmp_links = array();
		# Do we want to follow this current link
		$do_follow = true;
				
		foreach($this->unprocessed_links as $key => $link)
		{
			# If link is already in cache skip ahead
			if(in_array($link, $this->links_cache))
				continue;
				
			# Simple Boolean check. Run this check first
			if($this->offsite == false)
			{
				if(strpos($link, $this->base_url) === false)
				{
					foreach($this->ext_array as $key => $ext)
					{
						if(strpos($link, $ext) !== false)
							$do_follow = false;
					}	
				}
			}	
							
			if($do_follow == true && !empty($this->exclude))
			{
				if(in_array($link, $this->exclude))
					$do_follow = false;
			}

			# This is a new link, let's cache it.
			$this->links_cache[] = $link;
			
			# Before we crawl link, make sure it's not a media file
			$media_check = strtolower(substr($link, strrpos($link, '.')));
			
			if(in_array($media_check, $this->media_array))
			{
				$do_link = false;
				
				# Are we downloading images?
				if(!empty($this->images_folder))
				{
					if(in_array($media_check, array('.jpg', '.jpeg', '.gif', '.png')))
					{
						ImgScraper::downloadImage($link, $this->images_folder);
					}
				}
			}
			
						
			# If $follow_link still equals true, queue it for crawling
			if($do_follow == true)
				$tmp_links[] = $link;
		}
		# Moved this outside of loop because unset($this->follow_links) was 
		# interupting access to $follow_links in another script
		$this->follow_links = $tmp_links;
	}
}