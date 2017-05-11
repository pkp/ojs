<?php
/**
 * @file classes/xml/XMLHelper.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class XMLHelper
 * @ingroup xml
 *
 * @brief A class that groups useful XML helper functions.
 */


class XMLHelper {
	/**
	 * Take an XML node and generate a nested array.
	 * @param $xmlNode DOMDocument
	 * @param $keepEmpty boolean whether to keep empty elements, default: false
	 * @return mixed
	 */
	function &xmlToArray(&$xmlNode, $keepEmpty = false) {
		// Loop through all child nodes of the xml node.
		$resultArray = array();
		foreach ($xmlNode->childNodes as $childNode) {
			if ($childNode->nodeType == 1) {
				$childNodes =& $childNode->childNodes;
				if ($childNodes->length > 1) {
					// Recurse
					$resultArray[$childNode->nodeName] = $this->xmlToArray($childNode);
				} elseif ( ($childNode->nodeValue == '' && $keepEmpty) || ($childNode->nodeValue != '') ) {
					if (isset($resultArray[$childNode->nodeName])) {
						if (!is_array($resultArray[$childNode->nodeName])) {
							// We got a second value with the same key,
							// let's convert this element into an array.
							$resultArray[$childNode->nodeName] = array($resultArray[$childNode->nodeName]);
						}

						// Add the child node to the result array
						$resultArray[$childNode->nodeName][] = $childNode->nodeValue;
					} else {
						// This key occurs for the first time so
						// set it as a scalar value.
						$resultArray[$childNode->nodeName] = $childNode->nodeValue;
					}
				}
				unset($childNodes);
			}
		}

		return $resultArray;
	}
}

?>
