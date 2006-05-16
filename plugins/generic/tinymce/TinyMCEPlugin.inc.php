<?php

/**
 * TinyMCEPlugin.inc.php
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

	function getDisableTemplates() {
		$disableTemplates = array(
			'manager/emails/emailTemplateForm.tpl',
			'email/email.tpl',
			'rt/email.tpl',
			'submission/comment/commentEmail.tpl',
			'submission/comment/editorDecisionEmail.tpl'
		);
		HookRegistry::call('TinyMCEPlugin::getDisableTemplates', array(&$this, &$disableTemplates));
		return $disableTemplates;
	}

	function callback($hookName, $args) {
		$templateManager =& $args[0];
		$template =& $args[1];

		if (!in_array($template, $this->getDisableTemplates())) {
			$baseUrl = $templateManager->get_template_vars('baseUrl');
			$additionalHeadData = $templateManager->get_template_vars('additionalHeadData');

			$tinyMCE_scipt = '
			<script language="javascript" type="text/javascript" src="'.$baseUrl.'/'.TINYMCE_JS_PATH.'/tiny_mce.js"></script>
			<script language="javascript" type="text/javascript">
				tinyMCE.init({
				mode : "textareas",
				theme : "advanced",
				theme_advanced_buttons1 : "bold,italic,underline,bullist,numlist,link,unlink,help,code",
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

	function getManagementVerbs() {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getJournalId():0;
		$isEnabled = $this->getSetting($journalId, 'enabled');

		$verbs = array();
		if ($this->isMCEInstalled()) $verbs[] = array(
			($isEnabled?'disable':'enable'),
			Locale::translate($isEnabled?'manager.plugins.disable':'manager.plugins.enable')
		);
		return $verbs;
	}

	function manage($verb, $args) {
		$journal =& Request::getJournal();
		$journalId = $journal?$journal->getJournalId():0;
		$isEnabled = $this->getSetting($journalId, 'enabled');
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
