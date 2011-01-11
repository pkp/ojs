<?php

/**
 * @file plugins/generic/sword/AuthorDepositForm.inc.php
 *
 * Copyright (c) 2003-2011 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class AuthorDepositForm
 * @ingroup plugins_generic_sword
 *
 * @brief Form to perform an author's SWORD deposit(s)
 */

// $Id$


import('lib.pkp.classes.form.Form');

class AuthorDepositForm extends Form {
	/** @var $article object */
	var $article;

	/** @var $swordPlugin object */
	var $swordPlugin;

	/**
	 * Constructor.
	 */
	function AuthorDepositForm(&$swordPlugin, &$article) {
		parent::Form($swordPlugin->getTemplatePath() . '/authorDepositForm.tpl');

		$this->swordPlugin =& $swordPlugin;
		$this->article =& $article;
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();

		$depositPoints = $this->_getDepositableDepositPoints();
		// For the sake of the UI, figure out whether we're dealing with any
		// sword URLs where deposit points are to be chosen by the author.
		$hasFlexible = false;
		foreach ($depositPoints as $depositPoint) {
			if ($depositPoint['type'] == SWORD_DEPOSIT_TYPE_OPTIONAL_SELECTION) {
				$hasFlexible = true;
			}
		}
		$templateMgr->assign('depositPoints', $depositPoints);
		$templateMgr->assign_by_ref('article', $this->article);
		$templateMgr->assign('hasFlexible', $hasFlexible);
		$templateMgr->assign('allowAuthorSpecify', $this->swordPlugin->getSetting($this->article->getJournalId(), 'allowAuthorSpecify'));
		parent::display();
	}

	/**
	 * Initialize form data from default settings.
	 */
	function initData() {
		$this->_data = array(
		);
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array(
			'authorDepositUrl',
			'authorDepositUsername',
			'authorDepositPassword',
			'depositPoint'
		));
	}

	/**
	 * Perform SWORD deposit
	 */
	function execute() {
		import('classes.sword.OJSSwordDeposit');
		$deposit = new OJSSwordDeposit($this->article);
		$deposit->setMetadata();
		$deposit->addEditorial();
		$deposit->createPackage();

		import('lib.pkp.classes.notification.NotificationManager');
		$notificationManager = new NotificationManager();

		$allowAuthorSpecify = $this->swordPlugin->getSetting($this->article->getJournalId(), 'allowAuthorSpecify');
		$authorDepositUrl = $this->getData('authorDepositUrl');
		if ($allowAuthorSpecify && $authorDepositUrl != '') {
			$deposit->deposit(
				$this->getData('authorDepositUrl'),
				$this->getData('authorDepositUsername'),
				$this->getData('authorDepositPassword')
			);

			$notificationManager->createTrivialNotification(Locale::translate('notification.notification'), Locale::translate('plugins.generic.sword.depositComplete', array('itemTitle' => $this->article->getLocalizedTitle(), 'repositoryName' => $this->getData('authorDepositUrl'))), NOTIFICATION_TYPE_SUCCESS, null, false);
		}

		$depositableDepositPoints = $this->_getDepositableDepositPoints();
		$depositPoints = $this->getData('depositPoint');
		foreach ($depositableDepositPoints as $key => $depositPoint) {
			if (!isset($depositPoints[$key]['enabled'])) continue;

			if ($depositPoint['type'] == SWORD_DEPOSIT_TYPE_OPTIONAL_SELECTION) {
				$url = $depositPoints[$key]['depositPoint'];
			} else { // SWORD_DEPOSIT_TYPE_OPTIONAL_FIXED
				$url = $depositPoint['url'];
			}

			$deposit->deposit(
				$url,
				$depositPoint['username'],
				$depositPoint['password']
			);

			$notificationManager->createTrivialNotification(Locale::translate('notification.notification'), Locale::translate('plugins.generic.sword.depositComplete', array('itemTitle' => $this->article->getLocalizedTitle(), 'repositoryName' => $depositPoint['name'])), NOTIFICATION_TYPE_SUCCESS, null, false);
		}

		$deposit->cleanup();
	}

	function _getDepositableDepositPoints() {
		import('classes.sword.OJSSwordDeposit');
		$depositPoints = $this->swordPlugin->getSetting($this->article->getJournalId(), 'depositPoints');
		foreach ($depositPoints as $key => $depositPoint) {
			$type = $depositPoint['type'];
			if ($type == SWORD_DEPOSIT_TYPE_OPTIONAL_SELECTION) {
				// Get a list of supported deposit points
				$client = new SWORDAPPClient();
				$doc = $client->servicedocument(
					$depositPoint['url'],
					$depositPoint['username'],
					$depositPoint['password'],
					''
				);
				$points = array();
				foreach ($doc->sac_workspaces as $workspace) {
					foreach ($workspace->sac_collections as $collection) {
						$points["$collection->sac_href"] = "$collection->sac_colltitle";
					}
				}
				unset($client);
				unset($doc);
				$depositPoints[$key]['depositPoints'] = $points;
			} elseif ($type == SWORD_DEPOSIT_TYPE_OPTIONAL_FIXED) {
				// Don't need to do anything special
			} else {
				unset($depositPoints[$key]);
			}
		}
		return $depositPoints;
	}
}

?>
