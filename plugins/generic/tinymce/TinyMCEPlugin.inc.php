<?php

/**
 * @file TinyMCEPlugin.inc.php
 *
 * Copyright (c) 2003-2007 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.tinymce
 * @class TinyMCEPlugin
 *
 * TinyMCE WYSIWYG plugin for textareas - to allow cross-browser HTML editing
 *
 * $Id$
 */
 
import('classes.plugins.GenericPlugin');

define('TINYMCE_INSTALL_PATH', 'lib/tinymce');
define('TINYMCE_JS_PATH', TINYMCE_INSTALL_PATH . '/jscripts/tiny_mce');

class TinyMCEPlugin extends GenericPlugin {
	function register($category, $path) {
		if (parent::register($category, $path)) {
			$journal =& Request::getJournal();
			$journalId = $journal?$journal->getJournalId():0;
			$isEnabled = $this->getSetting($journalId, 'enabled');

			$this->addLocaleData();
			if ($this->isMCEInstalled() && $isEnabled) {
				HookRegistry::register('TemplateManager::display',array(&$this, 'callback'));
			}
			return true;
		}
		return false;
	}

	function getEnableFields(&$templateMgr, $page, $op) {
		$fields = array();
		switch ("$page/$op") {
			case 'author/submit':
				switch (array_shift(Request::getRequestedArgs())) {
					case 1: $fields[] = 'commentsToEditor'; break;
					case 2:
						$count = max(1, count($templateMgr->get_template_vars('authors')));
						for ($i=0; $i<$count; $i++) {
							$fields[] = "authors-$i-biography";
						}
						$fields[] = 'abstract';
						$fields[] = 'abstractAlt1';
						$fields[] = 'abstractAlt2';
						break;
				}
				break;
			case 'author/submitSuppFile': $fields[] = 'description'; break;
			case 'editor/createIssue':
			case 'editor/issueData':
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
						$fields[] = 'publisher-note';
						$fields[] = 'sponsorNote';
						$fields[] = 'contributorNote';
						break;
					case 2:
						$fields[] = 'focusScopeDesc';
						$fields[] = 'reviewPolicy';
						$fields[] = 'reviewGuidelines';
						$fields[] = 'privacyStatement';
						$count = max(1, count($templateMgr->get_template_vars('customAboutItems')));
						for ($i=0; $i<$count; $i++) {
							$fields[] = "customAboutItems-$i-content";
						}
						$fields[] = 'lockssLicense';
						break;
					case 3:
						$fields[] = 'authorGuidelines';
						$count = max(1, count($templateMgr->get_template_vars('submissionChecklist')));
						for ($i=0; $i<$count; $i++) {
							$fields[] = "submissionChecklist-$i";
						}
						$fields[] = 'copyrightNotice';
						break;
					case 4:
						$fields[] = 'openAccessPolicy';
						$fields[] = 'pubFreqPolicy';
						$fields[] = 'announcementsIntroduction';
						$fields[] = 'copyeditInstructions';
						$fields[] = 'layoutInstructions';
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
			case 'sectionEditor/viewMetadata':
			case 'editor/viewMetadata':
			case 'sectionEditor/saveMetadata':
			case 'editor/saveMetadata':
				$count = max(1, count($templateMgr->get_template_vars('authors')));
				for ($i=0; $i<$count; $i++) {
					$fields[] = "authors-$i-biography";
				}
				$fields[] = 'abstract';
				$fields[] = 'abstractAlt1';
				$fields[] = 'abstractAlt2';
				break;
			case 'sectionEditor/editSuppFile':
			case 'editor/editSuppFile':
			case 'sectionEditor/saveSuppFile':
			case 'editor/saveSuppFile':
				$fields[] = 'description';
				break;
			case 'manager/subscriptionPolicies':
				$fields[] = 'subscriptionMailingAddress';
				$fields[] = 'subscriptionAdditionalInformation';
				$fields[] = 'delayedOpenAccessPolicy';
				$fields[] = 'authorSelfArchivePolicy';
				break;
			case 'manager/editSubscriptionType':
			case 'manager/createSubscriptionType':
				$fields[] = 'description';
				break;
		}
		HookRegistry::call('TinyMCEPlugin::getEnableFields', array(&$this, &$fields));
		return $fields;
	}

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

	function getName() {
		return 'TinyMCEPlugin';
	}

	function getDisplayName() {
		return Locale::translate('plugins.generic.tinymce.name');
	}

	function getDescription() {
		if ($this->isMCEInstalled()) return Locale::translate('plugins.generic.tinymce.description');
		return Locale::translate('plugins.generic.tinymce.descriptionDisabled', array('tinyMcePath' => TINYMCE_INSTALL_PATH));
	}

	function isMCEInstalled() {
		return file_exists(TINYMCE_JS_PATH . '/tiny_mce.js');
	}

	function getEnabled() {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getJournalId():0;
		return $this->getSetting($journalId, 'enabled');
	}

	function getManagementVerbs() {
		$verbs = array();
		if ($this->isMCEInstalled()) $verbs[] = array(
			($this->getEnabled()?'disable':'enable'),
			Locale::translate($this->getEnabled()?'manager.plugins.disable':'manager.plugins.enable')
		);
		return $verbs;
	}

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
