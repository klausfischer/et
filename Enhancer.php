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
		} 
		if (isset($entities->entities->organization)) {
			$this->meta->organizations = $entities->entities->organization;	
		}

		$this->wrapTextInParagraphs();

		// insert images for locations & people
		if (isset($_POST["form-i-images-locations"]) && isset($this->meta->locations)) {
			$this->getImagesForLocations();
		}
		if (isset($_POST["form-i-images-people"]) && isset($this->meta->people)) {
			$this->getImagesForPeople();
		}
		
	}
	function wrapTextInParagraphs() {
		$wrappedText = "<p>" . implode( "</p><p>", preg_split( '/\n(?:\s*\n)+/', $this->text ) ) . "</p>";
		$this->text = $wrappedText;
	}
	function getImagesForLocations() {

		// get images for locations
		foreach ($this->meta->locations as $loc) {
			$json = $this->get_url_contents('http://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=' . urlencode($loc));
			$data = json_decode($json);
			// all results for one location
			foreach ($data->responseData->results as $result) {
				$results[$loc][] = array('url' => $result->url, 'alt' => $result->title, 'tbUrl' => $result->tbUrl);
			}
		}

		$this->addImagesToContent($results);		
		
	}
	function getImagesForPeople() {

		$imgrights = "&as_rights=cc_publicdomain";
		$imgtype = "&imgtype=face";

		// get images for persons
		foreach ($this->meta->people as $person) {
			$json = $this->get_url_contents('http://ajax.googleapis.com/ajax/services/search/images?v=1.0&q=' . urlencode($person) . $imgtype. $imgrights);
			$data = json_decode($json);
			// all results for one person
			foreach ($data->responseData->results as $result) {
				$results[$person][] = array('url' => $result->url, 'alt' => $result->title, 'tbUrl' => $result->tbUrl);
			}
		}

		$this->addImagesToContent($results);
	}
	function addImagesToContent($results) {

		foreach ($results as $locationname => $data) {
			$imgmarkup = "
 					<figure class='figure'>
 						<a class='figure__link' href='" . $data[0]["url"] . "' content='" . $data[0]["alt"] . "' target='_blank'>
 							<img class='figure__img' src='" . $data[0]["tbUrl"] . "' alt='" . $data[0]["alt"] . "'/>
 						</a>
 						<figcaption class='figure__caption'>" . $data[0]["alt"] . "</figcaption>
 					</figure>";		

			// replace first occurrence in text
 			$pos = strpos($this->text, $locationname);
 			if ($pos !== false) {
 				// add the img tag after the closing paragraph of matched word
 				$pos = strpos($this->text, "</p>", $pos);
 				$this->text = substr_replace($this->text,$imgmarkup,$pos,0);
 			}
		}
	}

	function get_url_contents($url) {
		$crl = curl_init();

		curl_setopt($crl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
		curl_setopt($crl, CURLOPT_URL, $url);
		curl_setopt($crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($crl, CURLOPT_CONNECTTIMEOUT, 5);

		$ret = curl_exec($crl);
		curl_close($crl);
		return $ret;
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