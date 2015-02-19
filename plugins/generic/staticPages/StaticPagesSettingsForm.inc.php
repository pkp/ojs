<?php

/**
 * @file plugins/generic/staticPages/StaticPagesSettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesSettingsForm
 *
 * Form for journal managers to modify Static Page content and title
 *
 */

import('lib.pkp.classes.form.Form');

class StaticPagesSettingsForm extends Form {
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
	function StaticPagesSettingsForm(&$plugin, $journalId) {

		parent::Form($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		$this->addCheck(new FormValidatorPost($this));
	}


	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		$staticPagesDao =& DAORegistry::getDAO('StaticPagesDAO');

		$rangeInfo =& Handler::getRangeInfo('staticPages');
		$staticPages = $staticPagesDao->getStaticPagesByJournalId($journalId);
		$this->setData('staticPages', $staticPages);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('pages'));
	}

	/**
	 * Save settings/changes
	 */
	function execute() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
	}

}
?>
