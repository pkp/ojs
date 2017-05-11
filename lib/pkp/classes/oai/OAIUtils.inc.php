<?php

/**
 * @file classes/oai/OAIUtils.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OAI
 * @ingroup oai
 * @see OAIDAO
 *
 * @brief Utility functions used by OAI related classes.
 */


class OAIUtils {

	/**
	 * Return a UTC-formatted datestamp from the specified UNIX timestamp.
	 * @param $timestamp int *nix timestamp (if not used, the current time is used)
	 * @param $includeTime boolean include both the time and date
	 * @return string UTC datestamp
	 */
	function UTCDate($timestamp = 0, $includeTime = true) {
		$format = "Y-m-d";
		if($includeTime) {
			$format .= "\TH:i:s\Z";
		}

		if($timestamp == 0) {
			return gmdate($format);

		} else {
			return gmdate($format, $timestamp);
		}
	}

	/**
	 * Returns a UNIX timestamp from a UTC-formatted datestamp.
	 * Returns the string "invalid" if datestamp is invalid,
	 * or "invalid_granularity" if unsupported granularity.
	 * @param $date string UTC datestamp
	 * @param $checkGranularity boolean verify that granularity is correct
	 * @return int timestamp
	 */
	function UTCtoTimestamp($date, $checkGranularity = true) {
		// FIXME Has limited range (see http://php.net/strtotime)
		if (preg_match("/^\d\d\d\d\-\d\d\-\d\d$/", $date)) {
			// Match date
			$time = strtotime("$date UTC");
			return ($time != -1) ? $time : 'invalid';

		} else if (preg_match("/^(\d\d\d\d\-\d\d\-\d\d)T(\d\d:\d\d:\d\d)Z$/", $date, $matches)) {
			// Match datetime
			// FIXME
			$date = "$matches[1] $matches[2]";
			if ($checkGranularity && $this->config->granularity != 'YYYY-MM-DDThh:mm:ssZ') {
				return 'invalid_granularity';

			} else {
				$time = strtotime("$date UTC");
				return ($time != -1) ? $time : 'invalid';
			}

		} else {
			return 'invalid';
		}
	}


	/**
	 * Clean input variables.
	 * @param $data mixed request parameter(s)
	 * @return mixed cleaned request parameter(s)
	 */
	function prepInput(&$data) {
		if (!is_array($data)) {
			$data = urldecode($data);

		} else {
			foreach ($data as $k => $v) {
				if (is_array($data[$k])) {
					self::prepInput($data[$k]);
				} else {
					$data[$k] = urldecode($v);
				}
			}
		}
		return $data;
	}

	/**
	 * Prepare variables for output.
	 * Data is assumed to be UTF-8 encoded (FIXME?)
	 * @param $data mixed output parameter(s)
	 * @return mixed cleaned output parameter(s)
	 */
	function prepOutput(&$data) {
		if (!is_array($data)) {
			$data = htmlspecialchars($data);

		} else {
			foreach ($data as $k => $v) {
				if (is_array($data[$k])) {
					$this->prepOutput($data[$k]);
				} else {
					// FIXME FIXME FIXME
					$data[$k] = htmlspecialchars($v);
				}
			}
		}
		return $data;
	}

	/**
	 * Parses string $string into an associate array $array.
	 * Acts like parse_str($string, $array) except duplicate
	 * variable names in $string are converted to an array.
	 * @param $duplicate string input data string
	 * @param $array array of parsed parameters
	 */
	function parseStr($string, &$array) {
		$pairs = explode('&', $string);
		foreach ($pairs as $p) {
			$vars = explode('=', $p);
			if (!empty($vars[0]) && isset($vars[1])) {
				$key = $vars[0];
				$value = join('=', array_splice($vars, 1));

				if (!isset($array[$key])) {
					$array[$key] = $value;
				} else if (is_array($array[$key])) {
					array_push($array[$key], $value);
				} else {
					$array[$key] = array($array[$key], $value);
				}
			}
		}
	}
}

?>
