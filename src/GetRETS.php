<?php

namespace timitek\GetRETS;

class ApiClient {
	private $url = "http://getrets.net";
	public function getUrl() { return $this->url; }
	public function setUrl($value) { $this->url = $value; return $this; }
	
	private $customerKey = "";
	public function getCustomerKey() { return $this->customerKey; }
	public function setCustomerKey($value) { $this->customerKey = $value; return $this; }
	
	public function __construct($customerKey, $url = null) {
		$this->setCustomerKey($customerKey);
		if ($url) {
			$this->setUrl($url);
		}
	}
	
	public function getFromApi($getUrl) {
		$contents = null;
		$results = null;

		if (file_get_contents(__FILE__) && ini_get('allow_url_fopen')) {
			$contents = file_get_contents($getUrl);
		}
		else if (function_exists('curl_version')) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $getUrl);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$contents = curl_exec($curl);
			curl_close($curl);
		}
		else {
			die('You have neither cUrl installed nor allow_url_fopen activated. Please setup one of those!');
		}

		if (!empty($contents)) {
			$results = json_decode($contents);
		}

		return $results;
	}
	
	public function postToApi($postUrl, $postData, $encodeData = true) {
            
		$contents = null;
		$results = null;
                
		if (file_get_contents(__FILE__) && ini_get('allow_url_fopen')) {
                        $payload = ($encodeData ? json_encode($postData) : $postData);
                        $context = stream_context_create(array(
                                                        'http' => array(
                                                                'method' => 'POST',
                                                                'header' => "Content-Type: application/json; charset=utf-8\r\n",
                                                                'content' => $payload)));

                        $contents = file_get_contents($postUrl, false, $context);
                        
                        if($contents === false){
                                die('Error');
                        }
		}
		else if (function_exists('curl_version')) {
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $postUrl);
                        curl_setopt($curl, CURLOPT_POST, count($postData));
                        $postFields = null;
                        foreach($postData as $key => $value) {
                            $postFields .= $key . '=' . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . '&';
                        }
                        //$postFields = http_build_query($postData);
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
                        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$contents = curl_exec($curl);
			curl_close($curl);
		}
		else {
			die('You have neither cUrl installed nor allow_url_fopen activated. Please setup one of those!');
		}

		if (!empty($contents)) {
			$results = json_decode($contents);
		}

		return $results;
	}
}

class Listing extends ApiClient {
	private $searchType = "Listing";
	public function getSearchType() { return $this->searchType; }
	public function setSearchType($value) { $this->searchType = $value; return $this; }
	
	private $sortBy = "rawListPrice";
	public function getSortBy() { return $this->sortBy; }
	public function setSortBy($value) { $this->sortBy = $value; return $this; }
        
        private $reverseSort = false;
	public function getReverseSort() { return $this->reverseSort; }
	public function setReverseSort($value) { $this->reverseSort = $value; return $this; }
        
        protected function sort() {
            $key = $this->getSortBy();
            $reverse = $this->getReverseSort();
            return function ($a, $b) use ($key, $reverse) {
                if (is_string($a->$key)) {
                    return strcmp($a->$key, $b->$key) * ($reverse ? -1 : 1);
                }
                else {
                    return ($reverse ? $a->$key < $b->$key : $a->$key > $b->$key);
                }
            };
        }
        
        protected function sortResults($results) {
            $output = $results;
            
			$sbCheck = $this->getSortBy();
            if (!empty($sbCheck)) {
                usort($output, $this->sort());
            }
            
            return $output;
        }
	
	public function searchByKeyword($keyword) {
            $results = $this->getFromAPI($this->getUrl() . '/api/' . $this->getCustomerKey() . '/'. $this->searchType . '/SearchByKeyword/' . rawurlencode($keyword));
            return $this->sortResults($results);
	}

	public function search($keyword, $maxPrice, $minPrice, $includeResidential, $includeLand, $includeCommercial) {
		$postData = array('Keyword' => $keyword,
						  'MaxPrice' => intval($maxPrice),
						  'MinPrice' => intval($minPrice),
						  'IncludeResidential' => boolval($includeResidential),
						  'IncludeLand' => boolval($includeLand),
						  'IncludeCommercial' => boolval($includeCommercial));

		$results = $this->postToAPI($this->getUrl() . '/api/' . $this->getCustomerKey() . '/'. $this->getSearchType() . '/Search', $postData);
                
                return $this->sortResults($results);
	}

	public function details($listingSource, $listingType, $listingId) {
		return $this->getFromApi($this->getUrl() . '/api/' . $this->getCustomerKey() . '/'. $this->getSearchType() . '/Details/' . $listingSource . '/' . $listingType . '/' . $listingId);
	}
	
	public function imageUrl($listingSource, $listingType, $listingId, $photoId, $width = null, $height = null) {
		$img = $this->getUrl() . '/api/' . $this->getCustomerKey() . '/'. $this->getSearchType() . '/Image/' . $listingSource . '/' . $listingType . '/' . $listingId . '/' . $photoId;
		if ($width) {
			$img .= '?newWidth=' . $width . '&maxHeight=' .$height;
		}
		return $img;
	}
}

class RETSListing extends Listing {
	public function __construct($customerKey, $url = null) {
		$this->setSearchType("RETSListing");
		parent::__construct($customerKey, $url);
	}

	public function getListingsByDMQL($query, $feedName, $listingType) {
		$results = $this->getFromAPI($this->getUrl() . '/api/' . $this->getCustomerKey() . '/RETSListing/GetListingsByDMQL/' . $feedName . '/' . '?query=' . rawurlencode($query) . '&listingType=' . $listingType);
                if ($results && $results->data) {
                    $results->data = $this->sortResults($results->data);
                }
                return $results;
	}

	public function executeDMQL($query, $feedName, $listingType) {
		return $this->getFromAPI($this->getUrl() . '/api/' . $this->getCustomerKey() . '/RETSListing/executeDMQL/' . $feedName . '/' . '?query=' . rawurlencode($query) . '&listingType=' . $listingType);
	}
}


class Geocoding extends ApiClient {
	public function googleGeocode($address) {
		return $this->getFromAPI($this->getUrl() . '/api/' . $this->getCustomerKey() . '/Geocoding/GoogleGeocode?address='. rawurlencode($address));
	}	

	public function parseGoogleResults($googleResults) {
		return $this->postToAPI($this->getUrl() . '/api/' . $this->getCustomerKey() . '/Geocoding/ParseGoogleResults', $googleResults, false);
	}	
}

class GetRETS {
	private $listing = null;
	public function getListing() { return $this->listing; }
	
	private $retsListing = null;
	public function getRETSListing() { return $this->retsListing; }
	
	private $geocoding = null;
	public function getGeocoding() { return $this->geocoding; }
	
	public function __construct($customerKey, $url = null) {
		$this->listing = new Listing($customerKey, $url);
		$this->retsListing = new RETSListing($customerKey, $url);
		$this->geocoding = new Geocoding($customerKey, $url);
	}	
}

?>