<?php

/**
 * @file plugins/generic/externalFeed/ExternalFeedForm.inc.php
 *
 * Copyright (c) 2014-2018 Simon Fraser University
 * Copyright (c) 2003-2018 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedForm
 * @ingroup plugins_generic_externalFeed
 *
 * @brief Form for journal managers to mody external feed plugin settings
 */

import('lib.pkp.classes.form.Form');

class ExternalFeedForm extends Form {

	/** @var object */
	var $plugin;

	/** @var int */
	var $feedId;

	/** @var int */
	var $journalId;

	/**
	 * Constructor
	 * @param $plugin object
	 * @param $journalId int
	 * @param $feedId int
	 */
	function __construct(&$plugin, $feedId, $journalId) {
		$this->plugin =& $plugin;
		$this->feedId = isset($feedId) ? $feedId : null;
		$this->journalId = $journalId;

		parent::__construct($plugin->getTemplateResource('externalFeedForm.tpl'));

		// Feed URL is provided
		$this->addCheck(new FormValidatorUrl($this, 'feedUrl', 'required', 'plugins.generic.externalFeed.form.feedUrlValid'));

		// Feed title is provided
		$this->addCheck(new FormValidatorLocale($this, 'title', 'required', 'plugins.generic.externalFeed.form.titleRequired'));

		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
	}

	/**
	* Get the names of fields for which localized data is allowed.
	* @return array
	*/
	function getLocaleFieldNames() {
		$feedDao = DAORegistry::getDAO('ExternalFeedDAO');
		return $feedDao->getLocaleFieldNames();
	}

	/**
	 * @copydoc Form::display
	 */
	function display($request = null, $template = null) {
		$plugin = $this->plugin;
		$request = Application::getRequest();
		$templateMgr = TemplateManager::getManager($request);
		$templateMgr->assign('feedId', $this->feedId);

		$plugin->import('ExternalFeed');

		parent::display($request, $template);
	}

	/**
	 * Initialize form data.
	 */
	function initData() {
		if (isset($this->feedId)) {
			$feedDao = DAORegistry::getDAO('ExternalFeedDAO');
			$feed =& $feedDao->getExternalFeed($this->feedId);

			if ($feed != null) {
				$this->_data = array(
					'feedUrl' => $feed->getUrl(),
					'title' => $feed->getTitle(null),
					'displayHomepage' => $feed->getDisplayHomepage(),
					'displayBlock' => $feed->getDisplayBlock(),
					'limitItems' => $feed->getLimitItems(),
					'recentItems' => $feed->getRecentItems()
				);
			} else {
				$this->feedId = null;
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(
			array(
				'feedUrl',
				'title',
				'displayHomepage',
				'displayBlock',
				'limitItems',
				'recentItems'
			)
		);

		// Check that recent items value is a positive integer
		if ((int) $this->getData('recentItems') <= 0) $this->setData('recentItems', '');

		// If recent items is selected, check that we have a value
		if ($this->getData('limitItems')) {
			$this->addCheck(new FormValidator($this, 'recentItems', 'required', 'plugins.generic.externalFeed.settings.recentItemsRequired'));
		}

	}

	/**
	 * Save settings.
	 */
	function execute() {
		$plugin =& $this->plugin;

		$externalFeedDao = DAORegistry::getDAO('ExternalFeedDAO');
		$plugin->import('ExternalFeed');

		if (isset($this->feedId)) {
			$feed =& $externalFeedDao->getExternalFeed($this->feedId);
		}

		if (!isset($feed)) {
			$feed = new ExternalFeed();
		}

		$feed->setJournalId($this->journalId);
		$feed->setUrl($this->getData('feedUrl'));
		$feed->setTitle($this->getData('title'), null);
		$feed->setDisplayHomepage($this->getData('displayHomepage') ? 1 : 0);
		$feed->setDisplayBlock($this->getData('displayBlock') ? $this->getData('displayBlock') : EXTERNAL_FEED_DISPLAY_BLOCK_NONE);
		$feed->setLimitItems($this->getData('limitItems') ? 1 : 0);
		$feed->setRecentItems($this->getData('recentItems') ? $this->getData('recentItems') : 0);

		// Update or insert external feed
		if ($feed->getId() != null) {
			$externalFeedDao->updateExternalFeed($feed);
		} else {
			$feed->setSequence(REALLY_BIG_NUMBER);
			$externalFeedDao->insertExternalFeed($feed);

			// Re-order the feeds so the new one is at the end of the list.
			$externalFeedDao->resequenceExternalFeeds($feed->getJournalId());
		}
	}

}


