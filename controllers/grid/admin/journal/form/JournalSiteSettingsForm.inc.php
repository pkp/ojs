<?php

/**
 * @file controllers/grid/admin/journal/form/JournalSiteSettingsForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class JournalSiteSettingsForm
 * @ingroup controllers_grid_admin_journal_form
 *
 * @brief Form for site administrator to edit basic journal settings.
 */

import('lib.pkp.controllers.grid.admin.context.form.ContextSiteSettingsForm');

class JournalSiteSettingsForm extends ContextSiteSettingsForm {
	/**
	 * Constructor.
	 * @param $contextId omit for a new journal
	 */
	function __construct($contextId = null) {
		parent::__construct($contextId);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		parent::readInputData();

		if ($this->contextId) {
			$journalDao = DAORegistry::getDAO('JournalDAO');
			$journal = $journalDao->getById($this->contextId);
			if ($journal) $this->setData('oldPath', $journal->getPath());
		}
	}

	/**
	 * Save journal settings.
	 */
	function execute() {
		$request = Application::getRequest();
		$site = $request->getSite();
		$journalDao = DAORegistry::getDAO('JournalDAO');

		if (isset($this->contextId)) {
			$journal = $journalDao->getById($this->contextId);
		}

		if (!isset($journal)) {
			$journal = $journalDao->newDataObject();
		}

		// Check if the journal path has changed.
		$pathChanged = false;
		$journalPath = $journal->getPath();
		if ($journalPath != $this->getData('path')) {
			$pathChanged = true;
		}
		$journal->setPath($this->getData('path'));
		$journal->setEnabled($this->getData('enabled'));

		if ($journal->getId() != null) {
			$isNewJournal = false;
			$journalDao->updateObject($journal);
			$section = null;
		} else {
			$isNewJournal = true;

			// Give it a default primary locale
			$journal->setPrimaryLocale ($site->getPrimaryLocale());

			$journalId = $journalDao->insertObject($journal);
			$journalDao->resequence();

			$installedLocales = $site->getInstalledLocales();

			// Install default genres
			$genreDao = DAORegistry::getDAO('GenreDAO');
			$genreDao->installDefaults($journalId, $installedLocales); /* @var $genreDao GenreDAO */

			// load the default user groups and stage assignments.
			$this->_loadDefaultUserGroups($journalId);

			// Put this user in the Manager group.
			$this->_assignManagerGroup($journalId);

			// Make the file directories for the journal
			import('lib.pkp.classes.file.FileManager');
			$fileManager = new FileManager();
			$fileManager->mkdir(Config::getVar('files', 'files_dir') . '/journals/' . $journalId);
			$fileManager->mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/articles');
			$fileManager->mkdir(Config::getVar('files', 'files_dir'). '/journals/' . $journalId . '/issues');
			$fileManager->mkdir(Config::getVar('files', 'public_files_dir') . '/journals/' . $journalId);

			// Install default journal settings
			$journalSettingsDao = DAORegistry::getDAO('JournalSettingsDAO');
			$names = $this->getData('name');
			AppLocale::requireComponents(LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_APP_COMMON);
			$journalSettingsDao->installSettings($journalId, 'registry/journalSettings.xml', array(
				'indexUrl' => $request->getIndexUrl(),
				'journalPath' => $this->getData('path'),
				'primaryLocale' => $site->getPrimaryLocale(),
				'contextName' => $names[$site->getPrimaryLocale()],
				'ldelim' => '{', // Used to add variables to settings without translating now
				'rdelim' => '}',
			));

			// Create a default "Articles" section
			$sectionDao = DAORegistry::getDAO('SectionDAO');
			$section = new Section();
			$section->setJournalId($journal->getId());
			$section->setTitle(__('section.default.title'), $journal->getPrimaryLocale());
			$section->setAbbrev(__('section.default.abbrev'), $journal->getPrimaryLocale());
			$section->setMetaIndexed(true);
			$section->setMetaReviewed(true);
			$section->setPolicy(__('section.default.policy'), $journal->getPrimaryLocale());
			$section->setEditorRestricted(false);
			$section->setHideTitle(false);
			$sectionDao->insertObject($section);

			$journal->updateSetting('supportedLocales', $site->getSupportedLocales());

			// load default navigationMenus.
			$this->_loadDefaultNavigationMenus($journalId);

		}
		$journal->updateSetting('name', $this->getData('name'), 'string', true);
		$journal->updateSetting('description', $this->getData('description'), 'string', true);

		// Make sure all plugins are loaded for settings preload
		PluginRegistry::loadAllPlugins();

		HookRegistry::call('JournalSiteSettingsForm::execute', array($this, $journal, $section, $isNewJournal));

		if ($isNewJournal || $pathChanged) {
			return $journal->getPath();
		}
	}
}


