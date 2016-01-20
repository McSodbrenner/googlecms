<?php

class GoogleCMS {
	protected $_id;
	protected $_request_block_size;

	public function __construct($id, $request_block_size = 10) {
		$this->_id = $id;
		$this->_request_block_size = $request_block_size;
	}

	public function getData($array_divider = '.') {
		// get the data for all sheets
		$data_raw = $this->_getSheetData();

		// now we have the json results and are able to restructure the array to a simple 2D matrix
		// this array will be simpler to process
		$data_matrix = $this->_get2DMatrix($data_raw);

		// now we build simple key value pairs per page
		$data = $this->_getFinalData($data_matrix);

		// the final step is to explode the dividers in the array keys
		$data = $this->_explodeSyntax($data, $array_divider);

		return $data;
	}

	protected function _getSheetData() {
		$sheet_start	= 1;
		$results		= [];

		// we do not know how many sheets we have to get
		// so we try 10 sheets in parallel and take a look if there was a non existing sheet
		while (true) {
			$requests = [];
			for ($sheet = $sheet_start; $sheet < $sheet_start+$this->_request_block_size; $sheet++) {
				$requests[] = 'https://spreadsheets.google.com/feeds/cells/' . $this->_id . '/' . $sheet . '/public/values?alt=json';
			}

			$temporary_results = $this->_multiRequest($requests);

			foreach ($temporary_results as $result) {
				// if we do not get json back anymore skip retrieving more sheets
				if ($result{0} !== '{') break 2;
				$results[] = $result;
			}

			$sheet_start = $sheet;
		}

		return $results;
	}

	protected function _get2DMatrix($raw) {
		$data = [];
		foreach ($raw as $text) {
			$json = json_decode($text, true);

			$sheet_name = $json['feed']['title']['$t'];
			if( strpos($sheet_name, '[IGNORE]') !== 0 ){
				foreach ($json['feed']['entry'] as $entry) {
					list($row, $col, $text) = array_values($entry['gs$cell']);
					$data[$sheet_name][$row][$col] = $text;
				}
			}
		}

		return $data;
	}

	protected function _getFinalData($data_matrix) {
		$data = [];

		// iterate sheets
		foreach ($data_matrix as $sheet => $page) {

			// get the content_headers
			// if we do it this way it doesn't matter if cell(1,1) is empty or not
			unset($page[1][1]);
			$content_headers = $page[1];

			// now iterate content_headers (e.g. language strings like "en", "de")
			foreach ($content_headers as $col => $header) {

				// the value in col 1 doesn't matter because it is the string identifier
				if ($col === 1) continue;

				// iterate all values
				foreach ($page as $row) {
					// if there is no identifier it is probably a headline in the spreadsheet for a better visualizing
					if (!isset($row[1])) continue;

					$key = $row[1];
					if (isset($row[$col])) {
						// if the key does already exist transform to array
						if (isset($data[$sheet][$header][$key])) {
							if (!is_array($data[$sheet][$header][$key])) {
								$data[$sheet][$header][$key] = array(0 => $data[$sheet][$header][$key]);
							}
							$length = count($data[$sheet][$header][$key]);
							$data[$sheet][$header][$key][$length] = $row[$col];

						// else we have a simple key value pair
						} else {
							$data[$sheet][$header][$key] = $row[$col];
						}
					}
				}
			}
		}

		return $data;
	}

	protected function _multiRequest($data) {
		// array of curl handles
		$curly = array();

		// data to be returned
		$result = array();

		// multi handle
		$mh = curl_multi_init();

		// loop through $data and create curl handles
		// then add them to the multi-handle
		foreach ($data as $id => $url) {

			$curly[$id] = curl_init();

			curl_setopt($curly[$id], CURLOPT_URL,            $url);
			curl_setopt($curly[$id], CURLOPT_HEADER,         0);
			curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curly[$id], CURLOPT_TIMEOUT, 10);
			curl_multi_add_handle($mh, $curly[$id]);
		}

		// execute the handles
		$running = null;
		do {
			curl_multi_exec($mh, $running);
		} while($running > 0);


		// get content and remove handles
		foreach($curly as $id => $c) {
			$result[$id] = curl_multi_getcontent($c);
			curl_multi_remove_handle($mh, $c);
		}

		// all done
		curl_multi_close($mh);

		return $result;
	}
	protected function _explodeSyntax(array $array, $array_divider) {
		$data = array();

		// iterate keys
		foreach ($array as $rkey => $row) {
			$parent =& $data;
			$parts = explode($array_divider, $rkey);

			// iterate key parts
			foreach ($parts as $part) {
				//  create subkey "part" if it does not exist
				if (!isset($parent[$part]) || !is_array($parent[$part])) {
					if ($part === end($parts)) {
						if (!is_array($row)) {
							$parent[$part] = $row;
						} else {
							// recursive part
							$parent[$part] = $this->_explodeSyntax($row, $array_divider);
						}
					} else {
						// create sub array if we are not the last part
						$parent[$part] = array();
					}
				}
				$parent = &$parent[$part];
			}
		}
		return $data;
	}

	public function find($haystack, $key, $needle) {
		$results		= array();
		$keys			= explode('|', $key);
		$current_key	= array_shift($keys);

		foreach ($haystack as $key => $haybale) {
			$result = $this->_find_recursive($haybale, $keys, $needle);
			if ($result) $results[] = $key;
		}

		$returner = array();
		foreach ($results as $result) {
			$returner[$result] = $haystack[$result];
		}

		return $returner;
	}

	protected function _find_recursive($haystack, $keys, $needle) {
		$search_key	= array_shift($keys);

		foreach ($haystack as $key => $haybale) {
			if ($search_key != '*' && $search_key != $key) continue;

			$current_key = $key;
			if (is_array($haybale) && $this->_find_recursive($haybale, $keys, $needle) !== false) {
				return $current_key;
			}
			if ($needle === $haybale) {
				return $current_key;
			}
		}
		return false;
	}
}
