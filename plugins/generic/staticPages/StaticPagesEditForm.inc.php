<?php

/**
 * @file StaticPagesSettingsForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.staticPages
 * @class StaticPagesSettingsForm
 *
 * Form for journal managers to view and modify static pages
 *
 */

import('lib.pkp.classes.form.Form');

class StaticPagesEditForm extends Form {
	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;

	/** @var $staticPageId **/
	var $staticPageId;

	/** $var $errors string */
	var $errors;

	/**
	 * Constructor
	 * @param $journalId int
	 */
	function StaticPagesEditForm(&$plugin, $journalId, $staticPageId = null) {

		parent::Form($plugin->getTemplatePath() . 'editStaticPageForm.tpl');

		$this->journalId = $journalId;
		$this->plugin =& $plugin;
		$this->staticPageId = isset($staticPageId)? (int) $staticPageId: null;

		$this->addCheck(new FormValidatorCustom($this, 'pagePath', 'required', 'plugins.generic.staticPages.duplicatePath', array(&$this, 'checkForDuplicatePath'), array($journalId, $staticPageId)));
		$this->addCheck(new FormValidatorPost($this));

	}

	/**
	 * Custom Form Validator for PATH to ensure no duplicate PATHs are created
	 * @param $pagePath String the PATH being checked
	 * @param $journalId int
	 * @param $staticPageId int
	 */
	function checkForDuplicatePath($pagePath, $journalId, $staticPageId) {
		$staticPageDao =& DAORegistry::getDAO('StaticPagesDAO');

		return !$staticPageDao->duplicatePathExists($pagePath, $journalId, $staticPageId);
	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		// add the tiny MCE script
		$this->addTinyMCE();

		if (isset($this->staticPageId)) {
			$staticPageDao =& DAORegistry::getDAO('StaticPagesDAO');
			$staticPage =& $staticPageDao->getStaticPage($this->staticPageId);

			if ($staticPage != null) {
				$this->_data = array(
					'staticPageId' => $staticPage->getId(),
					'pagePath' => $staticPage->getPath(),
					'title' => $staticPage->getTitle(null),
					'content' => $staticPage->getContent(null)
				);
			} else {
				$this->staticPageId = null;
			}
		}
	}

	function addTinyMCE() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;
		$templateMgr =& TemplateManager::getManager();

		// Enable TinyMCE with specific params
		$additionalHeadData = $templateMgr->get_template_vars('additionalHeadData');

		import('classes.file.JournalFileManager');
		$publicFileManager = new PublicFileManager();
		$tinyMCE_script = '
		<script language="javascript" type="text/javascript" src="'.Request::getBaseUrl().'/'.TINYMCE_JS_PATH.'/tiny_mce.js"></script>
		<script language="javascript" type="text/javascript">
			tinyMCE.init({
			mode : "textareas",
			plugins : "safari,spellchecker,style,layer,table,save,advhr,jbimages,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,pagebreak,jbimages",
			theme_advanced_buttons1_add : "fontsizeselect",
			theme_advanced_buttons2_add : "separator,preview,separator,forecolor,backcolor",
			theme_advanced_buttons2_add_before: "search,replace,separator",
			theme_advanced_buttons3_add_before : "tablecontrols,separator",
			theme_advanced_buttons3_add : "media,separator",
			theme_advanced_buttons4 : "cut,copy,paste,pastetext,pasteword,separator,styleprops,|,spellchecker,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,blockquote,pagebreak,print,separator",
			theme_advanced_disable: "styleselect",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			relative_urls : false,
			document_base_url : "'. Request::getBaseUrl() .'/'.$publicFileManager->getJournalFilesPath($journalId) .'/",
			theme : "advanced",
			theme_advanced_layout_manager : "SimpleLayout",
			extended_valid_elements : "span[*], div[*]",
			spellchecker_languages : "+English=en,Danish=da,Dutch=nl,Finnish=fi,French=fr,German=de,Italian=it,Polish=pl,Portuguese=pt,Spanish=es,Swedish=sv"
			});
		</script>';

		$templateMgr->assign('additionalHeadData', $additionalHeadData."\n".$tinyMCE_script);

	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('staticPageId', 'pagePath', 'title', 'content'));
	}

	/**
	 * Get the names of localized fields
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('title', 'content');
	}

	/**
	 * Save page into DB
	 */
	function save() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;

		$plugin->import('StaticPage');
		$staticPagesDao =& DAORegistry::getDAO('StaticPagesDAO');
		if (isset($this->staticPageId)) {
			$staticPage =& $staticPagesDao->getStaticPage($this->staticPageId);
		}

		if (!isset($staticPage)) {
			$staticPage = new StaticPage();
		}

		$staticPage->setJournalId($journalId);
		$staticPage->setPath($this->getData('pagePath'));

		$staticPage->setTitle($this->getData('title'), null);		// Localized
		$staticPage->setContent($this->getData('content'), null);	// Localized

		if (isset($this->staticPageId)) {
			$staticPagesDao->updateStaticPage($staticPage);
		} else {
			$staticPagesDao->insertStaticPage($staticPage);
		}
	}

	function display() {
		$templateMgr =& TemplateManager::getManager();

		parent::display();
	}

}
?>
