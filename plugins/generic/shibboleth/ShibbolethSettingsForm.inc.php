<?php

/**
 * @file plugins/generic/shibboleth/ShibbolethSettingsForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ShibbolethSettingsForm
 * @ingroup plugins_generic_shibboleth
 *
 * @brief Form for journal managers to modify Shibboleth
 * authentication plugin settings
 */

import('lib.pkp.classes.form.Form');

class ShibbolethSettingsForm extends Form {

	/** @var int */
	var $_contextId;

	/** @var object */
	var $_plugin;

	/**
	 * Constructor
	 * @param $plugin ShibbolethAuthPlugin
	 * @param $contextId int
	 */
	function __construct($plugin, $contextId) {
		$this->_contextId = $contextId;
		$this->_plugin = $plugin;

		parent::__construct($plugin->getTemplatePath() . 'settingsForm.tpl');

		$this->addCheck(
			new FormValidator(
				$this,
				'shibbolethWayfUrl',
				'required',
				'plugins.generic.shibboleth.manager.settings.shibbolethWayfUrlRequired'
			)
		);
		$this->addCheck(
			new FormValidator(
				$this,
				'shibbolethHeaderUin',
				'required',
				'plugins.generic.shibboleth.manager.settings.shibbolethHeaderUinRequired'
			)
		);
		$this->addCheck(
			new FormValidator(
				$this,
				'shibbolethHeaderFirstName',
				'required',
				'plugins.generic.shibboleth.manager.settings.shibbolethHeaderFirstNameRequired'
			)
		);
		$this->addCheck(
			new FormValidator(
				$this,
				'shibbolethHeaderLastName',
				'required',
				'plugins.generic.shibboleth.manager.settings.shibbolethHeaderLastNameRequired'
			)
		);
		$this->addCheck(
			new FormValidator(
				$this,
				'shibbolethHeaderEmail',
				'required',
				'plugins.generic.shibboleth.manager.settings.shibbolethHeaderEmailRequired'
			)
		);
		$this->addCheck(
			new FormValidator(
				$this,
				'shibbolethAdminUins',
				'required',
				'plugins.generic.shibboleth.manager.settings.shibbolethAdminUinsRequired'
			)
		);
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		$this->_data = array(
			'shibbolethWayfUrl' => $this->_plugin->getSetting($this->_contextId, 'shibbolethWayfUrl'),
			'shibbolethHeaderUin' => $this->_plugin->getSetting($this->_contextId, 'shibbolethHeaderUin'),
			'shibbolethHeaderFirstName' => $this->_plugin->getSetting($this->_contextId, 'shibbolethHeaderFirstName'),
			'shibbolethHeaderLastName' => $this->_plugin->getSetting($this->_contextId, 'shibbolethHeaderLastName'),
			'shibbolethHeaderInitials' => $this->_plugin->getSetting($this->_contextId, 'shibbolethHeaderInitials'),
			'shibbolethHeaderEmail' => $this->_plugin->getSetting($this->_contextId, 'shibbolethHeaderEmail'),
			'shibbolethHeaderPhone' => $this->_plugin->getSetting($this->_contextId, 'shibbolethHeaderPhone'),
			'shibbolethHeaderMailing' => $this->_plugin->getSetting($this->_contextId, 'shibbolethHeaderMailing'),
			'shibbolethAdminUins' => $this->_plugin->getSetting($this->_contextId, 'shibbolethAdminUins'),
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('shibbolethWayfUrl'));
		$this->readUserVars(array('shibbolethHeaderUin'));
		$this->readUserVars(array('shibbolethHeaderFirstName'));
		$this->readUserVars(array('shibbolethHeaderLastName'));
		$this->readUserVars(array('shibbolethHeaderInitials'));
		$this->readUserVars(array('shibbolethHeaderEmail'));
		$this->readUserVars(array('shibbolethHeaderPhone'));
		$this->readUserVars(array('shibbolethHeaderMailing'));
		$this->readUserVars(array('shibbolethAdminUins'));
	}

	/**
	 * Fetch the form.
	 * @copydoc Form::fetch()
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('pluginName', $this->_plugin->getName());
		return parent::fetch($request);
	}

	/**
	 * Save settings.
	 */
	function execute() {
		$this->_plugin->updateSetting(
			$this->_contextId,
			'shibbolethWayfUrl',
			trim($this->getData('shibbolethWayfUrl'), "\"\';"),
			'string'
		);
		$this->_plugin->updateSetting(
			$this->_contextId,
			'shibbolethHeaderUin',
			trim($this->getData('shibbolethHeaderUin'), "\"\';"),
			'string'
		);
		$this->_plugin->updateSetting(
			$this->_contextId,
			'shibbolethHeaderFirstName',
			trim($this->getData('shibbolethHeaderFirstName'), "\"\';"),
			'string'
		);
		$this->_plugin->updateSetting(
			$this->_contextId,
			'shibbolethHeaderLastName',
			trim($this->getData('shibbolethHeaderLastName'), "\"\';"),
			'string'
		);
		$this->_plugin->updateSetting(
			$this->_contextId,
			'shibbolethHeaderInitials',
			trim($this->getData('shibbolethHeaderInitials'), "\"\';"),
			'string'
		);
		$this->_plugin->updateSetting(
			$this->_contextId,
			'shibbolethHeaderEmail',
			trim($this->getData('shibbolethHeaderEmail'), "\"\';"),
			'string'
		);
		$this->_plugin->updateSetting(
			$this->_contextId,
			'shibbolethHeaderPhone',
			trim($this->getData('shibbolethHeaderPhone'), "\"\';"),
			'string'
		);
		$this->_plugin->updateSetting(
			$this->_contextId,
			'shibbolethHeaderMailing',
			trim($this->getData('shibbolethHeaderMailing'), "\"\';"),
			'string'
		);
		$this->_plugin->updateSetting(
			$this->_contextId,
			'shibbolethAdminUins',
			trim($this->getData('shibbolethAdminUins'), "\"\';"),
			'string'
		);
	}
}

?>
