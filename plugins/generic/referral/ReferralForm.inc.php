<?php

/**
 * @file classes/manager/form/ReferralForm.inc.php
 *
 * Copyright (c) 2013-2015 Simon Fraser University Library
 * Copyright (c) 2003-2015 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReferralForm
 * @ingroup manager_form
 * @see AnnouncementForm
 *
 * @brief Form for authors to create/edit referrals.
 */

import('lib.pkp.classes.form.Form');

class ReferralForm extends Form {
	/** @var referralId int the ID of the referral being edited */
	var $referralId;

	/** @var $article object the article this referral refers to */
	var $article;

	/**
	 * Constructor
	 * @param referralId int leave as default for new referral
	 */
	function ReferralForm(&$plugin, &$article, $referralId = null) {
		$this->referralId = isset($referralId) ? (int) $referralId : null;
		$this->article =& $article;

		parent::Form($plugin->getTemplatePath() . 'referralForm.tpl');

		// Name is provided
		$this->addCheck(new FormValidatorLocale($this, 'name', 'required', 'plugins.generic.referral.nameRequired'));
		$this->addCheck(new FormValidatorURL($this, 'url', 'required', 'plugins.generic.referral.urlRequired'));

		$this->addCheck(new FormValidatorPost($this));
	}

	/**
	 * Get a list of localized field names for this form
	 * @return array
	 */
	function getLocaleFieldNames() {
		$referralDao =& DAORegistry::getDAO('ReferralDAO');
		return $referralDao->getLocaleFieldNames();
	}

	/**
	 * Display the form.
	 */
	function display() {
		$templateMgr =& TemplateManager::getManager();
		$templateMgr->assign('referralId', $this->referralId);
		$templateMgr->assign_by_ref('article', $this->article);
		// $templateMgr->assign('helpTopicId', 'FIXME');

		parent::display();
	}

	/**
	 * Initialize form data from current referral.
	 */
	function initData() {
		if (isset($this->referralId)) {
			$referralDao =& DAORegistry::getDAO('ReferralDAO');
			$referral =& $referralDao->getReferral($this->referralId);

			if ($referral != null) {
				$this->_data = array(
					'name' => $referral->getName(null), // Localized
					'status' => $referral->getStatus(),
					'url' => $referral->getUrl()
				);

			} else {
				$this->referralId = null;
			}
		}
	}

	/**
	 * Assign form data to user-submitted data.
	 */
	function readInputData() {
		$this->readUserVars(array('name', 'url', 'status'));
	}

	/**
	 * Save referral. 
	 */
	function execute() {
		$referralDao =& DAORegistry::getDAO('ReferralDAO');

		if (isset($this->referralId)) {
			$referral =& $referralDao->getReferral($this->referralId);
		}

		if (!isset($referral)) {
			$referral = new Referral();
			$referral->setDateAdded(Core::getCurrentDate());
			$referral->setLinkCount(0);
		}

		$referral->setArticleId($this->article->getId());
		$referral->setName($this->getData('name'), null); // Localized
		$referral->setUrl($this->getData('url'));
		$referral->setStatus($this->getData('status'));

		// Update or insert referral
		if ($referral->getId() != null) {
			$referralDao->updateReferral($referral);
		} else {
			$referralDao->insertReferral($referral);
		}
	}
}

?>
