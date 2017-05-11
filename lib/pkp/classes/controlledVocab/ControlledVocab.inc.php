<?php
/**
 * @defgroup controlled_vocab Controlled Vocabulary
 */

/**
 * @file classes/controlledVocab/ControlledVocab.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ControlledVocab
 * @ingroup controlled_vocab
 * @see ControlledVocabDAO
 *
 * @brief Basic class describing an controlled vocab.
 */

class ControlledVocab extends DataObject {
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}

	//
	// Get/set methods
	//

	/**
	 * get assoc id
	 * @return int
	 */
	function getAssocId() {
		return $this->getData('assocId');
	}

	/**
	 * set assoc id
	 * @param $assocId int
	 */
	function setAssocId($assocId) {
		$this->setData('assocId', $assocId);
	}

	/**
	 * Get associated type.
	 * @return int
	 */
	function getAssocType() {
		return $this->getData('assocType');
	}

	/**
	 * Set associated type.
	 * @param $assocType int
	 */
	function setAssocType($assocType) {
		$this->setData('assocType', $assocType);
	}

	/**
	 * Get symbolic name.
	 * @return string
	 */
	function getSymbolic() {
		return $this->getData('symbolic');
	}

	/**
	 * Set symbolic name.
	 * @param $symbolic string
	 */
	function setSymbolic($symbolic) {
		$this->setData('symbolic', $symbolic);
	}

	/**
	 * Get a list of controlled vocabulary options.
	 * @param $settingName string optional
	 * @return array $controlledVocabEntryId => name
	 */
	function enumerate($settingName = 'name') {
		$controlledVocabDao = DAORegistry::getDAO('ControlledVocabDAO');
		return $controlledVocabDao->enumerate($this->getId(), $settingName);
	}
}

?>
