<?php

/**
 * @file plugins/generic/announcementFeed/AnnouncementFeedSettingsForm.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AnnouncementFeedSettingsForm
 * @ingroup plugins_generic_annoucementFeed
 *
 * @brief Form for journal managers to modify announcement feed plugin settings
 */

import('lib.pkp.classes.form.Form');

class AnnouncementFeedSettingsForm extends Form {

	/** @var int */
	protected $_journalId;

	/** @var object */
	protected $_plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	public function __construct($plugin, $journalId) {
		$this->_journalId = $journalId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data.
	 */
	public function initData() {
		$journalId = $this->_journalId;
		$plugin = $this->_plugin;

		$this->setData('displayPage', $plugin->getSetting($journalId, 'displayPage'));
		$this->setData('recentItems', $plugin->getSetting($journalId, 'recentItems'));
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	public function readInputData() {
		$this->readUserVars(array('displayPage', 'recentItems'));

		// check that recent items value is a positive integer
		if ((int) $this->getData('recentItems') <= 0) $this->setData('recentItems', '');
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	public function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->_plugin->getName());
		return parent::fetch($request);
	}

	/**
	 * Save settings.
	 */
	public function execute() {
		$plugin = $this->_plugin;
		$journalId = $this->_journalId;

		$plugin->updateSetting($journalId, 'displayPage', $this->getData('displayPage'));
		$plugin->updateSetting($journalId, 'recentItems', $this->getData('recentItems'));
	}

}
