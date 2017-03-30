<?php

/**
 * @file controllers/grid/form/NavigationMenusForm.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class NavigationMenusForm
 * @ingroup controllers_grid_navigationMenus
 *
 * Form for press managers to create and modify NavigationMenus
 *
 */

import('lib.pkp.classes.form.Form');

class NavigationMenusForm extends Form {
	/** @var int Context (press / journal) ID */
	var $contextId;

	/** @var int NavigationMenu Id */
	var $navigationMenuId;

	/** @var NavigationMenusPlugin NavigationMenus plugin */
	var $plugin;

	/**
	 * Constructor
	 * @param $navigationMenusPlugin NavigationMenusPlugin The NavigationMenus plugin
	 * @param $contextId int Context ID
	 * @param $navigationMenuId int NavigationMenu ID (if any)
	 */
	function __construct($navigationMenusPlugin, $contextId, $navigationMenuId = null) {
		parent::__construct($navigationMenusPlugin->getTemplatePath() . 'editNavigationMenusForm.tpl');

		$this->contextId = $contextId;
		$this->NavigationMenuId = $navigationMenuId;
		$this->plugin = $navigationMenusPlugin;

		// Add form checks
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
		$this->addCheck(new FormValidator($this, 'title', 'required', 'plugins.generic.navigationMenus.nameRequired'));
		$this->addCheck(new FormValidatorRegExp($this, 'path', 'required', 'plugins.generic.navigationMenus.pathRegEx', '/^[a-zA-Z0-9\/._-]+$/'));
		$this->addCheck(new FormValidatorCustom($this, 'path', 'required', 'plugins.generic.navigationMenus.duplicatePath', create_function('$path,$form,$navigationMenusDao', '$page = $navigationMenusDao->getByPath($form->contextId, $path); return !$page || $page->getId()==$form->navigationMenuId;'), array($this, DAORegistry::getDAO('NavigationMenusDAO'))));
	}

	/**
	 * Initialize form data from current group group.
	 */
	function initData() {
		$templateMgr = TemplateManager::getManager();
		if ($this->navigationMenuId) {
			$navigationMenusDao = DAORegistry::getDAO('NavigationMenusDAO');
			$navigationMenu = $navigationMenusDao->getById($this->navigationMenuId, $this->contextId);
			$this->setData('path', $navigationMenu->getPath());
			$this->setData('title', $navigationMenu->getTitle(null)); // Localized
			$this->setData('content', $avigationMenu->getContent(null)); // Localized
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
		$templateMgr->assign('navigationMenuId', $this->navigationMenuId);
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
		$navigationMenusDao = DAORegistry::getDAO('NavigationMenusDAO');
		if ($this->navigationMenuId) {
			// Load and update an existing NavigationMenu
			$navigationMenu = $navigationMenusDao->getById($this->navigationMenusId, $this->contextId);
		} else {
			// Create a new NavigationMenu
			$navigationMenu = $navigationMenusDao->newDataObject();
			$navigationMenu->setContextId($this->contextId);
		}

		$navigationMenu->setPath($this->getData('path'));
		$navigationMenu->setTitle($this->getData('title'), null); // Localized
		$navigationMenu->setContent($this->getData('content'), null); // Localized

		if ($this->navigationMenuId) {
			$navigationMenusDao->updateObject($navigationMenu);
		} else {
			$navigationMenusDao->insertObject($navigationMenu);
		}
	}
}

?>
