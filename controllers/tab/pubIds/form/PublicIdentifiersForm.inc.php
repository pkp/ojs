<?php

/**
 * @file controllers/tab/pubIds/form/PublicIdentifiersForm.inc.php
 *
 * Copyright (c) 2014-2016 Simon Fraser University Library
 * Copyright (c) 2003-2016 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class PublicIdentifiersForm
 * @ingroup controllers_tab_pubIds_form
 *
 * @brief Displays a pub ids form.
 */

import('lib.pkp.controllers.tab.pubIds.form.PKPPublicIdentifiersForm');

class PublicIdentifiersForm extends PKPPublicIdentifiersForm {

	/**
	 * Constructor.
	 * @param $pubObject object
	 * @param $stageId integer
	 * @param $formParams array
	 */
	function PublicIdentifiersForm($pubObject, $stageId = null, $formParams = null) {
		parent::PKPPublicIdentifiersForm('controllers/tab/pubIds/form/publicIdentifiersForm.tpl', $pubObject, $stageId, $formParams);
		$this->setData('publisherId', $pubObject->getStoredPubId('publisher-id'));
	}

	/**
	 * @copydoc Form::validate()
	 */
	function validate() {
		$pubObject = $this->getPubObject();
		// check if public issue ID has already been used
		$assocType = null;
		if (is_a($pubObject, 'Article')) {
			$assocType = ASSOC_TYPE_ARTICLE;
		} elseif (is_a($pubObject, 'Representation')) {
			$assocType = ASSOC_TYPE_REPRESENTATION;
		} elseif (is_a($pubObject, 'SubmissionFile')) {
			$assocType = ASSOC_TYPE_SUBMISSION_FILE;
		} elseif (is_a($pubObject, 'Issue')) {
			$assocType = ASSOC_TYPE_ISSUE;
		}
		$publisherId = $this->getData('publisherId');
		$pubObjectId = $pubObject->getId();
		if ($assocType == ASSOC_TYPE_SUBMISSION_FILE) {
			$pubObjectId = $pubObject->getFileId();
		}
		$journalDao = DAORegistry::getDAO('JournalDAO'); /* @var $journalDao JournalDAO */
		if ($publisherId && $journalDao->anyPubIdExists($this->getContextId(), 'publisher-id', $publisherId, $assocType, $pubObjectId, true)) {
			$this->addError('publisherId', __('editor.publicIdentificationExistsForTheSameType', array('publicIdentifier' => $publisherId)));
			$this->addErrorField('$publisherId');
		}
		return parent::validate();
	}

	/**
	 * Store objects with pub ids.
	 * @copydoc Form::execute()
	 */
	function execute($request) {
		parent::execute($request);
		$pubObject = $this->getPubObject();
		if (is_a($pubObject, 'Issue')) {
			$issueDao = DAORegistry::getDAO('IssueDAO');
			$issueDao->updateObject($pubObject);
		}
	}

	/**
	 * Clear issue objects pub ids.
	 * @param $pubIdPlugInClassName string
	 */
	function clearIssueObjectsPubIds($pubIdPlugInClassName) {
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		if (is_array($pubIdPlugins)) {
			foreach ($pubIdPlugins as $pubIdPlugin) {
				if (get_class($pubIdPlugin) == $pubIdPlugInClassName) {
					$pubIdPlugin->clearIssueObjectsPubIds($this->getPubObject());
				}
			}
		}
	}

}

?>
