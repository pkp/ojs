<?php

/**
 * @file plugins/pubIds/urn/URNPubIdPlugin.inc.php
 *
 * Copyright (c) 2014-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class URNPubIdPlugin
 * @ingroup plugins_pubIds_urn
 *
 * @brief URN plugin class
 */


import('classes.plugins.PubIdPlugin');

class URNPubIdPlugin extends PubIdPlugin {

	//
	// Implement template methods from Plugin.
	//
	/**
	 * @see PubIdPlugin::register()
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		$this->addLocaleData();
		return $success;
	}

	/**
	 * @see Plugin::getDisplayName()
	 */
	function getDisplayName() {
		return __('plugins.pubIds.urn.displayName');
	}

	/**
	 * @see Plugin::getDescription()
	 */
	function getDescription() {
		return __('plugins.pubIds.urn.description');
	}

	/**
	 * @see Plugin::getTemplatePath()
	 * @param $inCore boolean True iff a core template should be preferred
	 */
	function getTemplatePath($inCore = false) {
		return parent::getTemplatePath($inCore) . 'templates/';
	}


	//
	// Implement template methods from PubIdPlugin.
	//
	/**
	 * @copydoc PKPPubIdPlugin::constructPubId()
	 */
	function constructPubId($pubIdPrefix, $pubIdSuffix, $contextId) {
		$urn = $pubIdPrefix . $pubIdSuffix;
		$suffixFieldName = $this->getSuffixFieldName();
		$suffixGenerationStrategy = $this->getSetting($contextId, $suffixFieldName);
		// checkNo is alread calculated for custom suffixes
		if ($suffixGenerationStrategy != 'customId' && $this->getSetting($contextId, 'urnCheckNo')) {
			$urn .= $this->_calculateCheckNo($urn);
		}
		return $urn;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdType()
	 */
	function getPubIdType() {
		return 'other::urn';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdDisplayType()
	 */
	function getPubIdDisplayType() {
		return 'URN';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdFullName()
	 */
	function getPubIdFullName() {
		return 'Uniform Resource Name';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getResolvingURL()
	 */
	function getResolvingURL($journalId, $pubId) {
		$resolverURL = $this->getSetting($journalId, 'urnResolver');
		return $resolverURL . $pubId;
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPubIdMetadataFile()
	 */
	function getPubIdMetadataFile() {
		return $this->getTemplatePath().'urnSuffixEdit.tpl';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getSettingsFormName()
	 */
	function getSettingsFormName() {
		return 'classes.form.URNSettingsForm';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getFormFieldNames()
	 */
	function getFormFieldNames() {
		return array('urnSuffix', 'excludeURN');
	}

	/**
	 * @copydoc PKPPubIdPlugin::getPrefixFieldName()
	 */
	function getPrefixFieldName() {
		return 'urnPrefix';
	}

	/**
	 * @see PKPPubIdPlugin::getSuffixFieldName()
	 */
	function getSuffixFieldName() {
		return 'urnSuffix';
	}

	/**
	 * @see PKPPubIdPlugin::getSuffixPatternsFieldName()
	 */
	function getSuffixPatternsFieldNames() {
		return  array(
			'Issue' => 'urnIssueSuffixPattern',
			'Article' => 'urnArticleSuffixPattern',
			'ArticleGalley' => 'urnArticleGalleySuffixPattern',
		);
	}

	/**
	 * @copydoc PKPPubIdPlugin::getExcludeFormFieldName()
	 */
	function getExcludeFormFieldName() {
		return 'excludeURN';
	}

	/**
	 * @copydoc PKPPubIdPlugin::getDAOFieldNames()
	 */
	function getDAOFieldNames() {
		return array('pub-id::other::urn');
	}

	/**
	 * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
	 */
	function isObjectTypeEnabled($pubObjectType, $contextId) {
		return $this->getSetting($contextId, "enable${pubObjectType}URN") == '1';
	}

	/**
	 * @copydoc PKPPubIdPlugin::isObjectTypeEnabled()
	 */
	function getNotUniqueErrorMsg() {
		return __('plugins.pubIds.urn.editor.urnSuffixCustomIdentifierNotUnique');
	}

	//
	// Private helper methods
	//
	/**
	 * Get the last, check number.
	 * Algorithm (s. http://www.persistent-identifier.de/?link=316):
	 *  every URN character is replaced with a number according to the conversion table,
	 *  every number is multiplied by it's position/index (beginning with 1),
	 *  the numbers' sum is calculated,
	 *  the sum is devided by the last number,
	 *  the last number of the quotient before the decimal point is the check number.
	 */
	function _calculateCheckNo($urn) {
	    $urnLower = strtolower_codesafe($urn);

	    $conversionTable = array('9' => '41', '8' => '9', '7' => '8', '6' => '7', '5' => '6', '4' => '5', '3' => '4', '2' => '3', '1' => '2', '0' => '1', 'a' => '18', 'b' => '14', 'c' => '19', 'd' => '15', 'e' => '16', 'f' => '21', 'g' => '22', 'h' => '23', 'i' => '24', 'j' => '25', 'k' => '42', 'l' => '26', 'm' => '27', 'n' => '13', 'o' => '28', 'p' => '29', 'q' => '31', 'r' => '12', 's' => '32', 't' => '33', 'u' => '11', 'v' => '34', 'w' => '35', 'x' => '36', 'y' => '37', 'z' => '38', '-' => '39', ':' => '17', '_' => '43', '/' => '45', '.' => '47', '+' => '49');

	    $newURN = '';
	    for ($i = 0; $i < strlen($urnLower); $i++) {
	    	$char = $urnLower[$i];
	    	$newURN .= $conversionTable[$char];
	    }
	    $sum = 0;
	    for ($j = 1; $j <= strlen($newURN); $j++) {
		    $sum = $sum + ($newURN[$j-1] * $j);
	    }

	    $lastNumber = $newURN[strlen($newURN)-1];
	    $quot = $sum / $lastNumber;
	    $quotRound = floor($quot);
	    $quotString = (string)$quotRound;

	    return $quotString[strlen($quotString)-1];
	}
}

?>
