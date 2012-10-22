<?php 
require_once 'scraper.class.php';
require_once 'Webbots/LIB_parse.php';

$feed = array();

class rssParser 
{
	public function download_parse_rss($target)
	{
		$news = new Scraper($target);
		$return_array = array();
		
		preg_match('/<title>(.*?)<\/title>/', $news->curl_file, $title);
		$return_array['title'] = isset($title[1]) ? $title[1] : '';
		
		preg_match('/<copyright>(.*?)<\/title>/', $news->curl_file, $copyright);
		$return_array['copyright'] = isset($copyright[1]) ? $copyright[1] : '';

		preg_match_all('/<item>(.*?)<\/item>/si', $news->curl_file, $items);
		$return_array['items'] = $items[1];

		return $return_array;
	}
	
	public function buildFeed($array)
	{		
		//specify second layer of array (SOURCE)
		$src = $array['src'];
		
		foreach($array['items'] as $item)
		{		
			#extract and sanitize all the elements we need from article	
			
						
			//publication date
			preg_match('!<pubDate>(.*?)</pubDate>!si', $item, $pubDate);
			$pubDateTime = $this->strip_cdata_tags($pubDate[1]);
			$pubPostDate = date('m-d', strtotime($pubDateTime));
			
			//article title
			preg_match('!<title>(.*?)</title>!si', $item, $title);
			$title = $this->strip_cdata_tags($title[1]);
			
			//article link
			preg_match('!<link>(.*?)</link>!si', $item, $link);
			$link = $this->strip_cdata_tags($link[1]);
			
			//content
			preg_match('!<description>(.*?)</description>!si', $item, $description);
			$description = $this->strip_cdata_tags($description[1]);
			
			//begin building the layers of our array
			
			#post date
			$this->feed[$pubPostDate][] = array(
									'pubTime' 	  => strtotime($pubDateTime), 
									'src' 		  => $src, 
									'title'		  => $title,
									'link' 		  => $link, 
									'description' => $description
									);
		}
		
	}
	
	
	/*
	 * array will have to key layers, pubPostDate and pubDateTime
	 * Once inside pubPostDate layer, pubDateTime must be sorted
	 */
	
	public function display_rss_array()
	{
		
		#CONTROL VARIABLES FOR TESTING
		$this->feed['10-17'][] = array(
									'pubTime' 	  => 1342534067, 
									'src' 		  => 'hoteljobs.com', 
									'title'		  => 'mytitle 2',
									'link' 		  => 'mylink', 
									'description' => 'n/a'
									);
		$this->feed['10-17'][] = array(
									'pubTime' 	  => 1442522773, 
									'src' 		  => 'hoteljobs.com', 
									'title'		  => 'mytitle 3',
									'link' 		  => 'mylink', 
									'description' => 'n/a'
									);	
		$this->feed['10-17'][] = array(
									'pubTime' 	  => 1142522773,
									'src' 		  => 'hoteljobs.com', 
									'title'		  => 'mytitle 1',
									'link' 		  => 'mylink', 
									'description' => 'n/a'
									);	
		$this->feed['04-10'][] = array(
									'pubTime' 	  => 1342522773, 
									'src' 		  => 'hoteljobs.com', 
									'title'		  => 'mytitle 3',
									'link' 		  => 'mylink', 
									'description' => 'n/a'
									);
							

		//var_dump($this->feed['postPubDate']);die;
		krsort($this->feed);

		echo '<div>';
		
		foreach($this->feed as $key => $articles)
		{var_dump($this->feed);die;
			usort($this->feed[$key], function($a, $b) { return $a['pubTime'] > $a['pubTime']; });
/*
			foreach($date as $article)
			{
				#drill down to the layer we perform sort within, not the next sublayer which holds the sorting key
				usort($date, function($a, $b) { return $a['pubTime'] > $b['pubTime']; });
			
				?>
				<div>
					<h3><a href="<?php echo $article['link']; ?>"><?php echo $article['title']; ?></a></h3>
					<p><?php echo $article['src']; ?></p>
					<p><?php echo $article['description']; ?></p>
				</div>
				<?php 
			}*/		var_dump($this->feed);

		}
		/*
		foreach($articles as $article)
		{
			?>
			<div>
				<h3><a href="<?php echo $article['link']; ?>"><?php echo $article['title']; ?></a></h3>
				<p><?php echo $article['src']; ?></p>
				<p><?php echo $article['description']; ?></p>
			</div>
			<?php 	*/
		
		echo '</div>';
	}
	


	public function strip_cdata_tags($string, $tag_strip = true)
	{
		$return_string = str_replace(array('<![CDATA[', ']]>'), '', $string);
				
		if($tag_strip) 
			$return_string = strip_tags($return_string);
		
		$return_string = htmlentities($return_string, ENT_IGNORE, "UTF-8");
		
		return $return_string;
	}
	

		
}