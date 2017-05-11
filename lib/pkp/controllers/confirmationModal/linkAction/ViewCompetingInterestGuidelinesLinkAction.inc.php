<?php

/**
 * @file controllers/confirmationModal/linkAction/ViewCompetingInterestGuidelinesLinkAction.inc.php
 *
 * Copyright (c) 2014-2017 Simon Fraser University
 * Copyright (c) 2003-2017 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ViewCompetingInterestGuidelinesLinkAction
 * @ingroup controllers_confirmationModal_linkAction
 *
 * @brief An action to open the competing interests confirmation modal.
 */

import('lib.pkp.classes.linkAction.LinkAction');

class ViewCompetingInterestGuidelinesLinkAction extends LinkAction {

	/**
	 * Constructor
	 * @param $request Request
	 */
	function __construct($request) {
		$context = $request->getContext();
		// Instantiate the view competing interests modal.
		import('lib.pkp.classes.linkAction.request.ConfirmationModal');
		$viewCompetingInterestsModal = new ConfirmationModal(
			$context->getLocalizedSetting('competingInterests'),
			__('reviewer.submission.competingInterests'),
			null, null, false,
			false
		);

		// Configure the link action.
		parent::__construct('viewCompetingInterestGuidelines', $viewCompetingInterestsModal, __('reviewer.submission.competingInterests'));
	}
}

?>
