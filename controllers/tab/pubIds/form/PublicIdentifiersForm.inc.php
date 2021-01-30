<?php

/**
 * @file controllers/tab/pubIds/form/PublicIdentifiersForm.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
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
	function __construct($pubObject, $stageId = null, $formParams = null) {
		parent::__construct($pubObject, $stageId, $formParams);
	}

	/**
	 * @copydoc Form::fetch()
	 */
	function fetch($request, $template = null, $display = false) {
		$templateMgr = TemplateManager::getManager($request);
		$enablePublisherId = (array) $request->getContext()->getData('enablePublisherId');
		$templateMgr->assign([
			'enablePublisherId' => (is_a($this->getPubObject(), 'ArticleGalley') && in_array('galley', $enablePublisherId)) ||
					(is_a($this->getPubObject(), 'Issue') && in_array('issue', $enablePublisherId)) ||
					(is_a($this->getPubObject(), 'IssueGalley') && in_array('issueGalley', $enablePublisherId)),
		]);

		return parent::fetch($request, $template, $display);
	}

	/**
	 * @copydoc Form::execute()
	 */
	function execute(...$functionArgs) {
		parent::execute(...$functionArgs);
		$pubObject = $this->getPubObject();
		if (is_a($pubObject, 'Issue')) {
			$issueDao = DAORegistry::getDAO('IssueDAO'); /* @var $issueDao IssueDAO */
			$issueDao->updateObject($pubObject);
		}
	}

	/**
	 * Clear issue objects pub ids.
	 * @param $pubIdPlugInClassName string
	 */
	function clearIssueObjectsPubIds($pubIdPlugInClassName) {
		$pubIdPlugins = PluginRegistry::loadCategory('pubIds', true);
		foreach ($pubIdPlugins as $pubIdPlugin) {
			if (get_class($pubIdPlugin) == $pubIdPlugInClassName) {
				$pubIdPlugin->clearIssueObjectsPubIds($this->getPubObject());
			}
		}
	}

	/**
	 * @copydoc PKPPublicIdentifiersForm::getAssocType()
	 */
	function getAssocType($pubObject) {
		if (is_a($pubObject, 'Issue')) {
			return ASSOC_TYPE_ISSUE;
		}
		return parent::getAssocType($pubObject);
	}

}


