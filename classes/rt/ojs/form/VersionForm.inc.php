<?php

/**
 * VersionForm.inc.php
 *
 * Copyright (c) 2003-2005 The Public Knowledge Project
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package rt.ojs.form
 *
 * Form to change metadata information for an RT version.
 *
 * $Id$
 */

import('form.Form');

class VersionForm extends Form {

	/** @var int the ID of the version */
	var $versionId;

	/** @var int the ID of the journal */
	var $journalId;

	/** @var Version current version */
	var $version;

	/**
	 * Constructor.
	 */
	function VersionForm($versionId, $journalId) {
		parent::Form('rtadmin/version.tpl');

		$this->journalId = $journalId;

		$rtDao = &DAORegistry::getDAO('RTDAO');
		$this->version = &$rtDao->getVersion($versionId, $journalId);

		if (isset($this->version)) {
			$this->versionId = $versionId;
		}
	}

	/**
	 * Initialize form data from current version.
	 */
	function initData() {
		if (isset($this->version)) {
			$version = &$this->version;
			$this->_data = array(
				'key' => $version->getKey(),
				'title' => $version->getTitle(),
				'locale' => $version->getLocale(),
				'description' => $version->getDescription()
			);
		} else {
			$this->_data = array();
		}
	}

	/**
	 * Display the form.
	 */
	function display() {
		$journal = &Request::getJournal();
		$templateMgr = &TemplateManager::getManager();

		if (isset($this->version)) {
			$templateMgr->assign('version', $this->version);
			$templateMgr->assign('versionId', $this->versionId);
		}

		$templateMgr->assign('helpTopicId', 'journal.managementPages.readingTools.versions');
		parent::display();
	}


	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'key',
				'title',
				'locale',
				'description'
			)
		);
	}

	/**
	 * Save changes to version.
	 * @return int the version ID
	 */
	function execute() {
		$rtDao = &DAORegistry::getDAO('RTDAO');

		$version = $this->version;
		if (!isset($version)) {
			$version = new RTVersion();
		}

		$version->setTitle($this->getData('title'));
		$version->setKey($this->getData('key'));
		$version->setLocale($this->getData('locale'));
		$version->setDescription($this->getData('description'));

		if (isset($this->version)) {
			$rtDao->updateVersion($this->journalId, $version);
		} else {
			$rtDao->insertVersion($this->journalId, &$version);
			$this->versionId = $version->getVersionId();
		}

		return $this->versionId;
	}

}

?>
