<?php

require __DIR__ . '/config/config.php';
require __DIR__ . '/vendor/autoload.php';
date_default_timezone_set('Europe/Vienna');

class Enhancer {

	private $textapi = "";
	// text to be analyzed
	private $text;
	// meta data for text
	private $meta;
	private $hashtags;
	private $result;
	private $log;

	public function Enhancer() {
		$this->meta = new stdClass();
		$this->textapi = new AYLIEN\TextAPI(APP_ID, APP_KEY);
	}
	
	function analyzeText($text) {
		$this->text = $text;

		$entities = $this->textapi->Entities(array('text' => $this->text));
	
		// debug output
		// foreach ($entities->entities as $type => $values) {
		// 	printf($type . ": " . implode(', ', $values) . "<br/><br/>");
		// }

		
		// extract meta & cleanup
		if (isset($entities->entities->date)) {
			foreach ($entities->entities->date as $d) {
				// valiDATE 
				if (strtotime($d)) {
					$this->meta->dates[] = $d;
				}
			}
		}
		if (isset($entities->entities->location)) {
			$this->meta->locations = $entities->entities->location;
		}
		if (isset($entities->entities->keyword)) {
			$this->meta->keywords = $entities->entities->keyword;	
		}
		if (isset($entities->entities->person)) {
			$this->meta->people = $entities->entities->person;	
			$this->meta->people = $this->removeSingleWords($this->meta->people);
			
		} 
		if (isset($entities->entities->organization)) {
			$this->meta->organizations = $entities->entities->organization;	
		}
		$this->fetchHashtags();
		$this->wrapTextInParagraphs();

		// insert images for locations & people
		if (isset($_POST["form-i-images-locations"]) && isset($this->meta->locations)) {
			$this->addImagesForTerms($this->meta->locations);
		}
		if (isset($_POST["form-i-images-people"]) && isset($this->meta->people)) {
			$this->addImagesForTerms($this->meta->people);
		}
		if (isset($_POST["form-i-links-people"]) && isset($this->meta->people)) {
			$this->getLinksForKeywords($this->meta->people);
		}
		if (isset($_POST["form-i-links-organizations"]) && isset($this->meta->organizations)) {
			$this->getLinksForKeywords($this->meta->organizations);
		}
	}
	
	
	function getLinksForKeywords($keywords) {

		foreach ($keywords as $person) {
			$json = $this->get_url_contents('https://en.wikipedia.org/w/api.php?action=query&titles=' . rawurlencode($person) . '&prop=info&inprop=url&format=json');
			$data = json_decode($json);

			if (isset($data->query->pages)) {
				foreach ($data->query->pages as $key => $page) {
					if ($key > -1) {
						$fullurl = $page->fullurl;
						$title = $data->query->pages->title;
						$amarkup = "<a href='" . $fullurl . "' title='" . $title . "' target='_blank'>" . $person . "</a>";
						
						$pos = strpos($this->text, $person);
						$this->text = substr_replace($this->text,$amarkup,$pos,strlen($person));		
					}
				}
				
			}
			
		}
	}
	function getImagesForLocations() {

		// get images for locations
		foreach ($this->meta->locations as $loc) {
			$json = $this->get_url_contents('http://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=' . urlencode($loc));
			$data = json_decode($json);

			if (isset($data->responseData)) {
				// all results for one location
				foreach ($data->responseData->results as $result) {
					$results[$loc][] = array('url' => $result->url, 'alt' => $result->title, 'tbUrl' => $result->tbUrl);
				}	
			}
			
		}
		if (count($results) > 0) {
			$this->addImagesToContent($results);	
		}
		
		
	}
	function addImagesForTerms($ar) {

		$allImages = array();
		foreach ($ar as $term) {
			
			$results = $this->getWikipediaPageImages($term);

			if (count($results) > 0) {
				$allImages[$term] = $results[0];
			}
		}

		if (count($allImages) > 0) {
			$this->addImagesToContent($allImages);
		}

		// deprecated Google Image Search
		// $imgrights = "&as_rights=cc_publicdomain";
		// $imgtype = "&imgtype=face";

		// // get images for persons
		// foreach ($this->meta->people as $person) {
		// 	$json = $this->get_url_contents('http://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=' . urlencode($person) . $imgtype. $imgrights);
		// 	$data = json_decode($json);

		// 	// all results for one person
		// 	foreach ($data->responseData->results as $result) {
		// 		$results[$person][] = array('url' => $result->url, 'alt' => $result->title, 'tbUrl' => $result->tbUrl);
		// 	}
		// }
		
	}
	function addImagesToContent($results) {

		foreach ($results as $termInText => $data) {
			$imgmarkup = "
			<figure class='figure'>
				<a class='figure__link' href='" . $data["url"] . "' content='" . $data["title"] . "' target='_blank'>
					<img class='figure__img' src='" . $data["tbUrl"] . "' alt='" . $data["title"] . "'/>
				</a>
				<figcaption class='figure__caption'>" . $data["title"] . "</figcaption>
			</figure>";		

			// replace first occurrence in text
			$pos = strpos($this->text, $termInText);
			if ($pos !== false) {
 				// add the img tag after the closing paragraph of matched word
				$pos = strpos($this->text, "</p>", $pos);
				$this->text = substr_replace($this->text,$imgmarkup,$pos,0);
			}
		}
	}

	function getWikipediaPageImages($term){

		$term_ = str_replace(" ", "_", $term);
		$url = "http://en.wikipedia.org/w/api.php?action=query&titles=".$term_."&prop=pageimages&format=json&pithumbsize=200";
		$json = $this->get_url_contents($url);

		$results = array();
		$json_array = json_decode($json, true);
		foreach($json_array['query']['pages'] as $page){
			if(!isset($page['missing'])){
				$title = $page["title"];
				$tbUrl = $page["thumbnail"]["source"];
				$url = "https://en.wikipedia.org/wiki/File:" . $page["pageimage"];
				if ($tbUrl) {
					$results[] = array("title" => $title, "tbUrl" => $tbUrl, "url" => $url);	
				}
			}
		}
		return $results;
	}

	function get_url_contents($url) {
		$crl = curl_init();

		curl_setopt($crl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/534.30 (KHTML, like Gecko) Chrome/12.0.742.112 Safari/534.30');
		curl_setopt($crl, CURLOPT_URL, $url);
		curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($crl, CURLOPT_FOLLOWLOCATION, true);

		$ret = curl_exec($crl);

		if (!$ret) {
			var_dump($ret);
			var_dump($crl);
			exit('cURL Error: '. curl_error($crl));
		}
		curl_close($crl);
		return $ret;
	}

	function wrapTextInParagraphs() {
		$wrappedText = "<p>" . implode( "</p><p>", preg_split( '/\n(?:\s*\n)+/', $this->text ) ) . "</p>";
		$this->text = $wrappedText;
	}

	function fetchHashtags() {
		$result = $this->textapi->Hashtags(array('text' => $this->text));
		$this->hashtags = $result->hashtags;

	}

	function getHashtags() {
		return $this->hashtags;
	}

	function removeSingleWords($ar) {
		foreach ($ar as $item) {
			if(strpos(trim($item), ' ') === false)
			{
					// just a single word, remove it from array
				$key = array_search($item,$ar);
				if($key!==false){
					unset($ar[$key]);
				}
			}
		}
		return $ar;
	}
	function timesInArray($ar, $findTerm) {
		$count = 0;
		foreach($ar as $item) {
			if (strpos($item, $findTerm) !== false ) {
				$count++;
			}
		}
		return $count;
	}

	function getMeta() {
		return $this->meta;
	}

	function getEnhancedText() {
		return $this->text;
	}
	
	function debug($output) {
		echo '<pre>' . var_export($output, true) . '</pre>';
	}

}


?>