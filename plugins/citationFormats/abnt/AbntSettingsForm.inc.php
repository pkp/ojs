<?php

/**
 * @file AbntSettingsForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Contributed by Lepidus Tecnologia
 *
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AbntSettingsForm
 * @ingroup plugins_citationFormats_abnt
 *
 * @brief Form for journal managers to modify ABNT Citation plugin settings
 */

import('lib.pkp.classes.form.Form');

class AbntSettingsForm extends Form {

	/** @var int */
	var $journalId;

	/** @var object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function __construct(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::__construct($plugin->getTemplatePath() . 'settingsForm.tpl');
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$this->_data = array(
 			'location' => $plugin->getSetting($journalId, 'location')
		);
	}

	/**
	 * Get the list of field names for which localized settings are used.
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('location');
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
 		$this->readUserVars(array('location'));
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;

		$value = $this->getData('location');
		if (is_array($value)) {
			$plugin->updateSetting($this->journalId, 'location', $value, 'object');
		}
	}
}

?>
