<?php
/**
 * @file plugins/importexport/native/filter/NativeFilterHelper.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2000-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NativeFilterHelper
 * @ingroup plugins_importexport_native
 *
 * @brief Class that provides native import/export filter-related helper methods.
 */

class NativeFilterHelper {

	/**
	 * Create and return an issue identification node.
	 * @param $filter NativeExportFilter
	 * @param $doc DOMDocument
	 * @param $issue Issue
	 * @return DOMElement
	 */
	function createIssueIdentificationNode($filter, $doc, $issue) {
		$deployment = $filter->getDeployment();
		$vol = $issue->getVolume();
		$num = $issue->getNumber();
		$year = $issue->getYear();
		$title = $issue->getTitle(null);
		assert($issue->getShowVolume() || $issue->getShowNumber() || $issue->getShowYear() || $issue->getShowTitle());
		$issueIdentificationNode = $doc->createElementNS($deployment->getNamespace(), 'issue_identification');
		if ($issue->getShowVolume()) {
			assert(!empty($vol));
			$issueIdentificationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'volume', htmlspecialchars($vol, ENT_COMPAT, 'UTF-8')));
		}
		if ($issue->getShowNumber()) {
			assert(!empty($num));
			$issueIdentificationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'number', htmlspecialchars($num, ENT_COMPAT, 'UTF-8')));
		}
		if ($issue->getShowYear()) {
			assert(!empty($year));
			$issueIdentificationNode->appendChild($node = $doc->createElementNS($deployment->getNamespace(), 'year', $year));
		}
		if ($issue->getShowTitle()) {
			assert(!empty($title));
			$filter->createLocalizedNodes($doc, $issueIdentificationNode, 'title', $title);
		}
		return $issueIdentificationNode;
	}

}

