<?php

/**
 * @file controllers/grid/form/StaticPageForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class StaticPageForm
 * @ingroup controllers_grid_staticPages
 *
 * Form for press managers to create and modify sidebar blocks
 *
 */

import('lib.pkp.classes.form.Form');

class StaticPageForm extends Form {
	/** @var int Context (press / journal) ID */
	var $contextId;

	/** @var string Static page name */
	var $staticPageId;

	/** @var StaticPagesPlugin Static pages plugin */
	var $plugin;

	/**
	 * Constructor
	 * @param $staticPagesPlugin StaticPagesPlugin The static page plugin
	 * @param $contextId int Context ID
	 * @param $staticPageId int Static page ID (if any)
	 */
	function __construct($staticPagesPlugin, $contextId, $staticPageId = null) {
		parent::__construct($staticPagesPlugin->getTemplatePath() . 'editStaticPageForm.tpl');

		$this->contextId = $contextId;
		$this->staticPageId = $staticPageId;
		$this->plugin = $staticPagesPlugin;

		// Add form checks
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
		$this->addCheck(new FormValidator($this, 'title', 'required', 'plugins.generic.staticPages.nameRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'path', 'required', 'plugins.generic.staticPages.pathRegEx', '/^[a-zA-Z0-9\/._-]+$/'));
		$this->addCheck(new FormValidatorCustom($this, 'path', 'required', 'plugins.generic.staticPages.duplicatePath', create_function('$path,$form,$staticPagesDao', '$page = $staticPagesDao->getByPath($form->contextId, $path); return !$page || $page->getId()==$form->staticPageId;'), array($this, DAORegistry::getDAO('StaticPagesDAO'))));
	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$templateMgr = TemplateManager::getManager();
		if ($this->staticPageId) {
			$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
			$staticPage = $staticPagesDao->getById($this->staticPageId, $this->contextId);
			$this->setData('path', $staticPage->getPath());
			$this->setData('title', $staticPage->getTitle(null)); // Localized
			$this->setData('content', $staticPage->getContent(null)); // Localized
		}

	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('path', 'title', 'content'));
	}

	/**
	 * @see Form::fetch
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign('staticPageId', $this->staticPageId);
		$templateMgr->assign('pluginJavaScriptURL', $this->plugin->getJavaScriptURL($request));

		$context = $request->getContext();
		if ($context) $templateMgr->assign('allowedVariables', array(
			'contactName' => __('plugins.generic.tinymce.variables.principalContactName', array('value' => $context->getSetting('contactName'))),
			'contactEmail' => __('plugins.generic.tinymce.variables.principalContactEmail', array('value' => $context->getSetting('contactEmail'))),
			'supportName' => __('plugins.generic.tinymce.variables.supportContactName', array('value' => $context->getSetting('supportName'))),
			'supportPhone' => __('plugins.generic.tinymce.variables.supportContactPhone', array('value' => $context->getSetting('supportPhone'))),
			'supportEmail' => __('plugins.generic.tinymce.variables.supportContactEmail', array('value' => $context->getSetting('supportEmail'))),
		));

		$context = $request->getContext();
		if ($context) $templateMgr->assign('allowedVariables', array(
			'contactName' => __('plugins.generic.tinymce.variables.principalContactName', array('value' => $context->getSetting('contactName'))),
			'contactEmail' => __('plugins.generic.tinymce.variables.principalContactEmail', array('value' => $context->getSetting('contactEmail'))),
			'supportName' => __('plugins.generic.tinymce.variables.supportContactName', array('value' => $context->getSetting('supportName'))),
			'supportPhone' => __('plugins.generic.tinymce.variables.supportContactPhone', array('value' => $context->getSetting('supportPhone'))),
			'supportEmail' => __('plugins.generic.tinymce.variables.supportContactEmail', array('value' => $context->getSetting('supportEmail'))),
		));

		return parent::fetch($request);
	}

	/**
	 * Save form values into the database
	 */
	function execute() {
		$staticPagesDao = DAORegistry::getDAO('StaticPagesDAO');
		if ($this->staticPageId) {
			// Load and update an existing page
			$staticPage = $staticPagesDao->getById($this->staticPageId, $this->contextId);
		} else {
			// Create a new static page
			$staticPage = $staticPagesDao->newDataObject();
			$staticPage->setContextId($this->contextId);
		}

		$staticPage->setPath($this->getData('path'));
		$staticPage->setTitle($this->getData('title'), null); // Localized
		$staticPage->setContent($this->getData('content'), null); // Localized

		if ($this->staticPageId) {
			$staticPagesDao->updateObject($staticPage);
		} else {
			$staticPagesDao->insertObject($staticPage);
		}
	}
}

?>
