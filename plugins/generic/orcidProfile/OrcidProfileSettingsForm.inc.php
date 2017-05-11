<?php

/**
 * @file plugins/generic/googleAnalytics/OrcidProfileSettingsForm.inc.php
 *
 * Copyright (c) 2015-2016 University of Pittsburgh
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class OrcidProfileSettingsForm
 * @ingroup plugins_generic_orcidProfile
 *
 * @brief Form for site admins to modify ORCID Profile plugin settings
 */


import('lib.pkp.classes.form.Form');

class OrcidProfileSettingsForm extends Form {

	/** @var $contextId int */
	var $contextId;

	/** @var $plugin object */
	var $plugin;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $contextId int
	 */
	function __construct(&$plugin, $contextId) {
		$this->contextId = $contextId;
		$this->plugin =& $plugin;

		parent::__construct($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(new FormValidator($this, 'orcidProfileAPIPath', 'required', 'plugins.generic.orcidProfile.manager.settings.orcidAPIPathRequired'));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$contextId = $this->contextId;
		$plugin =& $this->plugin;

		$this->_data = array(
			'orcidProfileAPIPath' => $plugin->getSetting($contextId, 'orcidProfileAPIPath'),
			'orcidClientId' => $plugin->getSetting($contextId, 'orcidClientId'),
			'orcidClientSecret' => $plugin->getSetting($contextId, 'orcidClientSecret'),
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('orcidProfileAPIPath'));
		$this->readUserVars(array('orcidClientId'));
		$this->readUserVars(array('orcidClientSecret'));
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->plugin->getName());
		return parent::fetch($request);
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;
		$contextId = $this->contextId;

		$plugin->updateSetting($contextId, 'orcidProfileAPIPath', trim($this->getData('orcidProfileAPIPath'), "\"\';"), 'string');
		$plugin->updateSetting($contextId, 'orcidClientId', $this->getData('orcidClientId'), 'string');
		$plugin->updateSetting($contextId, 'orcidClientSecret', $this->getData('orcidClientSecret'), 'string');
	}
}

?>
