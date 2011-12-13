<?php

/**
 * @file classes/plugins/PidPlugin.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PidPlugin
 * @ingroup plugins
 *
 * @brief Abstract class for public identifiers plugins
 */


import('classes.plugins.Plugin');

class PubIdPlugin extends Plugin {
	function PubIdPlugin() {
		parent::Plugin();
	}

	/**
	 * Called as a plugin is registered to the registry.
	 * @param $category String Name of category plugin was registered to
	 * @return boolean True if plugin initialized successfully; if false,
	 * 	the plugin will not be registered.
	 */
	function register($category, $path) {
		$success = parent::register($category, $path);
		if ($success) {
			// Enable storage of the additional fields
			foreach($this->getDAOs() as $daoName) {
				HookRegistry::register(strtolower($daoName).'::getAdditionalFieldNames', array($this, 'getAdditionalFieldNames'));
			}
		}
		return $success;
	}

	/**
	 * Get the name of this plugin.
	 * The name must be unique within its category.
	 * @return String name of plugin
	 */
	function getName() {
		assert(false); // Should always be overridden
	}

	/**
	 * Get the display name for this plugin.
	 * @return String
	 */
	function getDisplayName() {
		assert(false); // Should always be overridden
	}

	/**
	 * Get a description of this plugin.
	 */
	function getDescription() {
		assert(false); // Should always be overridden
	}


	/*
	 * Get and Set
	 */

	/**
	 * Get the public identifier.
	 * @param $pubObject object
	 *  (Issue, Article, PublishedArticle, ArticleGalley, SuppFile)
	 * @param $preview boolean
	 *  when true, the public identifier will not be stored
	 */
	function getPubId($pubObject, $preview = false) {
		assert(false); // Should always be overridden
	}

	/**
	 * Set and store a public identifier.
	 * @param $pubObject object
	 *  (Issue, Article, PublishedArticle, ArticleGalley, SuppFile)
	 * @param $pubId string
	 */
	function setStoredPubId($pubObject, $pubId) {
		assert(false); // Should always be overridden
	}

	/**
	 * Public identifier type, that is used in the database.
	 * S. http://dtd.nlm.nih.gov/publishing/tag-library/n-4zh0.html
	 */
	function getPubIdType() {
		assert(false); // Should always be overridden
	}

	/**
	 * Public identifier type that will be displayed to the reader.
	 */
	function getPubIdDisplayType() {
		assert(false); // Should always be overridden
	}

	/**
	 * Full name of the public identifier.
	 */
	function getPubIdFullName() {
		assert(false); // Should always be overridden
	}

	/**
	 * Get the whole resolving URL.
	 * @param $pubId string
	 * @return string resolving URL
	 */
	function getResolvingURL($pubId) {
		assert(false); // Should always be overridden
	}

	/**
	 * Get the file (path + filename)
	 * that is included in the objects metadata pages.
	 */
	function getPubIdMetadataFile() {
		assert(false); // Should be overridden
	}

	/**
	 * Verify posted data.
	 * @param $data string
	 * @param $pubObject object
	 * @param $journalId integer
	 * @param $errorMsg string
	 * @return boolean
	 */
	function verifyData($data, &$pubObject, $journalId, &$errorMsg) {
		assert(false); // Should be overridden
	}

	/**
	 * Check for duplicate URN.
	 * @param $pubId string
	 * @param $pubObject object
	 * @param $journalId integer
	 * @return boolean
	 */
	function checkDuplicate($pubId, &$pubObject, $journalId) {
		assert(false); // Should be overridden
	}

	/**
	 * Get the additional form field names.
	 */
	function getFormFieldNames() {
		assert(false); // Should be overridden
	}

	/**
	 * Get additional field names to be considered for storage.
	 */
	function getDAOFieldNames() {
		assert(false); // Should be overridden
	}

	/**
	 * Return the name of the corresponding DAO.
	 * @param $pubObject object
	 * @return string
	 */
	function getDAO($pubObjectType) {
		$daos =  array(
			'Issue' => 'IssueDAO',
			'Article' => 'ArticleDAO',
			'Galley' => 'ArticleGalleyDAO',
			'SuppFile' => 'SuppFileDAO'
		);
		return $daos[$pubObjectType];
	}

	/**
	 * Return the object type.
	 * @param $pubObject object
	 *  (Issue, Article, PublishedArticle, ArticleGalley, SuppFile)
	 * @return array
	 */
	function getPubObjectType($pubObject) {
		$allowedTypes = array(
			'Issue' => 'Issue',
			'Article' => 'Article',
			'ArticleGalley' => 'Galley',
			'SuppFile' => 'SuppFile'
		);
		$pubObjectType = null;
		foreach ($allowedTypes as $allowedType => $pubObjectTypeCandidate) {
			if (is_a($pubObject, $allowedType)) {
				$pubObjectType = $pubObjectTypeCandidate;
				break;
			}
		}
		if (is_null($pubObjectType)) {
			// This must be a dev error, so bail with an assertion.
			assert(false);
			return null;
		}
		return $pubObjectType;
	}

	/**
	 * Return an array of the corresponding DAOs.
	 * @return array
	 */
	protected function getDAOs() {
		return array('IssueDAO', 'ArticleDAO', 'ArticleGalleyDAO', 'SuppFileDAO');
	}



	/**
	 * Add the suffix element and the public identifier
	 * to the object (issue, article, galley, supplementary file).
	 * @param $hookName string
	 * @param $params array ()
	 */
	function getAdditionalFieldNames($hookName, $params) {
		$fields =& $params[1];
		$formFieldNames = $this->getFormFieldNames();
		foreach ($formFieldNames as $formFieldName) {
			$fields[] = $formFieldName;
		}
		$daoFieldNames = $this->getDAOFieldNames();
		foreach ($daoFieldNames as $daoFieldName) {
			$fields[] = $daoFieldName;
		}
		return false;
	}

	/**
	 * Determine whether or not this plugin is enabled.
	 */
	function getEnabled($journalId = null) {
		if (!$journalId) {
			$request =& Application::getRequest();
			$router =& $request->getRouter();
			$journal =& $router->getContext($request);

			if (!$journal) return false;
			$journalId = $journal->getid();
		}
		return $this->getSetting($journalId, 'enabled');
	}

	/**
	 * Set the enabled/disabled state of this plugin.
	 */
	function setEnabled($enabled) {
		$journal =& Request::getJournal();
		if ($journal) {
			$this->updateSetting(
				$journal->getId(),
				'enabled',
				$enabled?true:false
			);
			return true;
		}
		return false;
	}

	/**
	 * Display verbs for the management interface.
	 */
	function getManagementVerbs() {
		$verbs = array();
		if ($this->getEnabled()) {
			$verbs[] = array(
				'disable',
				Locale::translate('manager.plugins.disable')
			);
		} else {
			$verbs[] = array(
				'enable',
				Locale::translate('manager.plugins.enable')
			);
		}
		return $verbs;
	}

	/**
	 * Perform management functions.
	 */
	function manage($verb, $args) {
		$templateManager =& TemplateManager::getManager();
		$templateManager->register_function('plugin_url', array(&$this, 'smartyPluginUrl'));
		switch ($verb) {
			case 'enable': $this->setEnabled(true); break;
			case 'disable': $this->setEnabled(false); break;
		}
		return false;
	}

}

?>
