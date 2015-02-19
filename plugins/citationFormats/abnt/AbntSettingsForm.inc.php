<?php

/**
 * @file AbntSettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
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

	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	function AbntSettingsForm(&$plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');
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
		$journalId =& Request::getJournal()->getId();
		$plugin =& $this->plugin;

		$value = $this->getData('location');
		if (is_array($value)) {
			$plugin->updateSetting($journalId, 'location', $value, 'object');
		}
	}
}

?>
