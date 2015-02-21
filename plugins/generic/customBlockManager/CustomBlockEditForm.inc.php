<?php

/**
 * @file plugins/generic/customBlockManager/CustomBlockEditForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins.generic.customBlockManager
 * @class CustomBlockEditForm
 *
 * Form for journal managers to create and modify sidebar blocks
 * 
 */

import('lib.pkp.classes.form.Form');

class CustomBlockEditForm extends Form {
	/** @var $journalId int */
	var $journalId;

	/** @var $plugin object */
	var $plugin;
	
	/** $var $errors string */
	var $errors;

	/**
	 * Constructor
	 * @param $journalId int
	 */
	function CustomBlockEditForm(&$plugin, $journalId) {

		parent::Form($plugin->getTemplatePath() . 'editCustomBlockForm.tpl');

		$this->journalId = $journalId;
		$this->plugin =& $plugin;

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidator($this, 'blockContent', 'required', 'plugins.generic.customBlock.contentRequired'));

	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$journalId = $this->journalId;
		$plugin =& $this->plugin;

		// add the tiny MCE script 
		$this->addTinyMCE();
		$this->setData('blockContent', $plugin->getSetting($journalId, 'blockContent'));
	}

	/**
	 * Add the tinyMCE script for editing sidebar blocks with a WYSIWYG editor
	 */
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
			plugins : "style,paste,jbimages",
			theme : "advanced",
			theme_advanced_buttons1 : "formatselect,fontselect,fontsizeselect",
			theme_advanced_buttons2 : "bold,italic,underline,separator,strikethrough,justifyleft,justifycenter,justifyright, justifyfull,bullist,numlist,undo,redo,link,unlink",
			theme_advanced_buttons3 : "cut,copy,paste,pastetext,pasteword,|,cleanup,help,code,jbimages",
			theme_advanced_toolbar_location : "bottom",
			theme_advanced_toolbar_align : "left",
			content_css : "' . Request::getBaseUrl() . '/styles/common.css", 
			relative_urls : false,
			document_base_url : "'. Request::getBaseUrl() .'/'.$publicFileManager->getJournalFilesPath($journalId) .'/", 
			extended_valid_elements : "span[*], div[*]"
			});
		</script>';

		$templateMgr->assign('additionalHeadData', $additionalHeadData."\n".$tinyMCE_script);

	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('blockContent'));
	}
	
	/**
	 * Get the names of localized fields
	 * @return array
	 */
	function getLocaleFieldNames() {
		return array('blockContent');
	}

	/**
	 * Save page into DB
	 */	 
	function save() {
		$plugin =& $this->plugin;
		$journalId = $this->journalId;
		$plugin->updateSetting($journalId, 'blockContent', $this->getData('blockContent'));		
	}

}
?>
