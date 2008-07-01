<?php

/**
 * @file TinyMCEPlugin.inc.php
 *
 * Copyright (c) 2003-2008 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class TinyMCEPlugin
 * @ingroup plugins_generic_tinymce
 *
 * @brief TinyMCE WYSIWYG plugin for textareas - to allow cross-browser HTML editing
 */

// $Id$


import('classes.plugins.GenericPlugin');

define('TINYMCE_INSTALL_PATH', 'lib/tinymce');
define('TINYMCE_JS_PATH', TINYMCE_INSTALL_PATH . '/jscripts/tiny_mce');

class TinyMCEPlugin extends GenericPlugin {
	/**
	 * Register the plugin, if enabled; note that this plugin
	 * runs under both Journal and Site contexts.
	 * @param $category string
	 * @param $path string
	 * @return boolean
	 */
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$this->addLocaleData();
			if ($this->isMCEInstalled() && $this->getEnabled()) {
				HookRegistry::register('TemplateManager::display',array(&$this, 'callback'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Get the name of the settings file to be installed on new journal
	 * creation.
	 * @return string
	 */
	function getNewJournalPluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Get the name of the settings file to be installed site-wide when
	 * OJS is installed.
	 * @return string
	 */
	function getInstallSitePluginSettingsFile() {
		return $this->getPluginPath() . '/settings.xml';
	}

	/**
	 * Given a $page and $op, return a list of field names for which
	 * the plugin should be used.
	 * @param $templateMgr object
	 * @param $page string The requested page
	 * @param $op string The requested operation
	 * @return array
	 */
	function getEnableFields(&$templateMgr, $page, $op) {
		$formLocale = $templateMgr->get_template_vars('formLocale');
		$fields = array();
		switch ("$page/$op") {
			case 'admin/settings':
			case 'admin/saveSettings':
				$fields[] = 'intro';
				$fields[] = 'about';
				break;
			case 'admin/createJournal':
			case 'admin/updateJournal':
			case 'admin/editJournal':
				$fields[] = 'description';
				break;
			case 'author/submit':
			case 'author/saveSubmit':
				switch (array_shift(Request::getRequestedArgs())) {
					case 1: $fields[] = 'commentsToEditor'; break;
					case 2:
						$count = max(1, count($templateMgr->get_template_vars('authors')));
						for ($i=0; $i<$count; $i++) {
							$fields[] = "authors-$i-biography";
							$fields[] = "authors-$i-competingInterests";
						}
						$fields[] = 'abstract';
						break;
				}
				break;
			case 'author/submitSuppFile': $fields[] = 'description'; break;
			case 'editor/createIssue':
			case 'editor/issueData':
			case 'editor/editIssue':
				$fields[] = 'description';
				$fields[] = 'coverPageDescription';
				break;
			case 'manager/createAnnouncement':
			case 'manager/editAnnouncement':
				$fields[] = 'descriptionShort';
				$fields[] = 'description';
				break;
			case 'user/profile':
			case 'user/register':
			case 'manager/createUser':
			case 'manager/updateUser':
				$fields[] = 'mailingAddress';
				$fields[] = 'biography';
				break;
			case 'manager/editSection':
			case 'manager/updateSection':
			case 'manager/createSection':
				$fields[] = 'policy';
				break;
			case 'manager/setup':
			case 'manager/saveSetup':
				switch (array_shift(Request::getRequestedArgs())) {
					case 1:
						$fields[] = 'mailingAddress';
						$fields[] = 'contactMailingAddress';
						$fields[] = 'publisherNote';
						$fields[] = 'sponsorNote';
						$fields[] = 'contributorNote';
						break;
					case 2:
						$fields[] = 'focusScopeDesc';
						$fields[] = 'reviewPolicy';
						$fields[] = 'reviewGuidelines';
						$fields[] = 'privacyStatement';
						$customAboutItems = $templateMgr->get_template_vars('customAboutItems');
						$count = max(1, isset($customAboutItems[$formLocale])?count($customAboutItems[$formLocale]):0);
						for ($i=0; $i<$count; $i++) {
							// 1 extra in case of new field
							$fields[] = "customAboutItems-$i-content";
						}
						$fields[] = 'lockssLicense';
						break;
					case 3:
						$fields[] = 'authorGuidelines';
						$submissionChecklist = $templateMgr->get_template_vars('submissionChecklist');
						$count = max(1, isset($submissionChecklist[$formLocale])?count($submissionChecklist[$formLocale]):0);
						for ($i=0; $i<$count; $i++) {
							$fields[] = "submissionChecklist-$i";
						}
						$fields[] = 'copyrightNotice';
						$fields[] = 'competingInterestGuidelines';
						break;
					case 4:
						$fields[] = 'openAccessPolicy';
						$fields[] = 'pubFreqPolicy';
						$fields[] = 'announcementsIntroduction';
						$fields[] = 'copyeditInstructions';
						$fields[] = 'layoutInstructions';
						$fields[] = 'refLinkInstructions';
						$fields[] = 'proofInstructions';
						break;
					case 5:
						$fields[] = 'journalDescription';
						$fields[] = 'additionalHomeContent';
						$fields[] = 'journalPageHeader';
						$fields[] = 'journalPageFooter';
						$fields[] = 'readerInformation';
						$fields[] = 'librarianInformation';
						$fields[] = 'authorInformation';
				}
			case 'reviewer/submission':
				$fields[] = 'competingInterests';
				break;
			case 'rtadmin/editContext':
			case 'rtadmin/editSearch':
			case 'rtadmin/editVersion':
			case 'rtadmin/createContext':
			case 'rtadmin/createSearch':
			case 'rtadmin/createVersion':
				$fields[] = 'description';
				break;
			case 'editor/createReviewer':
			case 'sectionEditor/createReviewer':
				$fields[] = 'mailingAddress';
				$fields[] = 'biography';
				break;
			case 'editor/submissionNotes':
			case 'sectionEditor/submissionNotes':
				$fields[] = 'note';
				break;
			case 'author/viewMetadata':
			case 'sectionEditor/viewMetadata':
			case 'editor/viewMetadata':
			case 'author/saveMetadata':
			case 'sectionEditor/saveMetadata':
			case 'editor/saveMetadata':
			case 'copyeditor/viewMetadata':
			case 'copyeditor/saveMetadata':
				$count = max(1, count($templateMgr->get_template_vars('authors')));
				for ($i=0; $i<$count; $i++) {
					$fields[] = "authors-$i-biography";
					$fields[] = "authors-$i-competingInterests";
				}
				$fields[] = 'abstract';
				break;
			case 'sectionEditor/editSuppFile':
			case 'editor/editSuppFile':
			case 'sectionEditor/saveSuppFile':
			case 'editor/saveSuppFile':
				$fields[] = 'description';
				break;
			case 'manager/subscriptionPolicies':
			case 'manager/saveSubscriptionPolicies':
				$fields[] = 'subscriptionMailingAddress';
				$fields[] = 'subscriptionAdditionalInformation';
				$fields[] = 'delayedOpenAccessPolicy';
				$fields[] = 'authorSelfArchivePolicy';
				break;
			case 'manager/editSubscriptionType':
			case 'manager/createSubscriptionType':
			case 'manager/updateSubscriptionType':
				$fields[] = 'description';
				break;
		}
		HookRegistry::call('TinyMCEPlugin::getEnableFields', array(&$this, &$fields));
		return $fields;
	}

	/**
	 * Hook callback function for TemplateManager::display
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function callback($hookName, $args) {
		$templateManager =& $args[0];

		$page = Request::getRequestedPage();
		$op = Request::getRequestedOp();
		$enableFields = $this->getEnableFields($templateManager, $page, $op);

		if (!empty($enableFields)) {
			$baseUrl = $templateManager->get_template_vars('baseUrl');
			$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');
			$enableFields = join(',', $enableFields);
			$allLocales = Locale::getAllLocales();
			$localeList = array();
			foreach ($allLocales as $key => $locale) {
				$localeList[] = String::substr($key, 0, 2);
			}

			$tinyMCE_scipt = '
			<script language="javascript" type="text/javascript" src="'.$baseUrl.'/'.TINYMCE_JS_PATH.'/tiny_mce_gzip.js"></script>
			<script language="javascript" type="text/javascript">
				tinyMCE_GZ.init({
					relative_urls : "false",
					plugins : "paste",
					themes : "advanced",
					languages : "' . join(',', $localeList) . '",
					disk_cache : true
				});
			</script>
			<script language="javascript" type="text/javascript">
				tinyMCE.init({
					plugins : "paste",
					mode : "exact",
					language : "' . String::substr(Locale::getLocale(), 0, 2) . '",
					elements : "' . $enableFields . '",
					relative_urls : false,
					forced_root_block : false,
					apply_source_formatting : false,
					theme : "advanced",
					theme_advanced_buttons1 : "pasteword,bold,italic,underline,bullist,numlist,link,unlink,help,code",
					theme_advanced_buttons2 : "",
					theme_advanced_buttons3 : ""
				});
			</script>';

			$templateManager->assign('additionalHeadData', $additionalHeadData."\n".$tinyMCE_scipt);
		}
		return false;
	}

	/**
	 * Get the symbolic name of this plugin
	 * @return string
	 */
	function getName() {
		return 'TinyMCEPlugin';
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return Locale::translate('plugins.generic.tinymce.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		if ($this->isMCEInstalled()) return Locale::translate('plugins.generic.tinymce.description');
		return Locale::translate('plugins.generic.tinymce.descriptionDisabled', array('tinyMcePath' => TINYMCE_INSTALL_PATH));
	}

	/**
	 * Check whether or not the TinyMCE library is installed
	 * @return boolean
	 */
	function isMCEInstalled() {
		return file_exists(TINYMCE_JS_PATH . '/tiny_mce.js');
	}

	/**
	 * Check whether or not this plugin is enabled
	 * @return boolean
	 */
	function getEnabled() {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getJournalId():0;
		return $this->getSetting($journalId, 'enabled');
	}

	/**
	 * Get a list of available management verbs for this plugin
	 * @return array
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->isMCEInstalled()) $verbs[] = array(
			($this->getEnabled()?'disable':'enable'),
			Locale::translate($this->getEnabled()?'manager.plugins.disable':'manager.plugins.enable')
		);
		return $verbs;
	}

	/**
	 * Execute a management verb on this plugin
	 * @param $verb string
	 * @param $args array
	 * @return boolean
	 */
	function manage($verb, $args) {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getJournalId():0;
		switch ($verb) {
			case 'enable':
				$this->updateSetting($journalId, 'enabled', true);
				break;
			case 'disable':
				$this->updateSetting($journalId, 'enabled', false);
				break;
		}
		return false;
	}
}
?>
