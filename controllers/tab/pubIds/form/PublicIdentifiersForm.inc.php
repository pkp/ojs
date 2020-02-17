<?php

/**
 * @file controllers/tab/pubIds/form/PublicIdentifiersForm.inc.php
 *
 * Copyright (c) 2014-2020 Simon Fraser University
 * Copyright (c) 2003-2020 John Willinsky
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
		$enablePublisherId = $request->getContext()->getData('enablePublisherId');
		$templateMgr->assign([
			'enablePublisherId' => (is_a($this->getPubObject(), 'ArticleGalley') && in_array('galley', $enablePublisherId))
		]);

		return parent::fetch($request, $template, $display);
	}

}

