<?php

/**
 * @file plugins/generic/customBlockManager/SettingsForm.inc.php
 *
 * Copyright (c) 2003-2013 Simon Fraser University Library
 * Copyright (c) 2003-2013 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class SettingsForm
 *
 * @brief Form for journal managers to add or delete sidebar blocks
 *
 */

import('lib.pkp.classes.form.Form');

class SettingsForm extends Form {
	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/** $var $errors string */
	var $errors;

	/**
	 * Constructor
	 * @param $journalId int
	 */
	function SettingsForm(&$plugin, $journalId) {

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->journalId = $journalId;
		$this->plugin =& $plugin;

	}

	/**
	 * Initialize form data from  the plugin settings to the form
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$templateMgr =& TemplateManager::getManager();

		$blocks = $plugin->getSetting($journalId, 'blocks');

		if ( !is_array($blocks) ) {
			$this->setData('blocks', array());
		} else {
			$this->setData('blocks', $blocks);
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'blocks',
				'deletedBlocks'
			)
		);
	}

	/**
	 * Update the plugin settings
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$pluginSettingsDao =& DAORegistry::getDAO('PluginSettingsDAO');

		$deletedBlocks = explode(':',$this->getData('deletedBlocks'));
		foreach ($deletedBlocks as $deletedBlock) {
			$pluginSettingsDao->deleteSetting($journalId, $deletedBlock.'CustomBlockPlugin', 'enabled');
			$pluginSettingsDao->deleteSetting($journalId, $deletedBlock.'CustomBlockPlugin', 'seq');
			$pluginSettingsDao->deleteSetting($journalId, $deletedBlock.'CustomBlockPlugin', 'context');
			$pluginSettingsDao->deleteSetting($journalId, $deletedBlock.'CustomBlockPlugin', 'blockContent');
		}

		//sort the blocks in alphabetical order
		$blocks = $this->getData('blocks');
		ksort($blocks);

		//remove any blank entries that made it into the array
		foreach ($blocks as $key => $value) {
			if (is_null($value) || trim($value)=="") {
				unset($blocks[$key]);
			}
		}

		// Update blocks
		$plugin->updateSetting($journalId, 'blocks', $blocks);
		$this->setData('blocks',$blocks);
	}
}

?>
