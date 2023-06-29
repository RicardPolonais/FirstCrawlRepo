<?
function progressCallback($resource, $download_size = 0, $downloaded = 0, $upload_size = 0, $uploaded = 0)
{
	//die();
}

class siteMapper
{
	protected $startUrl;
	protected $depth;
	protected $parsed;
	protected $seen;
	protected $found;
	protected $iteracja;
	
	public function __construct($startUrl, $depth = 2)
   {
		$this->startUrl = rtrim($startUrl, "/");
      $this->depth = $depth;
      $this->parse = parse_url($startUrl);
   }
	
	public function crawl_page($url, $depth)
	{
		//PA($url);
		
		//$url = rtrim($url, '/');
		
		//$this->iteracja++;
		if( $depth == 0  ) {
			//echo "zero";exit;
		  return;
		}
		$itr=100;
		if( $this->iteracja>$itr  ) {
		  $this->seen["iteracja>".$itr] = true;
		  return;
		}
		
		$this->seen[$url] = true;
		//PA($this->seen);
		
		unset($output);
		// Initialize curl
		$ch = curl_init();
		 
		// URL for Scraping
		curl_setopt($ch, CURLOPT_URL,
			 $url);
		 
		// Return Transfer True
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , 5);
		curl_setopt($ch, CURLOPT_BUFFERSIZE, 128); // more progress info
		curl_setopt($ch, CURLOPT_NOPROGRESS, false);
		curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progressCallback');
		
		//curl_setopt($ch, CURLOPT_NOPROGRESS, false);
		$output = curl_exec($ch);
		//echo( $output);exit;
		// Closing cURL
		curl_close($ch);
		
		$dom = new DOMDocument('1.0');
	//$dom->loadHTML(file_get_contents($url, false, null, 0, 51200));
		$dom->loadHTML($output);
		
		$xpath = new DOMXpath($dom);
		$nodes = $xpath->query('//a');
		
		//$anchors = $dom->getElementsByTagName('a');

		
		foreach($nodes as $node) {
			$href = $node->getAttribute('href');
			$parse_href = parse_url($href);
			$new_crawl_addr = $this->parse["scheme"]."://".$this->parse["host"].$parse_href["path"];
			
			if( ($this->parse["host"] == $parse_href["host"] AND ($parse_href["scheme"]=="http" OR $parse_href["scheme"]=="https"))
					OR 
				 (empty($parse_href["host"]) AND !empty($parse_href["path"]))
			){
				//PA($parse_href);
					if(!isset($this->seen[$new_crawl_addr])){
						$this->found[$new_crawl_addr] = true;
						$this->crawl_page($new_crawl_addr, $depth - 1);
						//PA($depth - 1);
					}
			}else{
				//PA($parse_href);
			}
			
		}
		
	}
	public function run()
	{
		//PA($this->startUrl);
        $this->crawl_page($this->startUrl, $this->depth);
		  PA($this->found);
		  //PA($this->seen);
		 // echo "1111";exit;
		 //PA($this->_sitemap);
	}
}



?>