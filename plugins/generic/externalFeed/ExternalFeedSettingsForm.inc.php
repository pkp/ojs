<?php
/**
 * @file plugins/generic/externalFeed/ExternalFeedSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedSettingsForm
 * @ingroup plugins_generic_externalFeed
 *
 * @brief Form for journal managers to modify External Feed plugin settings
 */

import('lib.pkp.classes.form.Form');

class ExternalFeedSettingsForm extends Form {

	/** @var $journalId int */
	protected $journalId;

	/** @var $plugin object */
	protected $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 */
	public function __construct($plugin, $journalId) {
		$this->journalId = $journalId;
		$this->plugin = $plugin;
	
		parent::__construct($plugin->getTemplatePath() . 'settingsForm.tpl');
	
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data.
	 */
	public function initData() {
		$journalId = $this->journalId;
		$plugin = $this->plugin;

		$this->_data = array(
			'externalFeedStyleSheet' => $plugin->getSetting($journalId, 'externalFeedStyleSheet')
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	public function readInputData() {
		$this->readUserVars(array('externalFeedStyleSheet'));
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	public function fetch($request) {
		$journalId = $this->journalId;
		$plugin = $this->plugin;

		// Ensure upload file settings are reloaded when the form is displayed.
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('journalStyleSheet', $plugin->getSetting($journalId, 'externalFeedStyleSheet'));
		$templateMgr->assign('defaultStyleSheetUrl', $request->getBaseUrl() . '/' . $plugin->getDefaultStyleSheetFile());

		return parent::fetch($request);
	}

	/**
	 * Deletes a custom stylesheet.
	 */
	function deleteStyleSheet() {
		$journalId = $this->journalId;
		$plugin = $this->plugin;
		$settingName = 'externalFeedStyleSheet';
		$setting = $plugin->getSetting($journalId, $settingName);
		import('classes.file.PublicFileManager');
		$fileManager = new PublicFileManager();
		if ($fileManager->removeJournalFile($journalId, $setting['uploadName'])) {
			$plugin->updateSetting($journalId, $settingName, null);
			return new JSONMessage(true);
		} else {
			return new JSONMessage(false);
		}
	}

}
