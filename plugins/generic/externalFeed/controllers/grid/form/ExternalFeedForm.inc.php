<?php 
/**
 * @file controllers/grid/form/ExternalFeedForm.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ExternalFeedForm
 * @ingroup controllers_grid_externalFeed
 *
 * Form to create and modify external feeds
 *
 */

import('lib.pkp.classes.form.Form');

class ExternalFeedForm extends Form {
	/** @var int Context (press / journal) ID */
	protected $contextId;
	
	/** @var string Static page name */
	protected $feedId;
	
	/** @var ExternalFeedPlugin External feed plugin */
	protected $plugin;
	
	/**
	 * Constructor
	 * @param $externalFeedPlugin StaticPagesPlugin The static page plugin
	 * @param $contextId int Context ID
	 * @param $feedId int Static page ID (if any)
	 */
	function __construct($externalFeedPlugin, $contextId, $feedId = null) {
		parent::__construct($externalFeedPlugin->getTemplatePath() . '/editExternalFeedForm.tpl');
		$this->contextId = $contextId;
		$this->feedId = $feedId;
		$this->plugin = $externalFeedPlugin;
		
		// Add form checks
		$this->addCheck(new FormValidatorPost($this));
		$this->addCheck(new FormValidatorCSRF($this));
		$this->addCheck(new FormValidatorUrl($this, 'feedUrl', 'required', 'plugins.generic.externalFeed.form.feedUrlValid'));
		$this->addCheck(new FormValidator($this, 'title', 'required', 'plugins.generic.externalFeed.form.titleRequired'));
	}
	
	/**
	 * Initialize form data.
	 */
	function initData() {
		if ($this->feedId) {
			$feedDao = DAORegistry::getDAO('ExternalFeedDAO');
			$feed = $feedDao->getExternalFeed($this->feedId);
			
			$this->setData('feedUrl', $feed->getUrl());
			$this->setData('title', $feed->getTitle(AppLocale::getLocale()));
			$this->setData('displayHomepage', $feed->getDisplayHomepage());
			$this->setData('displayBlock', $feed->getDisplayBlock());
			$this->setData('limitItems', $feed->getLimitItems());
			
			$recentItems = (int) $feed->getRecentItems();
			if ($recentItems > 0) {
				$this->setData('recentItems', $feed->getRecentItems());
			}
			else {
				$this->setData('recentItems', '');
			}
		}
	}
	
	/**
	 * Assign form data to user-submitted data.
	 */
	public function readInputData() {
		$this->readUserVars(
			array(
				'feedUrl',
				'displayHomepage',
				'displayBlock',
				'limitItems',
				'recentItems'
			)
		);
		
		$this->setData('title', Request::getUserVar('title')[AppLocale::getLocale()]);
		
		// Check that recent items value is a positive integer
		if ((int) $this->getData('recentItems') <= 0) $this->setData('recentItems', '');
		
		// If recent items is selected, check that we have a value
		if ($this->getData('limitItems')) {
			$this->addCheck(new FormValidator($this, 'recentItems', 'required', 'plugins.generic.externalFeed.settings.recentItemsRequired'));
		}
	}
	
	/**
	 * @see Form::fetch
	 */
	function fetch($request) {
		$templateMgr = TemplateManager::getManager();
		$templateMgr->assign('feedId', $this->feedId);
		$templateMgr->assign('formLocale', AppLocale::getLocale());
		
		return parent::fetch($request);
	}
	
	/**
	 * Save settings.
	 */
	public function execute() {
		$plugin = $this->plugin;
	
		$externalFeedDao = DAORegistry::getDAO('ExternalFeedDAO');
		$plugin->import('classes.ExternalFeed');
	
		if (isset($this->feedId)) {
			$feed = $externalFeedDao->getExternalFeed($this->feedId);
		}
	
		if (!isset($feed)) {
			$feed = new ExternalFeed();
		}
	
		$feed->setJournalId($this->contextId);
		$feed->setUrl($this->getData('feedUrl'));
		$feed->setTitle($this->getData('title'), AppLocale::getLocale());
		$feed->setDisplayHomepage($this->getData('displayHomepage') ? 1 : 0);
		$feed->setDisplayBlock($this->getData('displayBlock') ? $this->getData('displayBlock') : EXTERNAL_FEED_DISPLAY_BLOCK_NONE);
		
		$limitItems = (int) $this->getData('limitItems');
		$recentItems = (int) $this->getData('recentItems');
		$feed->setLimitItems(($recentItems || $limitItems) ? 1 : 0);
		$feed->setRecentItems($recentItems ? $recentItems : 0);
	
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