<?php

/**
 * @file classes/controlledVocab/ControlledVocabEntry.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2000-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ControlledVocabEntry
 * @ingroup controlled_vocabs
 * @see ControlledVocabEntryDAO
 *
 * @brief Basic class describing a controlled vocab.
 */


class ControlledVocabEntry extends DataObject {
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
	 * Get the ID of the controlled vocab.
	 * @return int
	 */
	function getControlledVocabId() {
		return $this->getData('controlledVocabId');
	}

	/**
	 * Set the ID of the controlled vocab.
	 * @param $controlledVocabId int
	 */
	function setControlledVocabId($controlledVocabId) {
		$this->setData('controlledVocabId', $controlledVocabId);
	}

	/**
	 * Get sequence number.
	 * @return float
	 */
	function getSequence() {
		return $this->getData('sequence');
	}

	/**
	 * Set sequence number.
	 * @param $sequence float
	 */
	function setSequence($sequence) {
		$this->setData('sequence', $sequence);
	}

	/**
	 * Get the localized name.
	 * @return string
	 */
	function getLocalizedName() {
		return $this->getLocalizedData('name');
	}

	/**
	 * Get the name of the controlled vocabulary entry.
	 * @param $locale string
	 * @return string
	 */
	function getName($locale) {
		return $this->getData('name', $locale);
	}

	/**
	 * Set the name of the controlled vocabulary entry.
	 * @param $name string
	 * @param $locale string
	 */
	function setName($name, $locale) {
		$this->setData('name', $name, $locale);
	}
}

?>
