<?php

/**
 * @file plugins/generic/dataverse/classes/form/DataverseSelectForm.inc.php
 *
 * Copyright (c) 2013-2014 Simon Fraser University Library
 * Copyright (c) 2003-2014 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class DataverseSelectForm
 * @ingroup plugins_generic_dataverse
 *
 * @brief Plugin settings: select Dataverse to hold studies created from journal submissions
 * 
 */
import('lib.pkp.classes.form.Form');

class DataverseSelectForm extends Form {

	/** @var $_plugin DataversePlugin */
	var $_plugin;

	/** @var $_journalId int */
	var $_journalId;

	/**
	 * Constructor
	 * @param $plugin DataversePlugin
	 * @param $journalId int
	 * @see Form::Form()
	 */
	function DataverseSelectForm(&$plugin, $journalId) {
		$this->_plugin =& $plugin;
		$this->_journalId = $journalId;
		parent::Form($plugin->getTemplatePath() . 'dataverseSelectForm.tpl');
		$this->addCheck(new FormValidator($this, 'dataverse', FORM_VALIDATOR_REQUIRED_VALUE, 'plugins.generic.dataverse.settings.dataverseRequired'));		
		$this->addCheck(new FormValidatorPost($this));		
	}

	/**
	 * @see Form::initData()
	 */
	function initData() {
		// Get service document
		$sd = $this->_plugin->getServiceDocument(
						$this->_plugin->getSetting($this->_journalId, 'sdUri'),
						$this->_plugin->getSetting($this->_journalId, 'username'),
						$this->_plugin->getSetting($this->_journalId, 'password'),		 
						'' // on behalf of
					);
		
		$dataverses = array();
		if (isset($sd)) {
			foreach ($sd->sac_workspaces as $workspace) {
				foreach ($workspace->sac_collections as $collection) {
					$dataverses["$collection->sac_href"] = "$collection->sac_colltitle";
				}
			}
		}
		$this->setData('dataverses', $dataverses);
		
		$dataverseUri = $this->_plugin->getSetting($this->_journalId, 'dvUri');
		if (isset($dataverseUri) and array_key_exists($dataverseUri, $dataverses)) {
			$this->setData('dataverseUri', $dataverseUri);
		}			 
	}

	/**
	 * @see Form::readInputData()
	 */
	function readInputData() {
		$this->readUserVars(array('dataverse'));
	}

	/**
	 * @see Form::execute()
	 */
	function execute() {
		$this->_plugin->updateSetting($this->_journalId, 'dvUri', $this->getData('dataverse'), 'string');
	}
}
