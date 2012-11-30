<?php

/**
 * @file classes/manager/form/JournalSiteSettingsForm.inc.php
 *
 * Copyright (c) 2003-2012 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSiteSettingsForm
 * @ingroup admin_form
 *
 * @brief Form for site administrator to edit basic journal settings.
 */

import('lib.pkp.classes.db.DBDataXMLParser');
import('lib.pkp.classes.form.Form');

class JournalSiteSettingsForm extends Form {

	/** The ID of the journal being edited */
	var $journalId;

	/**
	 * Save journal settings.
	 */
	function execute() {
		$journalDao =& DAORegistry::getDAO('JournalDAO');

		if (isset($this->journalId)) {
			$journal =& $journalDao->getById($this->journalId);
		}

		if (!isset($journal)) {
			$journal = $journalDao->newDataObject();
		}

		$journal->setPath($this->getData('journalPath'));
		$journal->setEnabled($this->getData('enabled'));

		if ($journal->getId() != null) {
			$isNewJournal = false;
			$journalDao->updateObject($journal);
			$section = null;
		} else {
			$isNewJournal = true;
			$site =& Request::getSite();

			// Give it a default primary locale
			$journal->setPrimaryLocale ($site->getPrimaryLocale());

			$journalId = $journalDao->insertObject($journal);
			$journalDao->resequence();

			// Make the site administrator the journal manager of newly created journals
			$sessionManager =& SessionManager::getManager();
			$userSession =& $sessionManager->getUserSession();
			if ($userSession->getUserId() != null && $userSession->getUserId() != 0 && !empty($journalId)) {
				$role = new Role();
				$role->setJournalId($journalId);
				$role->setUserId($userSession->getUserId());
				$role->setRoleId(ROLE_ID_JOURNAL_MANAGER);

				$roleDao =& DAORegistry::getDAO('RoleDAO');
				$roleDao->insertRole($role);
			}

			// Make the file directories for the journal
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			$fileManager->mkdir(Config::getVar('files', 'files_dir') . '/journals/' . $journalId);
			$fileManager->mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/articles');
			$fileManager->mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/issues');
			$fileManager->mkdir(Config::getVar('files', 'public_files_dir') . '/journals/' . $journalId);

			// Install default journal settings
			$journalSettingsDao =& DAORegistry::getDAO('JournalSettingsDAO');
			$names = $this->getData('name');
			AppLocale::requireComponents(LOCALE_COMPONENT_OJS_DEFAULT, LOCALE_COMPONENT_APPLICATION_COMMON);
			$journalSettingsDao->installSettings($journalId, 'registry/journalSettings.xml', array(
				'indexUrl' => Request::getIndexUrl(),
				'journalPath' => $this->getData('journalPath'),
				'primaryLocale' => $site->getPrimaryLocale(),
				'journalName' => $names[$site->getPrimaryLocale()]
			));

			// Install the default RT versions.
			import('classes.rt.ojs.JournalRTAdmin');
			$journalRtAdmin = new JournalRTAdmin($journalId);
			$journalRtAdmin->restoreVersions(false);

			// Create a default "Articles" section
			$sectionDao =& DAORegistry::getDAO('SectionDAO');
			$section = new Section();
			$section->setJournalId($journal->getId());
			$section->setTitle(__('section.default.title'), $journal->getPrimaryLocale());
			$section->setAbbrev(__('section.default.abbrev'), $journal->getPrimaryLocale());
			$section->setMetaIndexed(true);
			$section->setMetaReviewed(true);
			$section->setPolicy(__('section.default.policy'), $journal->getPrimaryLocale());
			$section->setEditorRestricted(false);
			$section->setHideTitle(false);
			$sectionDao->insertSection($section);
		}
		$journal->updateSetting('name', $this->getData('name'), 'string', true);
		$journal->updateSetting('description', $this->getData('description'), 'string', true);

		// Make sure all plugins are loaded for settings preload
		PluginRegistry::loadAllPlugins();

		HookRegistry::call('JournalSiteSettingsForm::execute', array(&$this, &$journal, &$section, &$isNewJournal));
	}

}

?>
